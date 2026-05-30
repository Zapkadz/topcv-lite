<?php
/**
 * Nghiệp vụ đăng ký, duyệt employer, kiểm tra quyền đăng nhập (Phase 2A).
 */
declare(strict_types=1);

require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../user_status.php';

class UserModerationService
{
    /**
     * @return array{ok: bool, message: string, user_id: int|null}
     */
    public static function register(
        PDO $conn,
        string $fullname,
        string $email,
        string $password,
        string $role
    ): array {
        $role = trim($role);
        if (!in_array($role, ['candidate', 'employer'], true)) {
            return ['ok' => false, 'message' => 'Vai trò đăng ký không hợp lệ.', 'user_id' => null];
        }

        if (UserRepository::emailExists($conn, $email)) {
            return ['ok' => false, 'message' => 'Email này đã được sử dụng. Vui lòng chọn email khác!', 'user_id' => null];
        }

        $employerApproval = null;
        if ($role === 'employer') {
            $employerApproval = 'pending';
        }

        $userId = UserRepository::create(
            $conn,
            $fullname,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $role,
            'active',
            $employerApproval
        );

        $message = $role === 'employer'
            ? 'Đăng ký thành công! Tài khoản Nhà tuyển dụng cần chờ Admin duyệt.'
            : 'Đăng ký thành công! Bạn có thể đăng nhập ngay.';

        return ['ok' => true, 'message' => $message, 'user_id' => $userId];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public static function validateLoginAccess(array $user): array
    {
        if (!user_is_account_active($user)) {
            return [
                'ok' => false,
                'message' => 'Tài khoản đã bị khóa hoặc chưa được kích hoạt. Vui lòng liên hệ quản trị viên.',
            ];
        }

        if (($user['role'] ?? '') === 'employer' && ($user['employer_approval_status'] ?? '') === 'rejected') {
            return [
                'ok' => false,
                'message' => 'Tài khoản Nhà tuyển dụng đã bị từ chối. Vui lòng liên hệ quản trị viên.',
            ];
        }

        return ['ok' => true, 'message' => ''];
    }

    /**
     * Thông báo sau khi login thành công (employer chờ duyệt vẫn login được).
     */
    public static function loginNoticeForUser(array $user): ?string
    {
        if (($user['role'] ?? '') !== 'employer') {
            return null;
        }

        if (($user['employer_approval_status'] ?? '') === 'pending') {
            return 'Tài khoản Nhà tuyển dụng đang chờ Admin phê duyệt. Bạn chưa thể đăng tin cho đến khi được duyệt.';
        }

        return null;
    }

    public static function approveEmployer(PDO $conn, int $userId): bool
    {
        return UserRepository::setEmployerApproval($conn, $userId, 'approved');
    }

    public static function rejectEmployer(PDO $conn, int $userId): bool
    {
        return UserRepository::setEmployerApproval($conn, $userId, 'rejected');
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public static function assertEmployerPanelAccess(PDO $conn, int $userId): array
    {
        $user = UserRepository::findById($conn, $userId);
        if (!$user || ($user['role'] ?? '') !== 'employer') {
            return ['ok' => false, 'message' => 'Bạn không có quyền truy cập khu vực Nhà tuyển dụng.'];
        }

        if (!user_is_account_active($user)) {
            return ['ok' => false, 'message' => 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.'];
        }

        if (($user['employer_approval_status'] ?? '') === 'pending') {
            return [
                'ok' => false,
                'message' => 'Tài khoản Nhà tuyển dụng đang chờ Admin phê duyệt. Bạn chưa thể sử dụng khu vực quản lý tin.',
            ];
        }

        if (($user['employer_approval_status'] ?? '') === 'rejected') {
            return [
                'ok' => false,
                'message' => 'Tài khoản Nhà tuyển dụng đã bị từ chối. Vui lòng liên hệ quản trị viên.',
            ];
        }

        return ['ok' => true, 'message' => ''];
    }
}
