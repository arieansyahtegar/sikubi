<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnomalyFlag extends Model
{
    protected $fillable = [
        'transaction_id', 'detection_method', 'score', 'severity',
        'reason', 'is_reviewed', 'is_dismissed', 'needs_leader_action', 'review_note', 'detected_at',
        'is_approved_by_leader', 'leader_note', 'leader_reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:4',
            'is_reviewed' => 'boolean',
            'is_dismissed' => 'boolean',
            'needs_leader_action' => 'boolean',
            'detected_at' => 'datetime',
            'is_approved_by_leader' => 'boolean',
            'leader_reviewed_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
