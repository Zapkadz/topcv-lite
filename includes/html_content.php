<?php
/**
 * Hiển thị nội dung HTML từ CKEditor (mô tả job, v.v.).
 */

if (!function_exists('html_to_plain')) {
    /** Bỏ thẻ HTML, trả về text thuần (dùng cho đoạn xem trước). */
    function html_to_plain(string $html, ?int $maxLength = null): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';

        if ($maxLength !== null && $maxLength > 0 && mb_strlen($text) > $maxLength) {
            return mb_substr($text, 0, $maxLength) . '…';
        }

        return $text;
    }
}

if (!function_exists('html_display')) {
    /** Render HTML an toàn (chỉ giữ thẻ định dạng cơ bản). */
    function html_display(string $html): string
    {
        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><h4><a><blockquote>';

        return strip_tags($html, $allowed);
    }
}

if (!function_exists('textarea_editor_content')) {
    /** Nội dung ban đầu cho textarea + CKEditor (giữ HTML, chống thoát thẻ textarea). */
    function textarea_editor_content(?string $html): string
    {
        $html = $html ?? '';

        return preg_replace('/<\/textarea/i', '&lt;/textarea', $html) ?? $html;
    }
}
