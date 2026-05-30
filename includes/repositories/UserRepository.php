<?php
/**
 * Truy vấn bảng users — không redirect / session.
 */
declare(strict_types=1);

require_once __DIR__ . '/../user_status.php';

class UserRepository
{
    public static function findByEmail(PDO $conn, string $email): ?array
    {
        $stmt = $conn->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([trim($email)]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function findById(PDO $conn, int $id): ?array
    {
        $stmt = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function emailExists(PDO $conn, string $email): bool
    {
        return self::findByEmail($conn, $email) !== null;
    }

    public static function create(
        PDO $conn,
        string $fullname,
        string $email,
        string $hashedPassword,
        string $role,
        string $accountStatus = 'active',
        ?string $employerApprovalStatus = null
    ): int {
        $stmt = $conn->prepare(
            'INSERT INTO users (fullname, email, password, role, account_status, employer_approval_status)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $fullname,
            $email,
            $hashedPassword,
            $role,
            $accountStatus,
            $employerApprovalStatus,
        ]);

        return (int) $conn->lastInsertId();
    }

    public static function setEmployerApproval(PDO $conn, int $userId, string $approvalStatus): bool
    {
        if (!in_array($approvalStatus, user_employer_approval_statuses(), true)) {
            return false;
        }

        $stmt = $conn->prepare(
            "UPDATE users SET employer_approval_status = ?
             WHERE id = ? AND role = 'employer'"
        );

        return $stmt->execute([$approvalStatus, $userId]);
    }

    public static function listPendingEmployers(PDO $conn): array
    {
        $stmt = $conn->query(
            "SELECT * FROM users
             WHERE role = 'employer' AND employer_approval_status = 'pending'
             ORDER BY created_at DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function listAllExceptPendingEmployers(PDO $conn): array
    {
        $stmt = $conn->query(
            "SELECT * FROM users
             WHERE NOT (role = 'employer' AND employer_approval_status = 'pending')
             ORDER BY id DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
