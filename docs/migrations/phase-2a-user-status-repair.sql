-- Phase 2A REPAIR — Chạy khi đã có account_status + employer_approval_status
-- nhưng cột `status` cũ đã bị DROP (lỗi #1054 Unknown column 'status').
-- Không dùng cột status — gán giá trị mặc định an toàn cho DB dev.

UPDATE `users` SET
  `account_status` = 'active',
  `employer_approval_status` = NULL
WHERE `role` IN ('candidate', 'admin');

UPDATE `users` SET
  `account_status` = 'active',
  `employer_approval_status` = 'approved'
WHERE `role` = 'employer'
  AND (`employer_approval_status` IS NULL OR `employer_approval_status` = '');

-- Nếu muốn 1 employer test ở trạng thái chờ duyệt, sửa id/email tương ứng:
-- UPDATE `users` SET `employer_approval_status` = 'pending' WHERE `email` = 'email-ntd-moi@example.com';
