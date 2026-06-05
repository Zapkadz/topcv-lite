<?php

/**
 * Prompt builder cho CV import (Mức B).
 * AI output bắt buộc là JSON object (không markdown).
 */

if (!function_exists('cv_parse_build_system_prompt')) {
    function cv_parse_build_system_prompt(): string
    {
        return 'Bạn là trợ lý trích xuất dữ liệu từ CV. '
            . 'Hãy đọc nội dung CV (text) và trích xuất các trường theo schema bên dưới. '
            . 'QUAN TRỌNG: Chỉ trả về JSON object hợp lệ, KHÔNG trả về markdown, KHÔNG trả về văn bản giải thích.';
    }
}

if (!function_exists('cv_parse_build_user_prompt')) {
    function cv_parse_build_user_prompt(string $text): string
    {
        $schema = [
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
                [
                    'skill_name' => 'string',
                    'description' => 'string',
                ],
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
                [
                    'issued_at' => 'YYYY-MM hoặc rỗng',
                    'certificate_name' => 'string',
                ],
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

        $schemaJson = json_encode($schema, JSON_UNESCAPED_UNICODE);

        return 'Nội dung CV (text) dưới đây. Hãy trích xuất theo schema này: ' . "\n"
            . $schemaJson . "\n\n"
            . 'Quy tắc:' . "\n"
            . "- Nếu không tìm thấy trường nào thì dùng chuỗi rỗng ''.''." . "\n"
            . "- Không bịa. Chỉ lấy từ text." . "\n"
            . "- Giữ nguyên ngôn ngữ nội dung (Việt/Anh) như trong CV." . "\n"
            . "- Trả về JSON object hợp lệ.\n\n"
            . 'TEXT CV START' . "\n"
            . $text
            . "\nTEXT CV END";
    }
}

