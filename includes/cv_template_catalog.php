<?php

require_once __DIR__ . '/cv_rules.php';
require_once __DIR__ . '/cv_preview_render.php';

if (!function_exists('cv_template_catalog')) {
    /**
     * @return array<string, array{key: string, label: string, description: string, tags: list<string>, preview_mode: string}>
     */
    function cv_template_catalog(): array
    {
        return [
            'classic' => [
                'key' => 'classic',
                'label' => 'Classic',
                'description' => 'Tiêu đề xanh nổi bật, bố cục dọc truyền thống — phù hợp đa số ngành nghề.',
                'tags' => ['Truyền thống', 'Bố cục dọc', 'Dễ đọc'],
                'preview_mode' => 'classic',
            ],
            'modern' => [
                'key' => 'modern',
                'label' => 'Modern',
                'description' => 'Sidebar xanh bên trái, nội dung trắng gọn gàng — nổi bật và hiện đại.',
                'tags' => ['Hiện đại', 'Sidebar', 'Nổi bật'],
                'preview_mode' => 'modern',
            ],
        ];
    }
}

if (!function_exists('cv_template_catalog_list')) {
    /**
     * @return list<array{key: string, label: string, description: string, tags: list<string>, preview_mode: string}>
     */
    function cv_template_catalog_list(): array
    {
        $catalog = cv_template_catalog();
        $out = [];
        foreach (cv_allowed_template_keys() as $key) {
            if (isset($catalog[$key])) {
                $out[] = $catalog[$key];
            }
        }

        return $out;
    }
}

if (!function_exists('cv_template_sample_preview_data')) {
    /**
     * Dữ liệu mẫu cố định để render preview card (không dùng dữ liệu user).
     *
     * @return array<string, mixed>
     */
    function cv_template_sample_preview_data(string $templateKey): array
    {
        $templateKey = cv_normalize_template_key($templateKey);

        return [
            'profile' => [
                'full_name' => 'Nguyễn Văn Minh',
                'target_position' => 'Nhân viên Marketing',
                'phone' => '0912345678',
                'email' => 'minh.nguyen@email.com',
                'address' => 'Quận 1, TP. Hồ Chí Minh',
                'career_objective' => 'Mong muốn phát triển trong lĩnh vực truyền thông số và thương hiệu.',
                'interests' => 'Đọc sách, thiết kế, công nghệ',
                'template_key' => $templateKey,
            ],
            'educations' => [[
                'school_name' => 'Đại học Kinh tế TP.HCM',
                'major' => 'Quản trị kinh doanh',
                'start_date' => '2020-09',
                'end_date' => '2024-06',
                'description' => '',
            ]],
            'experiences' => [[
                'company_name' => 'Công ty ABC',
                'position' => 'Thực tập sinh Marketing',
                'start_date' => '2023-06',
                'end_date' => '2024-01',
                'description' => 'Hỗ trợ chiến dịch social media và nội dung thương hiệu.',
            ]],
            'skills' => [
                ['skill_name' => 'Excel', 'description' => ''],
                ['skill_name' => 'Canva', 'description' => ''],
                ['skill_name' => 'Tiếng Anh', 'description' => 'Giao tiếp tốt'],
            ],
            'projects' => [],
            'activities' => [],
            'certificates' => [],
            'awards' => [],
            'references' => [],
        ];
    }
}

if (!function_exists('cv_template_render_card_preview_html')) {
    function cv_template_render_card_preview_html(string $templateKey): string
    {
        $templateKey = cv_normalize_template_key($templateKey);
        $sample = cv_template_sample_preview_data($templateKey);

        return $templateKey === 'modern'
            ? cv_render_preview_modern_html($sample)
            : cv_render_preview_classic_html($sample);
    }
}

if (!function_exists('cv_template_builder_url')) {
    /**
     * @param array<string, scalar|null> $query
     */
    function cv_template_builder_url(string $templateKey, array $query = []): string
    {
        $query = array_merge(['template' => cv_normalize_template_key($templateKey)], $query);

        return cv_template_page_url('cv-builder.php', $query);
    }
}
