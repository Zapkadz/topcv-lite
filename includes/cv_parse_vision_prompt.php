<?php

require_once __DIR__ . '/cv_import_rules.php';

if (!function_exists('cv_parse_build_vision_system_prompt')) {
    function cv_parse_build_vision_system_prompt(): string
    {
        return 'Bạn là chuyên gia trích xuất CV tiếng Việt/Anh từ file PDF (digital hoặc scan). '
            . 'Bạn nhìn được layout: cột trái/phải, sidebar liên hệ, header, icon, bảng. '
            . 'Đọc theo thứ tự con người (thường trên xuống, cột trái trước nếu có 2 cột). '
            . 'Ghép mỗi khoảng thời gian với đúng trường học / công ty / dự án / tổ chức gần nhất về mặt thị giác. '
            . 'KHÔNG bịa dữ liệu. Field không thấy → chuỗi rỗng "". '
            . 'Chỉ trả JSON object hợp lệ, KHÔNG markdown, KHÔNG giải thích.';
    }
}

if (!function_exists('cv_parse_build_vision_user_prompt')) {
    function cv_parse_build_vision_user_prompt(string $supplementaryText = ''): string
    {
        $schema = cv_parse_get_schema_array();
        $schemaJson = json_encode($schema, JSON_UNESCAPED_UNICODE);

        $prompt = 'Trích xuất toàn bộ CV từ file PDF đính kèm theo schema JSON:' . "\n"
            . $schemaJson . "\n\n"
            . cv_parse_build_extraction_rules();

        if (trim($supplementaryText) !== '') {
            $text = cv_import_truncate_text($supplementaryText, 4000);
            $prompt .= "\n\n" . 'TEXT phụ trích máy local (có thể nhiễu, chỉ tham khảo — ưu tiên PDF/visual):' . "\n"
                . $text;
        }

        return $prompt;
    }
}

if (!function_exists('cv_parse_get_schema_array')) {
    /**
     * @return array<string, mixed>
     */
    function cv_parse_get_schema_array(): array
    {
        return [
            'title' => 'string',
            'full_name' => 'string',
            'target_position' => 'string',
            'date_of_birth' => 'string (YYYY-MM-DD hoặc rỗng)',
            'gender' => 'string (Nam|Nữ|Khác|rỗng)',
            'phone' => 'string',
            'email' => 'string',
            'website' => 'string',
            'address' => 'string',
            'career_objective' => 'string',
            'interests' => 'string',
            'educations' => [
                [
                    'start_date' => 'YYYY-MM hoặc rỗng',
                    'end_date' => 'YYYY-MM hoặc rỗng',
                    'school_name' => 'string',
                    'major' => 'string',
                    'description' => 'string',
                ],
            ],
            'experiences' => [
                [
                    'start_date' => 'YYYY-MM hoặc rỗng',
                    'end_date' => 'YYYY-MM hoặc rỗng',
                    'company_name' => 'string',
                    'position' => 'string',
                    'description' => 'string',
                ],
            ],
            'skills' => [
                ['skill_name' => 'string', 'description' => 'string'],
            ],
            'projects' => [
                [
                    'start_date' => 'YYYY-MM hoặc rỗng',
                    'end_date' => 'YYYY-MM hoặc rỗng',
                    'project_name' => 'string',
                    'position' => 'string',
                    'description' => 'string',
                ],
            ],
            'activities' => [
                [
                    'start_date' => 'YYYY-MM hoặc rỗng',
                    'end_date' => 'YYYY-MM hoặc rỗng',
                    'organization' => 'string',
                    'role' => 'string',
                    'description' => 'string',
                ],
            ],
            'certificates' => [
                ['issued_at' => 'YYYY-MM hoặc rỗng', 'certificate_name' => 'string'],
            ],
            'awards' => [
                [
                    'awarded_at' => 'YYYY-MM hoặc rỗng',
                    'title' => 'string',
                    'description' => 'string',
                ],
            ],
            'references' => [
                [
                    'full_name' => 'string',
                    'position' => 'string',
                    'contact_info' => 'string',
                ],
            ],
        ];
    }
}

if (!function_exists('cv_parse_build_extraction_rules')) {
    function cv_parse_build_extraction_rules(): string
    {
        return 'Quy tắc chung:' . "\n"
            . "- Không bịa. Chỉ lấy từ CV." . "\n"
            . "- Field không có → chuỗi rỗng ''." . "\n"
            . "- Bỏ qua chữ lặp liên tiếp." . "\n"
            . "- Ngày chuẩn hóa YYYY-MM; chỉ có năm → YYYY-01; đang học/làm → end_date = ''." . "\n"
            . "- Giữ ngôn ngữ gốc trong CV." . "\n\n"
            . 'Quy tắc BẮT BUỘC cho educations / experiences / projects / activities:' . "\n"
            . "- Mỗi phần tử = MỘT mục hoàn chỉnh; thời gian phải kèm trường/công ty/dự án/tổ chức." . "\n"
            . "- KHÔNG tạo mục chỉ có ngày mà thiếu tên trường/công ty/dự án/tổ chức." . "\n"
            . "- description chỉ thuộc đúng mục; không copy sang mục khác." . "\n\n"
            . 'Quy tắc skills: mỗi kỹ năng một phần tử; skill_name bắt buộc có chữ.' . "\n\n"
            . 'Trả về JSON object hợp lệ, KHÔNG markdown.';
    }
}
