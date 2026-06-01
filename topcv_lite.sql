-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th1 01, 2026 lúc 03:00 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `topcv_lite`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `cv_profile_id` int(11) DEFAULT NULL,
  `cv_snapshot` varchar(255) DEFAULT NULL,
  `cv_snapshot_json` longtext DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `status` enum('pending','viewed','interview','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `applications`
--

INSERT INTO `applications` (`id`, `job_id`, `candidate_id`, `cv_snapshot`, `cover_letter`, `status`, `created_at`) VALUES
(2, 1, 1, 'uploads/cv/cv_apply_1_1_1767265051.pdf', 'tôi muốn ứng tuyển', 'viewed', '2026-01-01 10:57:31'),
(3, 3, 1, 'uploads/cv/cv_apply_1_3_1767266370.pdf', 'a', 'pending', '2026-01-01 11:19:30');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `candidates`
--

CREATE TABLE `candidates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `cv_path` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `candidates`
--

INSERT INTO `candidates` (`id`, `user_id`, `title`, `cv_path`, `bio`, `updated_at`) VALUES
(1, 2, 'IT DEV', 'uploads/cv/cv_base_2_1767263631.pdf', 'học it xuất sắc', '2026-01-01 10:33:51');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'IT Phần mềm'),
(2, 'Marketing'),
(3, 'Kế toán'),
(4, 'Bán hàng'),
(5, 'Nhân sự');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `companies`
--

INSERT INTO `companies` (`id`, `user_id`, `name`, `logo`, `website`, `address`, `description`, `created_at`) VALUES
(1, 3, 'Công Ty FPT ', 'uploads/logos/company_3_1767264779.png', NULL, 'Số 1 Phạm Văn Bạch', 'Công ty hàng đầu về IT', '2026-01-01 10:44:35');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `salary_range` varchar(50) DEFAULT NULL,
  `description` text NOT NULL,
  `requirements` text DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `status` enum('pending','approved','rejected','hidden') DEFAULT 'pending',
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `job_type` varchar(50) DEFAULT 'Toàn thời gian',
  `job_level` varchar(50) DEFAULT 'Nhân viên',
  `experience` varchar(50) DEFAULT 'Không yêu cầu',
  `gender` varchar(20) DEFAULT 'Không yêu cầu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `jobs`
--

INSERT INTO `jobs` (`id`, `company_id`, `category_id`, `location_id`, `title`, `salary_range`, `description`, `requirements`, `benefits`, `deadline`, `status`, `view_count`, `created_at`, `updated_at`, `admin_note`, `quantity`, `job_type`, `job_level`, `experience`, `gender`) VALUES
(1, 1, 1, 1, 'Tuyển dụng intern IT DEV Front-end', '3 - 10tr', 'Học hỏi - nghe theo sắp xếp công việc của quản lý', 'Đã tốt nghiệp', 'Ăn trưa \r\nThưởng lễ', '2026-01-15', 'approved', 11, '2026-01-01 10:47:46', NULL, NULL, 1, 'Toàn thời gian', 'Nhân viên', 'Không yêu cầu', 'Không yêu cầu'),
(2, 1, 1, 1, 'test', '10 - 20', 'a', 'a', 'a', '2026-02-04', 'rejected', 0, '2026-01-01 10:49:49', NULL, 'như l', 1, 'Toàn thời gian', 'Nhân viên', 'Không yêu cầu', 'Không yêu cầu'),
(3, 1, 1, 1, 'Fresher DEV Back-end', 'Thỏa Thuận', 'Làm các job được giao', '3 năm kinh nghiệm', 'Đủ phúc lợi', '2026-01-08', 'approved', 8, '2026-01-01 11:18:06', '2026-01-01 11:38:22', NULL, 5, 'Toàn thời gian', 'Nhân viên', 'Không yêu cầu', 'Không yêu cầu');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `locations`
--

INSERT INTO `locations` (`id`, `name`) VALUES
(1, 'TP. Hà Nội'),
(2, 'TP. Hồ Chí Minh'),
(3, 'TP. Đà Nẵng'),
(4, 'TP. Cần Thơ'),
(5, 'Remote'),
(6, 'Tuyên Quang'),
(7, 'Cao Bằng'),
(8, 'Lào Cai'),
(9, 'Lai Châu'),
(10, 'Điện Biên'),
(11, 'Sơn La'),
(12, 'Lạng Sơn'),
(13, 'Thái Nguyên'),
(14, 'Phú Thọ'),
(15, 'Bắc Ninh'),
(16, 'Quảng Ninh'),
(17, 'Hưng Yên'),
(18, 'Ninh Bình'),
(19, 'Thanh Hóa'),
(20, 'Nghệ An'),
(21, 'Hà Tĩnh'),
(22, 'Quảng Trị'),
(23, 'Quảng Ngãi'),
(24, 'Gia Lai'),
(25, 'Đắk Lắk'),
(26, 'Khánh Hòa'),
(27, 'Lâm Đồng'),
(28, 'Đồng Nai'),
(29, 'Tây Ninh'),
(30, 'Đồng Tháp'),
(31, 'Vĩnh Long'),
(32, 'Cà Mau'),
(33, 'An Giang'),
(34, 'TP. Huế'),
(35, 'TP. Hải Phòng'),
(36, 'Khác');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('candidate','employer','admin') NOT NULL DEFAULT 'candidate',
  `account_status` enum('active','suspended','pending_verification') NOT NULL DEFAULT 'active',
  `employer_approval_status` enum('pending','approved','rejected') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `phone`, `role`, `account_status`, `employer_approval_status`, `created_at`) VALUES
(1, 'Super Admin', 'admin@topcv.local', '$2y$10$Nj.kTYt9FcyGlYwdW00t8OjEsUic1ebYNsc8Zy7zZM8MV4//7lkUu', NULL, 'admin', 'active', NULL, '2026-01-01 10:05:27'),
(2, 'Văn Minh Thành', 'thanh@gmail.com', '$2y$10$Nj.kTYt9FcyGlYwdW00t8OjEsUic1ebYNsc8Zy7zZM8MV4//7lkUu', NULL, 'candidate', 'active', NULL, '2026-01-01 10:26:49'),
(3, 'Trần Thị Huyền', 'tranhuyen@gmail.com', '$2y$10$Nj.kTYt9FcyGlYwdW00t8OjEsUic1ebYNsc8Zy7zZM8MV4//7lkUu', NULL, 'employer', 'active', 'approved', '2026-01-01 10:27:18'),
(4, 'trần trí huy', 'huytran@gmail.com', '$2y$10$YolsAh7720319j5VYbc3y.AdYkIye94rg9fg5gPwuAuPLOjla2QSC', NULL, 'employer', 'active', 'approved', '2026-01-01 13:54:03');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cv_profiles` (Phase CV-A)
--

CREATE TABLE `cv_profiles` (
  `id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL DEFAULT '',
  `target_position` varchar(255) NOT NULL DEFAULT '',
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `phone` varchar(10) DEFAULT NULL COMMENT 'VN mobile: 0xxxxxxxxx',
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `career_objective` text DEFAULT NULL,
  `interests` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `template_key` varchar(32) NOT NULL DEFAULT 'classic',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `completion_percent` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `cv_educations` (
  `id` int(11) NOT NULL,
  `cv_id` int(11) NOT NULL,
  `start_date` char(7) DEFAULT NULL COMMENT 'YYYY-MM',
  `end_date` char(7) DEFAULT NULL COMMENT 'YYYY-MM',
  `school_name` varchar(255) NOT NULL DEFAULT '',
  `major` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `cv_experiences` (
  `id` int(11) NOT NULL,
  `cv_id` int(11) NOT NULL,
  `start_date` char(7) DEFAULT NULL COMMENT 'YYYY-MM',
  `end_date` char(7) DEFAULT NULL COMMENT 'YYYY-MM',
  `company_name` varchar(255) NOT NULL DEFAULT '',
  `position` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `cv_skills` (
  `id` int(11) NOT NULL,
  `cv_id` int(11) NOT NULL,
  `skill_name` varchar(255) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `saved_jobs` (Phase 2D)
--

CREATE TABLE `saved_jobs` (
  `id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `moderation_logs` (Phase 2C)
--

CREATE TABLE `moderation_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `entity_type` enum('job','employer') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `action` enum('approve','reject') NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `candidate_id` (`candidate_id`),
  ADD KEY `idx_applications_cv_profile` (`cv_profile_id`),
  ADD UNIQUE KEY `uniq_job_candidate` (`job_id`,`candidate_id`);

--
-- Chỉ mục cho bảng `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Chỉ mục cho bảng `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `cv_profiles`
--
ALTER TABLE `cv_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cv_profiles_candidate` (`candidate_id`),
  ADD KEY `idx_cv_profiles_primary` (`candidate_id`,`is_primary`);

ALTER TABLE `cv_educations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cv_educations_cv` (`cv_id`,`sort_order`);

ALTER TABLE `cv_experiences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cv_experiences_cv` (`cv_id`,`sort_order`);

ALTER TABLE `cv_skills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cv_skills_cv` (`cv_id`,`sort_order`);

--
-- Chỉ mục cho bảng `saved_jobs`
--
ALTER TABLE `saved_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_saved_job` (`candidate_id`,`job_id`),
  ADD KEY `idx_saved_candidate` (`candidate_id`),
  ADD KEY `idx_saved_job` (`job_id`);

--
-- Chỉ mục cho bảng `moderation_logs`
--
ALTER TABLE `moderation_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_moderation_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_moderation_created` (`created_at`),
  ADD KEY `idx_moderation_admin` (`admin_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `candidates`
--
ALTER TABLE `candidates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `moderation_logs`
--
ALTER TABLE `moderation_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `saved_jobs`
--
ALTER TABLE `saved_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `cv_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `cv_educations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `cv_experiences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `cv_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_cv_profile_fk` FOREIGN KEY (`cv_profile_id`) REFERENCES `cv_profiles` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `candidates`
--
ALTER TABLE `candidates`
  ADD CONSTRAINT `candidates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `companies`
--
ALTER TABLE `companies`
  ADD CONSTRAINT `companies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jobs_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `jobs_ibfk_3` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`);

--
-- Các ràng buộc cho bảng `moderation_logs`
--
ALTER TABLE `moderation_logs`
  ADD CONSTRAINT `moderation_logs_admin_fk` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `saved_jobs`
--
ALTER TABLE `saved_jobs`
  ADD CONSTRAINT `saved_jobs_candidate_fk` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_jobs_job_fk` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

ALTER TABLE `cv_profiles`
  ADD CONSTRAINT `cv_profiles_candidate_fk` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE;

ALTER TABLE `cv_educations`
  ADD CONSTRAINT `cv_educations_cv_fk` FOREIGN KEY (`cv_id`) REFERENCES `cv_profiles` (`id`) ON DELETE CASCADE;

ALTER TABLE `cv_experiences`
  ADD CONSTRAINT `cv_experiences_cv_fk` FOREIGN KEY (`cv_id`) REFERENCES `cv_profiles` (`id`) ON DELETE CASCADE;

ALTER TABLE `cv_skills`
  ADD CONSTRAINT `cv_skills_cv_fk` FOREIGN KEY (`cv_id`) REFERENCES `cv_profiles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
