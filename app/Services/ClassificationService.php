<?php

namespace App\Services;

use App\Models\ClassificationRule;
use App\Models\Transaction;

class ClassificationService
{
    /**
     * 3-Stage Classification Pipeline
     * Stage 1: Rule-based exact/contains matching (confidence: 1.0)
     * Stage 2: Fuzzy pattern matching via token similarity (confidence: 0.6-0.9)
     * Stage 3: Historical KNN similarity (confidence: 0.5-0.8)
     */
    public function classify(string $description, string $type): array
    {
        $upperDesc = strtoupper(trim($description));

        // Stage 1: Rule-based matching (strict type match first)
        $rules = ClassificationRule::with('category')
            ->orderBy('priority', 'asc')
            ->get();

        // 1a: Match rules where category type matches transaction type
        foreach ($rules as $rule) {
            if (!$rule->category) continue;
            if ($rule->category->type !== $type) continue;

            if ($this->matchesRule($upperDesc, $rule)) {
                $rule->incrementHit();
                return [
                    'category_id' => $rule->category_id,
                    'method' => 'RULE_BASED',
                    'confidence' => 1.0,
                ];
            }
        }

        // 1b: Try matching ANY rule regardless of type (for ambiguous cases)
        // BCA descriptions often don't clearly indicate type from text alone
        foreach ($rules as $rule) {
            if (!$rule->category) continue;
            // Skip if type mismatch — but only for multi-word patterns (more specific)
            if ($rule->category->type !== $type && str_word_count($rule->pattern) > 1) continue;

            if ($this->matchesRule($upperDesc, $rule)) {
                $rule->incrementHit();
                return [
                    'category_id' => $rule->category_id,
                    'method' => 'RULE_BASED',
                    'confidence' => 0.85,
                ];
            }
        }

        // Stage 2: Fuzzy pattern matching (token-based Jaccard + substring matching)
        $descTokens = $this->tokenize($upperDesc);
        $bestMatch = null;

        foreach ($rules as $rule) {
            if (!$rule->category) continue;
            if ($rule->category->type !== $type) continue;

            $ruleTokens = $this->tokenize(strtoupper($rule->pattern));

            // Check if ALL rule tokens appear in the description (substring match)
            $allFound = true;
            foreach ($ruleTokens as $rToken) {
                if (!str_contains($upperDesc, $rToken)) {
                    $allFound = false;
                    break;
                }
            }

            if ($allFound && count($ruleTokens) > 0) {
                $similarity = count($ruleTokens) / max(count($descTokens), 1);
                $similarity = min($similarity + 0.5, 0.95); // boost because all tokens found
            } else {
                $similarity = $this->tokenSimilarity($descTokens, $ruleTokens);
            }

            if ($similarity > 0.4 && (!$bestMatch || $similarity > $bestMatch['similarity'])) {
                $bestMatch = [
                    'category_id' => $rule->category_id,
                    'similarity' => $similarity,
                ];
            }
        }

        if ($bestMatch && $bestMatch['similarity'] > 0.4) {
            return [
                'category_id' => $bestMatch['category_id'],
                'method' => 'PATTERN_MATCH',
                'confidence' => min($bestMatch['similarity'], 0.9),
            ];
        }

        // Stage 3: Historical similarity (KNN from past classified transactions)
        $historicalTxs = Transaction::where('type', $type)
            ->whereNotNull('category_id')
            ->whereIn('classification_method', ['RULE_BASED', 'MANUAL'])
            ->select('description', 'category_id')
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        if ($historicalTxs->count() > 0) {
            $scored = $historicalTxs->map(function ($tx) use ($descTokens) {
                return [
                    'category_id' => $tx->category_id,
                    'similarity' => $this->tokenSimilarity(
                        $descTokens,
                        $this->tokenize(strtoupper($tx->description))
                    ),
                ];
            })->filter(fn($s) => $s['similarity'] > 0.3)
              ->sortByDesc('similarity')
              ->take(5)
              ->values();

            if ($scored->count() > 0) {
                $votes = [];
                foreach ($scored as $s) {
                    $catId = $s['category_id'];
                    if (!isset($votes[$catId])) {
                        $votes[$catId] = ['count' => 0, 'totalSim' => 0];
                    }
                    $votes[$catId]['count']++;
                    $votes[$catId]['totalSim'] += $s['similarity'];
                }

                uasort($votes, fn($a, $b) =>
                    $b['count'] <=> $a['count'] ?: $b['totalSim'] <=> $a['totalSim']
                );

                $winnerId = array_key_first($votes);
                $winner = $votes[$winnerId];
                $consensus = $winner['count'] / $scored->count();

                if ($consensus >= 0.4) {
                    return [
                        'category_id' => $winnerId,
                        'method' => 'HISTORICAL',
                        'confidence' => min($consensus * 0.8 + 0.2, 0.8),
                    ];
                }
            }
        }

        // Stage 4: Auto-Suggestion (create category if same keyword appears 3+ times unclassified)
        $keyword = $this->extractKeyword($upperDesc, $type);
        if ($keyword && mb_strlen($keyword) >= 3) {
            $similarUnclassified = Transaction::where('type', $type)
                ->whereNull('category_id')
                ->where('classification_method', 'UNCLASSIFIED')
                ->where('description', 'LIKE', "%{$keyword}%")
                ->count();

            if ($similarUnclassified >= 2) { // current + 2 existing = 3 total
                // Check if suggested category already exists for this keyword
                $suggested = \App\Models\Category::where('is_suggested', true)
                    ->where('name', 'LIKE', "%{$keyword}%")
                    ->where('type', $type)
                    ->first();

                if (!$suggested) {
                    $suggested = \App\Models\Category::create([
                        'name' => ucfirst(strtolower($keyword)),
                        'type' => $type,
                        'color' => '#F59E0B', // amber for suggested
                        'is_suggested' => true,
                        'suggestion_count' => $similarUnclassified + 1,
                    ]);

                    \Illuminate\Support\Facades\Log::info("Auto-created suggested category: {$suggested->name}", [
                        'keyword' => $keyword,
                        'count' => $similarUnclassified + 1,
                    ]);
                } else {
                    $suggested->increment('suggestion_count');
                }

                return [
                    'category_id' => $suggested->id,
                    'method' => 'AUTO_SUGGESTED',
                    'confidence' => 0.6,
                ];
            }
        }

        return ['category_id' => null, 'method' => 'UNCLASSIFIED', 'confidence' => 0];
    }

    /**
     * Check if description matches a rule
     */
    private function matchesRule(string $upperDesc, $rule): bool
    {
        $pattern = strtoupper(trim($rule->pattern));

        return match ($rule->match_type) {
            'EXACT' => ($upperDesc === $pattern),
            'CONTAINS' => str_contains($upperDesc, $pattern),
            'REGEX' => (bool) @preg_match('/' . $pattern . '/i', $upperDesc),
            default => false,
        };
    }

    /**
     * Tokenize a string into words (min 2 chars)
     */
    private function tokenize(string $text): array
    {
        return array_values(array_filter(
            preg_split('/[\s\-\/\.\,]+/', $text),
            fn($t) => mb_strlen($t) >= 2
        ));
    }

    /**
     * Jaccard token similarity
     */
    private function tokenSimilarity(array $a, array $b): float
    {
        if (empty($a) || empty($b)) return 0;

        $setA = array_unique($a);
        $setB = array_unique($b);
        $intersection = count(array_intersect($setA, $setB));
        $union = count(array_unique(array_merge($setA, $setB)));

        return $union > 0 ? $intersection / $union : 0;
    }

    /**
     * Extract the most meaningful keyword from a description (excluding banking stopwords).
     */
    private function extractKeyword(string $upperDesc, string $type): ?string
    {
        $stopwords = [
            'TRSF', 'TRANSFER', 'E-BANKING', 'CR', 'DB', 'DARI', 'KE', 'UNTUK',
            'BANK', 'BCA', 'BRI', 'BNI', 'MANDIRI', 'PERMATA', 'CIMB',
            'MCM', 'INHOUSETRF', 'FEE', 'BIAYA', 'ADM', 'ADMIN',
            'ATM', 'EDC', 'TUNAI', 'KARTU', 'DEBIT', 'KREDIT',
            'PT', 'CV', 'UD', 'TBK', 'SIA', 'ITA',
            'TANGGAL', 'SALDO', 'MUTASI', 'DAN', 'YANG', 'ATAU',
        ];

        $tokens = $this->tokenize($upperDesc);
        $meaningful = array_filter($tokens, function ($t) use ($stopwords) {
            return !in_array($t, $stopwords) && mb_strlen($t) >= 3 && !preg_match('/^\d+$/', $t);
        });

        // Return the longest meaningful token
        if (empty($meaningful)) return null;
        usort($meaningful, fn($a, $b) => mb_strlen($b) - mb_strlen($a));
        return $meaningful[0] ?? null;
    }
}
