<?php

/**
 * Extract plain text from an uploaded CV PDF (text-based PDF).
 * Scan/photographed PDFs typically have no text layer and will fail or return empty text.
 */
if (!class_exists('PdfTextExtractor')) {
    require_once __DIR__ . '/../composer_bootstrap.php';

    class PdfTextExtractor
    {
        /**
         * @param string $absolutePath Absolute path to PDF file on filesystem
         * @return array{ok: bool, text?: string, message: string}
         */
        public static function extract(string $absolutePath): array
        {
            if (!is_file($absolutePath)) {
                return ['ok' => false, 'message' => 'File PDF không tồn tại.'];
            }

            if (!class_exists(\Smalot\PdfParser\Parser::class)) {
                return [
                    'ok' => false,
                    'message' => 'Chưa cài thư viện PDF parser (vendor/).',
                ];
            }

            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($absolutePath);
                $text = (string) $pdf->getText();
            } catch (\Throwable $e) {
                return [
                    'ok' => false,
                    'message' => 'Không thể đọc nội dung PDF: ' . ($e->getMessage() ?: 'unknown error'),
                ];
            }

            // Normalize whitespace to make downstream parsing more stable.
            $text = preg_replace('/\s+/u', ' ', trim($text ?? ''));
            if (!is_string($text) || $text === '') {
                return [
                    'ok' => false,
                    'message' => 'PDF không có text layer (có thể là file scan).',
                ];
            }

            return ['ok' => true, 'text' => $text, 'message' => ''];
        }
    }
}

