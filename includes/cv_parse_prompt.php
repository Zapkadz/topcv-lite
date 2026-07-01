<?php

/**
 * Prompt builder cho CV import (Mức B).
 * AI output bắt buộc là JSON object (không markdown).
 */

if (!function_exists('cv_parse_build_system_prompt')) {
    function cv_parse_build_system_prompt(): string
    {
        return 'Bạn là trợ lý trích xuất dữ liệu từ CV tiếng Việt/Anh. '
            . 'Đầu vào là TEXT thô trích từ PDF (pdfparser): có thể lặp chữ, thứ tự lộn, ngày tháng tách rời nội dung, '
            . 'nhiều cột bị dồn thành một dòng. '
            . 'Nhiệm vụ: suy luận CV thật, khử trùng lặp, ghép đúng thời gian với trường/công ty/vị trí tương ứng, '
            . 'rồi map sang JSON schema. '
            . 'QUAN TRỌNG: Chỉ trả về JSON object hợp lệ, KHÔNG markdown, KHÔNG giải thích.';
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

        return 'Trích xuất CV theo schema JSON sau từ TEXT thô (đã làm sạch sơ bộ):' . "\n"
            . $schemaJson . "\n\n"
            . 'Quy tắc chung:' . "\n"
            . "- Không bịa. Chỉ lấy từ text." . "\n"
            . "- Field không có → chuỗi rỗng ''." . "\n"
            . "- Bỏ qua chữ lặp liên tiếp (email×N, tên×N, ngày×N)." . "\n"
            . "- Dùng tiêu đề section (HỌC VẤN, KINH NGHIỆM, KỸ NĂNG, LIÊN HỆ...) để gom đúng nhóm." . "\n"
            . "- Ngày chuẩn hóa YYYY-MM; chỉ có năm → YYYY-01; đang học/làm → end_date = ''." . "\n"
            . "- Giữ ngôn ngữ gốc trong CV." . "\n\n"
            . 'Quy tắc BẮT BUỘC cho educations / experiences / projects / activities:' . "\n"
            . "- Mỗi phần tử mảng = MỘT mục CV hoàn chỉnh: thời gian PHẢI đi kèm nội dung (trường/công ty/tổ chức/tên dự án)." . "\n"
            . "- TUYỆT ĐỐI KHÔNG tạo mục chỉ có start_date/end_date mà thiếu school_name hoặc company_name hoặc project_name hoặc organization." . "\n"
            . "- Nếu trong text có khoảng thời gian nhưng không đủ thông tin để ghép trường/công ty gần đó → BỎ QUA mục đó (không thêm vào mảng)." . "\n"
            . "- Ghép ngữ nghĩa: tìm cụm [thời gian + tên trường/công ty + ngành/vị trí + mô tả] gần nhau trong text, kể cả khi bị xen kẽ do PDF lộn." . "\n"
            . "- description chỉ thuộc đúng mục đó; không copy mô tả mục A sang mục B." . "\n"
            . "- Không gộp hai trường/công ty khác nhau thành một mục." . "\n"
            . "- Ví dụ hợp lệ: start=2008-12, end=2012-12, school=Đại học Hoa Sen, major=Thiết kế Sản phẩm, description=Theo học các khóa..." . "\n"
            . "- Ví dụ KHÔNG hợp lệ (không xuất): start=2028-01, end=2028-01, school='', major='', description='-'." . "\n\n"
            . 'Quy tắc skills:' . "\n"
            . "- Trích xuất TẤT CẢ kỹ năng có trong section KỸ NĂNG / SKILLS (không giới hạn số lượng)." . "\n"
            . "- Mỗi kỹ năng một phần tử; skill_name bắt buộc có chữ." . "\n\n"
            . 'Trả về JSON object hợp lệ.' . "\n\n"
            . 'TEXT CV START' . "\n"
            . $text
            . "\nTEXT CV END";
    }
}

