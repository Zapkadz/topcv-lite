# Kiến trúc tổng thể

TopCV Lite hiện là ứng dụng monolith PHP thuần (server-rendered), không dùng framework MVC chuẩn, không có service layer riêng, mọi business logic phân tán trực tiếp trong file page controller (`*.php`).  
Hệ thống dùng session auth, PDO truy vấn trực tiếp, MariaDB/MySQL làm DB chính, upload file lưu local filesystem.

- Frontend architecture: server-side rendering bằng PHP + Bootstrap + JS thuần, không có SPA/state store.
- Backend architecture: page-based controller, include file chung (`config/db.php`, `includes/header.php`), chưa tách domain/service/repository.
- API structure: gần như không có API public; chỉ có endpoint nội bộ `admin/get-job-json.php` cho modal.
- State management: session PHP (`$_SESSION`) cho auth + flash message.
- Role system: `users.role` gồm `candidate/employer/admin`, kèm `users.status` dùng để duyệt employer.

# Luồng hoạt động hệ thống

1. Người dùng đăng ký (`register.php`) -> lưu `users`; employer mặc định `status=0`.
2. Admin duyệt employer ở `admin/users.php` -> `status=1`.
3. Employer tạo hồ sơ công ty (`employer/company.php`) -> đăng job (`job-create.php`) với trạng thái `pending`.
4. Admin duyệt/reject job (`admin/jobs.php`) -> job `approved` mới hiển thị public.
5. Candidate cập nhật hồ sơ/CV (`candidate/profile.php`) -> apply job (`apply.php`) tạo `applications`.
6. Employer xem và đổi trạng thái hồ sơ ứng viên (`employer/applicants.php`).

# Các module chính

- Public portal: `index.php`, `jobs.php`, `job-detail.php`, `companies.php`, `company-detail.php`.
- Auth/account: `login.php`, `register.php`, `logout.php`.
- Candidate: `candidate/profile.php`, `candidate/my-jobs.php`.
- Employer: dashboard, company profile, job create/edit/manage, applicants.
- Admin: dashboard, user moderation, job moderation, categories CRUD.
- Database core: `users`, `companies`, `jobs`, `applications`, `candidates`, `categories`, `locations`.

# Quan hệ giữa các module

- `users` là root identity.
- `companies.user_id -> users.id` (employer sở hữu công ty).
- `jobs.company_id -> companies.id` (công ty đăng nhiều job).
- `candidates.user_id -> users.id` (candidate có hồ sơ).
- `applications(job_id, candidate_id)` nối candidate với job.
- Admin thao tác trực tiếp trên `users/jobs/categories` và ảnh hưởng toàn bộ flow.

# Điểm mạnh hiện tại

- Luồng nghiệp vụ cốt lõi job portal đã chạy được end-to-end.
- Có quy trình duyệt employer và duyệt job cơ bản.
- Đã dùng prepared statement cho phần lớn truy vấn.
- Mô hình dữ liệu ban đầu tương đối dễ hiểu, phù hợp MVP.

# Điểm yếu hiện tại

- Kiến trúc monolith file-based gây trùng logic, khó test, khó scale team.
- Thiếu middleware/guard tập trung; auth check không đồng nhất.
- Thiếu chuẩn API, thiếu contract, thiếu versioning.
- Nhiều business rule quan trọng đang nằm trực tiếp ở UI/page layer.
- Không có queue, cache, audit log, rate limit, monitoring.

# Technical debt hiện hữu

- **Schema drift**: code tham chiếu `companies.phone/email/scale` nhưng DB dump không có.
- **Data integrity risk**: thiếu unique index cho `applications(job_id,candidate_id)`.
- **Security debt**: thiếu CSRF, thiếu kiểm tra MIME upload, thiếu anti-automation.
- **Operational debt**: config hard-code trong source (`config/db.php`), chưa có env strategy.
- **Product debt**: chưa có saved jobs, notification thật, recommendation engine, payment/subscription, chat.
