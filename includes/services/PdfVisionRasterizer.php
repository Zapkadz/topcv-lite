<?php

require_once __DIR__ . '/../composer_bootstrap.php';

/**
 * Rasterize PDF pages → PNG base64 data URLs (Imagick). Optional — không bắt buộc trên XAMPP.
 */
class PdfVisionRasterizer
{
    /**
     * @return array{ok: bool, images?: list<string>, message: string, page_count?: int}
     */
    public static function rasterizePages(string $absolutePath, int $maxPages = 5): array
    {
        if (!extension_loaded('imagick') || !class_exists('Imagick')) {
            return ['ok' => false, 'message' => 'Imagick extension chưa cài — bỏ qua rasterize.'];
        }

        if (!is_file($absolutePath)) {
            return ['ok' => false, 'message' => 'File PDF không tồn tại.'];
        }

        try {
            $imagick = new Imagick();
            $imagick->setResolution(144, 144);
            $imagick->readImage($absolutePath);
            $pageCount = $imagick->getNumberImages();
            if ($pageCount <= 0) {
                return ['ok' => false, 'message' => 'PDF không có trang.'];
            }

            $limit = min($maxPages, $pageCount);
            $images = [];

            for ($i = 0; $i < $limit; $i++) {
                $imagick->setIteratorIndex($i);
                $page = $imagick->getImage();
                $page->setImageFormat('png');
                $page->setImageCompressionQuality(85);
                $blob = $page->getImageBlob();
                $page->clear();
                $page->destroy();

                if (!is_string($blob) || $blob === '') {
                    continue;
                }

                $images[] = 'data:image/png;base64,' . base64_encode($blob);
            }

            $imagick->clear();
            $imagick->destroy();

            if ($images === []) {
                return ['ok' => false, 'message' => 'Không rasterize được trang PDF.'];
            }

            return [
                'ok' => true,
                'images' => $images,
                'page_count' => $pageCount,
                'message' => '',
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Rasterize PDF lỗi: ' . ($e->getMessage() ?: 'unknown'),
            ];
        }
    }

    public static function countPages(string $absolutePath): int
    {
        if (!class_exists(\Smalot\PdfParser\Parser::class)) {
            return 0;
        }

        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($absolutePath);

            return count($pdf->getPages());
        } catch (\Throwable) {
            return 0;
        }
    }
}
