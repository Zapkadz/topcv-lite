-- Phase 2A — Tách account_status và employer_approval_status
-- Chạy TOÀN BỘ file một lần (phpMyAdmin → SQL → Go).
-- Nếu lỗi #1054 Unknown column 'status' → dùng phase-2a-user-status-repair.sql

-- Bước 1: Thêm cột mới (bỏ qua nếu phpMyAdmin báo cột đã tồn tại)
ALTER TABLE `users`
  ADD COLUMN `account_status` enum('active','suspended','pending_verification') NOT NULL DEFAULT 'active' AFTER `role`,
  ADD COLUMN `employer_approval_status` enum('pending','approved','rejected') NULL DEFAULT NULL AFTER `account_status`;

-- Bước 2: Copy dữ liệu từ cột status cũ (CHỈ khi cột status còn tồn tại)
UPDATE `users` SET
  `account_status` = IF(`status` = 0, 'suspended', 'active'),
  `employer_approval_status` = NULL
WHERE `role` IN ('candidate', 'admin');

UPDATE `users` SET
  `account_status` = 'active',
  `employer_approval_status` = IF(`status` = 0, 'pending', 'approved')
WHERE `role` = 'employer';

-- Bước 3: Xóa cột cũ
ALTER TABLE `users` DROP COLUMN `status`;
