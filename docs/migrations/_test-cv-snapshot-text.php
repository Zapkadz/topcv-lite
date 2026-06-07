<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/cv_snapshot_text.php';

$json = json_encode([
    'profile' => [
        'full_name' => 'Nguyen Van A',
        'target_position' => 'Backend Developer',
        'career_objective' => 'Backend developer with Java experience.',
    ],
    'skills' => [['skill_name' => 'Java'], ['skill_name' => 'Spring Boot']],
    'experiences' => [[
        'position' => 'Intern',
        'company_name' => 'ABC Tech',
        'start_date' => '2024-06',
        'end_date' => '2024-12',
        'description' => "Built REST APIs.\nDesigned MySQL schemas.",
    ]],
], JSON_UNESCAPED_UNICODE);

$text = cv_snapshot_text_from_json($json);
echo $text !== null && str_contains($text, 'Skills:') && str_contains($text, 'Java') ? "OK\n" : "FAIL\n";
echo "---\n" . ($text ?? 'null') . "\n";
