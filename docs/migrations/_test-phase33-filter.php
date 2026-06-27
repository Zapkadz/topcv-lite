<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/services/JobRecommendationService.php';

$ref = new ReflectionClass(JobRecommendationService::class);
$method = $ref->getMethod('filterEligibleTopJobs');
$method->setAccessible(true);

$top = [
    ['job_id' => 1, 'job_quality' => ['quality_label' => 'eligible']],
    ['job_id' => 2, 'job_quality' => ['quality_label' => 'insufficient_jd_data']],
    ['job_id' => 3, 'job_quality' => ['flags' => ['placeholder_title']]],
    ['job_id' => 4, 'job_quality' => ['quality_label' => 'eligible']],
];
$excluded = [['job_id' => 4]];

$out = $method->invoke(null, $top, $excluded);
$ids = array_map(static fn(array $row): int => (int) $row['job_id'], $out);

echo 'filtered_ids=' . implode(',', $ids) . PHP_EOL;
echo 'pass=' . ($ids === [1] ? 'yes' : 'no') . PHP_EOL;

exit($ids === [1] ? 0 : 1);
