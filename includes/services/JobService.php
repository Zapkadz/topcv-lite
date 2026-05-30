<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/JobRepository.php';
require_once __DIR__ . '/../job_rules.php';

class JobService
{
    /**
     * @return array{ok: bool, message: string}
     */
    public static function softDeleteForCompany(PDO $conn, int $jobId, int $companyId): array
    {
        $job = JobRepository::findByIdForCompany($conn, $jobId, $companyId);
        if (!$job) {
            return ['ok' => false, 'message' => 'Không tìm thấy tin hoặc bạn không có quyền.'];
        }
        if (job_is_soft_deleted($job)) {
            return ['ok' => false, 'message' => 'Tin đã được xóa trước đó.'];
        }

        if (!JobRepository::softDelete($conn, $jobId, $companyId)) {
            return ['ok' => false, 'message' => 'Không thể xóa tin.'];
        }

        return ['ok' => true, 'message' => 'Đã xóa tin tuyển dụng (có thể khôi phục trong mục Đã xóa).'];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public static function restoreForCompany(PDO $conn, int $jobId, int $companyId): array
    {
        $job = JobRepository::findByIdForCompany($conn, $jobId, $companyId);
        if (!$job || !job_is_soft_deleted($job)) {
            return ['ok' => false, 'message' => 'Tin không nằm trong thùng đã xóa.'];
        }

        if (!JobRepository::restore($conn, $jobId, $companyId)) {
            return ['ok' => false, 'message' => 'Không thể khôi phục tin.'];
        }

        return ['ok' => true, 'message' => 'Đã khôi phục tin tuyển dụng.'];
    }

    public static function assertEditableByCompany(PDO $conn, int $jobId, int $companyId): array
    {
        $job = JobRepository::findByIdForCompany($conn, $jobId, $companyId);
        if (!$job) {
            return ['ok' => false, 'message' => 'Tin không tồn tại hoặc bạn không có quyền sửa.'];
        }
        if (job_is_soft_deleted($job)) {
            return ['ok' => false, 'message' => 'Tin đã xóa — hãy khôi phục trước khi sửa.'];
        }

        return ['ok' => true, 'message' => ''];
    }
}
