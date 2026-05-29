# Tổng quan database

Database hiện tại gồm 7 bảng chính: `users`, `candidates`, `companies`, `jobs`, `applications`, `categories`, `locations`.  
Mức độ: phù hợp MVP nhưng chưa đủ chuẩn production cho job platform vận hành thật.

# Phân tích từng bảng

## `users`
- Vai trò: identity + role + trạng thái duyệt.
- Vấn đề:
  - `status` overload (vừa active/inactive, vừa pending employer).
  - Không có `last_login`, `email_verified_at`, `deleted_at`, `failed_login_count`.
- Mức độ: High.
- Ảnh hưởng: khó audit bảo mật, khó khóa mềm, khó chống account abuse.

## `candidates`
- Vai trò: hồ sơ ứng viên rút gọn.
- Vấn đề:
  - Chưa enforce unique `user_id` (một user có thể có nhiều hồ sơ nếu lỗi race condition).
  - Thiếu kỹ năng, kinh nghiệm, education, expected salary, location preference.
- Mức độ: High.
- Ảnh hưởng: không đủ dữ liệu để matching/recommendation thật.

## `companies`
- Vai trò: hồ sơ doanh nghiệp.
- Vấn đề:
  - Schema thiếu trường business-critical (phone/email/scale/tax_code/verification_status).
  - Code đang đọc các field không tồn tại (`phone`, `email`, `scale`) -> lỗi vận hành tiềm ẩn.
- Mức độ: Critical.
- Ảnh hưởng: profile không nhất quán, trust/screening nhà tuyển dụng yếu.

## `jobs`
- Vai trò: tin tuyển dụng.
- Vấn đề:
  - Một số trường đang free-text (`salary_range`, `experience`, `job_type`) gây khó filter chuẩn.
  - Thiếu `published_at`, `closed_at`, `is_featured`, `deleted_at`, `moderation_history`.
  - Index chưa tối ưu cho search/filter multi-condition.
- Mức độ: High.
- Ảnh hưởng: search chậm khi scale, khó phân tích funnel tuyển dụng.

## `applications`
- Vai trò: đơn ứng tuyển.
- Vấn đề:
  - Thiếu unique constraint `(job_id, candidate_id)` => chống duplicate chỉ ở app layer.
  - Không có cột chống race (`version`/idempotency token).
  - Thiếu timeline/event history cho trạng thái.
- Mức độ: Critical.
- Ảnh hưởng: có thể phát sinh đơn trùng khi concurrent apply.

## `categories`, `locations`
- Vai trò: dictionary.
- Vấn đề:
  - Có thể bị xóa cứng dù đang được tham chiếu, thiếu chính sách quản trị chuẩn.
- Mức độ: Medium.
- Ảnh hưởng: mất tính toàn vẹn danh mục.

# Relationship audit

- FK hiện có nhưng thiếu unique key ở các quan hệ one-to-one logic (`candidates.user_id`, `companies.user_id`).
- Cascade delete đang bật rộng (`jobs` xóa kéo `applications`) có thể gây mất lịch sử nghiệp vụ.
- Orphan risk thấp ở mức FK, nhưng business orphan vẫn có (job hết hạn không có closed state rõ ràng).

# Performance audit

- Thiếu composite index quan trọng:
  - `jobs(status, deadline, created_at)`
  - `jobs(company_id, status, created_at)`
  - `applications(candidate_id, created_at)`
  - `applications(job_id, status, created_at)`
- Search hiện tại chủ yếu `%LIKE%` -> full scan khi dữ liệu lớn.
- Chưa có full-text search cho title/description.

# Scalability reality check

- 1 triệu user: query listing/admin dashboard sẽ chậm do thiếu index và pagination chiến lược.
- 100k CV upload: lưu local filesystem + tên file thủ công sẽ khó backup/versioning/CDN.
- Recruiter 1000 jobs: page quản lý sẽ nặng, không có archiving/caching.
- Spam apply: chưa có rate limiting/anti-bot -> DB phình nhanh.

# Security review

- Mật khẩu có hash (điểm tốt).
- Thiếu CSRF token cho hầu hết form POST.
- Upload chỉ check extension, chưa check MIME/signature + virus scan.
- Session không thấy hardening flags (secure, httponly, samesite) cấu hình tập trung.

# Business reality check

- Duplicate CV: có snapshot copy nhưng thiếu hash để dedupe.
- Fake jobs: có admin moderation nhưng thiếu trust score và workflow bằng chứng doanh nghiệp.
- Expired jobs: lọc ở query nhưng chưa có cơ chế archive/close automation.
- Blocked company/inactive account: phần employer có check status, nhưng các luồng khác chưa đồng bộ tuyệt đối.
- Saved jobs/recommendation history/AI scoring history: chưa có bảng hỗ trợ.
