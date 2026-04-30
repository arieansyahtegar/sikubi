<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class PdfParserService
{
    /**
     * Extract text content from a PDF bank statement.
     * Converts the extracted text into CSV-like lines for the import service.
     */
    public function extractText(string $filePath): string
    {
        try {
            $config = new \Smalot\PdfParser\Config();
            // Bypass "Secured pdf file" error for PDFs that only have Owner passwords
            $config->setIgnoreEncryption(true);
            
            $parser = new Parser([], $config);
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            Log::info('PDF text extracted', ['length' => strlen($text), 'file' => basename($filePath)]);

            return $text;
        } catch (\Exception $e) {
            Log::error('PDF parsing failed: ' . $e->getMessage());
            throw new \RuntimeException('Gagal membaca file PDF: ' . $e->getMessage());
        }
    }

    /**
     * Convert raw PDF text into structured lines suitable for the adaptive parser.
     * Handles BRI PDF format specifically.
     */
    public function convertToLines(string $rawText): array
    {
        $lines = explode("\n", $rawText);
        $cleaned = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                $cleaned[] = $trimmed;
            }
        }

        return $cleaned;
    }
}
