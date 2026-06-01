-- Phase CV-B — Chuẩn hóa định dạng dữ liệu CV
-- Chạy qua migrate-phase-cv-b-formats.php (sau CV-A)

-- 1) Chuẩn hóa dữ liệu cũ (năm 4 chữ số → YYYY-01)
UPDATE `cv_educations` SET `start_date` = CONCAT(`start_date`, '-01') WHERE `start_date` REGEXP '^[0-9]{4}$';
UPDATE `cv_educations` SET `end_date` = CONCAT(`end_date`, '-01') WHERE `end_date` REGEXP '^[0-9]{4}$';
UPDATE `cv_experiences` SET `start_date` = CONCAT(`start_date`, '-01') WHERE `start_date` REGEXP '^[0-9]{4}$';
UPDATE `cv_experiences` SET `end_date` = CONCAT(`end_date`, '-01') WHERE `end_date` REGEXP '^[0-9]{4}$';

-- 2) Xóa giá trị không đúng chuẩn YYYY-MM
UPDATE `cv_educations` SET `start_date` = NULL WHERE `start_date` IS NOT NULL AND `start_date` NOT REGEXP '^[0-9]{4}-[0-9]{2}$';
UPDATE `cv_educations` SET `end_date` = NULL WHERE `end_date` IS NOT NULL AND `end_date` NOT REGEXP '^[0-9]{4}-[0-9]{2}$';
UPDATE `cv_experiences` SET `start_date` = NULL WHERE `start_date` IS NOT NULL AND `start_date` NOT REGEXP '^[0-9]{4}-[0-9]{2}$';
UPDATE `cv_experiences` SET `end_date` = NULL WHERE `end_date` IS NOT NULL AND `end_date` NOT REGEXP '^[0-9]{4}-[0-9]{2}$';

-- 3) SĐT không đúng 10 chữ số (0xxxxxxxxx) → NULL (cần user sửa lại trên form)
UPDATE `cv_profiles` SET `phone` = NULL
  WHERE `phone` IS NOT NULL AND `phone` NOT REGEXP '^0[0-9]{9}$';

-- 4) Thu hẹp kiểu cột (ràng buộc lưu trữ)
ALTER TABLE `cv_profiles`
  MODIFY `phone` varchar(10) DEFAULT NULL COMMENT 'VN mobile: 0xxxxxxxxx (10 digits)';

ALTER TABLE `cv_educations`
  MODIFY `start_date` char(7) DEFAULT NULL COMMENT 'ISO year-month YYYY-MM',
  MODIFY `end_date` char(7) DEFAULT NULL COMMENT 'ISO year-month YYYY-MM';

ALTER TABLE `cv_experiences`
  MODIFY `start_date` char(7) DEFAULT NULL COMMENT 'ISO year-month YYYY-MM',
  MODIFY `end_date` char(7) DEFAULT NULL COMMENT 'ISO year-month YYYY-MM';
