-- Phase 1.1 — Cập nhật bảng locations (36 dòng)
-- Khuyến nghị Windows/XAMPP: php docs/migrations/run-phase-1-1-locations.php
-- (tránh lỗi encoding khi pipe file .sql). File SQL này dùng cho Linux/phpMyAdmin import UTF-8.
-- Chạy SAU KHI backup. Thứ tự: rename cũ → insert mới.

-- 1) Đổi tên 5 bản ghi cũ (giữ location_id cho jobs hiện có)
UPDATE `locations` SET `name` = 'TP. Hà Nội' WHERE `name` = 'Hà Nội';
UPDATE `locations` SET `name` = 'TP. Hồ Chí Minh' WHERE `name` = 'Hồ Chí Minh';
UPDATE `locations` SET `name` = 'TP. Đà Nẵng' WHERE `name` = 'Đà Nẵng';
UPDATE `locations` SET `name` = 'TP. Cần Thơ' WHERE `name` = 'Cần Thơ';
UPDATE `locations` SET `name` = 'Remote' WHERE `name` = 'Toàn Quốc';

-- 2) Thêm các đơn vị còn thiếu (chỉ insert nếu chưa có tên)
INSERT INTO `locations` (`name`)
SELECT * FROM (
  SELECT 'Tuyên Quang' AS name UNION ALL
  SELECT 'Cao Bằng' UNION ALL
  SELECT 'Lào Cai' UNION ALL
  SELECT 'Lai Châu' UNION ALL
  SELECT 'Điện Biên' UNION ALL
  SELECT 'Sơn La' UNION ALL
  SELECT 'Lạng Sơn' UNION ALL
  SELECT 'Thái Nguyên' UNION ALL
  SELECT 'Phú Thọ' UNION ALL
  SELECT 'Bắc Ninh' UNION ALL
  SELECT 'Quảng Ninh' UNION ALL
  SELECT 'Hưng Yên' UNION ALL
  SELECT 'Ninh Bình' UNION ALL
  SELECT 'Thanh Hóa' UNION ALL
  SELECT 'Nghệ An' UNION ALL
  SELECT 'Hà Tĩnh' UNION ALL
  SELECT 'Quảng Trị' UNION ALL
  SELECT 'Quảng Ngãi' UNION ALL
  SELECT 'Gia Lai' UNION ALL
  SELECT 'Đắk Lắk' UNION ALL
  SELECT 'Khánh Hòa' UNION ALL
  SELECT 'Lâm Đồng' UNION ALL
  SELECT 'Đồng Nai' UNION ALL
  SELECT 'Tây Ninh' UNION ALL
  SELECT 'Đồng Tháp' UNION ALL
  SELECT 'Vĩnh Long' UNION ALL
  SELECT 'Cà Mau' UNION ALL
  SELECT 'An Giang' UNION ALL
  SELECT 'TP. Huế' UNION ALL
  SELECT 'TP. Hải Phòng' UNION ALL
  SELECT 'Khác'
) AS new_rows
WHERE NOT EXISTS (SELECT 1 FROM `locations` l WHERE l.name = new_rows.name);
