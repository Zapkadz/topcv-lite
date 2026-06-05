# Dev Learning Log

## 2026-05-28 - Phase 1 / Nhóm 1: Chặn apply trùng

### Mục tiêu
- Đảm bảo một ứng viên không thể có 2 đơn cho cùng một công việc, kể cả khi có request đồng thời.

### Những gì đã thay đổi
- Cập nhật `topcv_lite.sql`:
  - Thêm unique key `uniq_job_candidate (job_id, candidate_id)` trong bảng `applications`.
- Cập nhật `apply.php`:
  - Bổ sung `catch (PDOException $e)` để bắt lỗi duplicate key ở DB.
  - Nếu trùng đơn thì hiển thị thông báo thân thiện: "Bạn đã ứng tuyển công việc này rồi!".
  - Các lỗi DB khác hiển thị thông báo an toàn: "Lỗi hệ thống, vui lòng thử lại.".

### Vì sao làm vậy
- Chặn ở UI hoặc check trước insert chỉ đủ cho luồng bình thường.
- Chặn ở DB bằng `UNIQUE` mới là lớp bảo vệ cuối cùng chống race condition.

### Bài học rút ra
- Rule quan trọng của hệ thống phải có ràng buộc ở database, không chỉ ở code giao diện/backend.
- Cặp thao tác `SELECT rồi INSERT` luôn có rủi ro race nếu thiếu constraint.

### Lưu ý triển khai
- Khi áp dụng constraint trên DB đang chạy thật, cần kiểm tra dữ liệu trùng trước để migration không fail.

## 2026-05-28 - Phase 1 / Nhóm 4: Sửa runtime hồ sơ ứng viên (`$profile`)

### Mục tiêu
- Ngăn lỗi biến chưa khởi tạo khi mở trang `candidate/profile.php`.

### Những gì đã thay đổi
- Cập nhật `candidate/profile.php`:
  - Thêm query lấy dữ liệu hồ sơ hiện tại từ bảng `candidates` theo `user_id`.
  - Khởi tạo fallback an toàn khi user chưa có bản ghi (`title`, `cv_path`, `bio` rỗng).

### Vì sao làm vậy
- Form đang đọc `$profile['title']`, `$profile['cv_path']`, `$profile['bio']` nhưng trước đó chưa có đoạn gán `$profile`.
- Trong runtime thật, điều này có thể gây warning/notice và làm trang hiển thị không ổn định.

### Bài học rút ra
- Với trang edit profile, luôn cần 2 nhánh dữ liệu rõ ràng:
  1) đã có bản ghi -> load dữ liệu cũ,
  2) chưa có bản ghi -> dùng giá trị mặc định an toàn.

### Lưu ý triển khai
- Đây là fix phạm vi hẹp, không thay đổi nghiệp vụ insert/update hiện tại.

## 2026-05-28 - Phase 1 / Nhóm 2A: CSRF cho apply + profile

### Mục tiêu
- Bịt lỗ hổng CSRF ở 2 luồng ưu tiên cao: gửi hồ sơ ứng tuyển và cập nhật hồ sơ ứng viên.

### Những gì đã thay đổi
- Tạo mới `includes/csrf.php`:
  - `csrf_token($form_key)` để tạo token theo từng form.
  - `csrf_validate($form_key, $token)` để kiểm tra token an toàn bằng `hash_equals`.
- Cập nhật `job-detail.php`:
  - Nhúng hidden input `csrf_token` vào form apply.
- Cập nhật `apply.php`:
  - Thêm validate CSRF trước khi xử lý nghiệp vụ apply.
  - Nếu token sai/thiếu -> chặn request và báo "Phiên làm việc không hợp lệ, vui lòng thử lại."
- Cập nhật `candidate/profile.php`:
  - Nhúng hidden input `csrf_token` vào form cập nhật hồ sơ.
  - Validate CSRF ở đầu nhánh POST trước khi cập nhật dữ liệu.

### Vì sao làm vậy
- POST request không có CSRF token có thể bị giả mạo từ website khác khi user đang đăng nhập.
- Token theo form giúp server xác nhận request thật sự xuất phát từ form của hệ thống.

### Bài học rút ra
- Bảo mật form POST nên được chuẩn hóa bằng helper chung để tránh mỗi file tự làm một kiểu.
- Validate phải chạy ở đầu luồng xử lý POST, trước khi đụng nghiệp vụ hoặc DB.

### Lưu ý triển khai
- Đây là giai đoạn 2A (phạm vi hẹp). Các form POST còn lại sẽ được phủ CSRF ở các nhóm kế tiếp.

### Kết quả test (user xác nhận 2026-05-29)
- Apply job bình thường: ✅ pass
- Cập nhật profile bình thường: ✅ pass
- Submit thiếu/sai CSRF token bị chặn: ✅ pass
- **Trạng thái nhóm: HOÀN TẤT**

## 2026-05-29 - Incident: Bảng `applications` corruption (error 1932) — ĐÃ XỬ LÝ

### Vấn đề
- Sau khi chạy `ALTER TABLE` thêm UNIQUE (Nhóm 1), bảng `applications` trên DB live bị lỗi 1932 (metadata còn, engine mất).

### Cách xử lý
- `DROP TABLE` + `CREATE TABLE` lại bảng `applications` (có UNIQUE + FK như schema mới).

### Bài học rút ra
- Migration trên DB đang chạy nên dùng file migration riêng, backup trước, verify sau ALTER.
- Không chỉ sửa file `topcv_lite.sql` dump mà quên apply an toàn lên DB live.

## 2026-05-29 - Phase 1 / Nhóm 2B: CSRF cho auth, employer, admin

### Mục tiêu
- Phủ CSRF cho toàn bộ form POST còn lại (login, register, employer, admin).

### Những gì đã thay đổi
- Tái sử dụng `includes/csrf.php` (helper từ Nhóm 2A).
- **Auth:** `login.php` (`login_form`), `register.php` (`register_form`).
- **Employer:** `company.php`, `job-create.php`, `job-edit.php`, `applicants.php` — mỗi form một `form_key` riêng.
- **Admin:** `users.php` (duyệt NTD), `jobs.php` (duyệt/từ chối tin), `categories.php` (thêm/sửa danh mục).
- Mỗi handler POST: `csrf_validate()` ở đầu; fail → thông báo "Phiên làm việc không hợp lệ..." + redirect.

### `form_key` đã dùng
| File | form_key |
|------|----------|
| login.php | `login_form` |
| register.php | `register_form` |
| employer/company.php | `employer_company_form` |
| employer/job-create.php | `employer_job_create_form` |
| employer/job-edit.php | `employer_job_edit_form` |
| employer/applicants.php | `employer_applicant_status_form` |
| admin/users.php | `admin_approve_employer_form` |
| admin/jobs.php | `admin_job_moderate_form` |
| admin/categories.php | `admin_category_form` |

### Không nằm phạm vi 2B
- `employer/manage-jobs.php` — xóa job bằng GET (sẽ xử lý POST+CSRF ở phase sau).
- `admin/jobs.php`, `admin/categories.php` — xóa bằng GET.

### Branch
- `feature/phase-1-2b-csrf` → merge vào `main` qua PR.

### Kết quả test (user xác nhận 2026-05-29)
- Auth (login/register): ✅ pass
- Employer (company, job-create/edit, applicants): ✅ pass
- Admin (users, jobs, categories): ✅ pass
- CSRF sai/thiếu bị chặn: ✅ pass
- Regression 2A (apply + profile): ✅ pass
- **Trạng thái nhóm: HOÀN TẤT**

## 2026-05-29 - Phase 1 / Nhóm 3: Upload hardening (CV + logo)

### Mục tiêu
- Kiểm extension + MIME thật (`finfo`) + giới hạn dung lượng cho upload CV và logo.

### Những gì đã thay đổi
- Tạo `includes/upload_validate.php` — `upload_validate($file, $kind)` với `$kind` = `cv` | `image`.
- **CV** (`apply.php`, `candidate/profile.php`): pdf/doc/docx, MIME whitelist, tối đa **5MB**.
- **Logo** (`employer/company.php`): jpg/jpeg/png/webp, tối đa **2MB**.
- Profile/company: lỗi upload hiển thị SweetAlert (không im lặng); logo/CV invalid → **không lưu** form lần đó.
- `apply.php`: giữ flow copy CV online (snapshot); chỉ harden nhánh upload file mới.

### Bài học rút ra
- Chỉ check đuôi file không đủ — phải đọc MIME từ nội dung file bằng `finfo_file()`.
- Nên gom rule upload vào một helper để đồng bộ giới hạn và thông báo lỗi.

### Branch
- `feature/phase-1-3-upload` → merge `main` qua PR.

### Kết quả test (user xác nhận 2026-05-29)
- CV hợp lệ (profile, apply upload, apply online): ✅ pass
- Chặn file giả đuôi / quá size / MIME sai: ✅ pass
- Logo hợp lệ + chặn invalid: ✅ pass
- Regression CSRF: ✅ pass
- **Trạng thái nhóm: HOÀN TẤT — Phase 1 Critical Fixes đóng**

## 2026-05-29 - Phase 1.1: Deadline, expiry, locations

### Mục tiêu
- Chặn employer đặt hạn nộp trong quá khứ.
- Đồng bộ logic hết hạn (public apply, admin badge, employer manage).
- Cập nhật 36 địa điểm (Nghị quyết 34 đơn vị + Remote + Khác) + admin CRUD.

### Những gì đã thay đổi
- `includes/job_rules.php` — `job_validate_deadline()`, `job_is_expired()`, `job_is_open_for_apply()`, `job_admin_status_badge_html()`.
- Employer: validate + `min="<?= job_today_date() ?>"` trên `job-create.php`, `job-edit.php`.
- Public: `job-detail.php` (banner + nút disabled), `apply.php` (check server).
- Admin: `jobs.php` badge **Hết hạn**; `locations.php` CRUD + CSRF `admin_location_form`.
- `employer/manage-jobs.php` dùng `job_is_expired()` (sửa bug so sánh `time()` vs midnight).
- Seed: `docs/migrations/run-phase-1-1-locations.php` (UTF-8, Windows-safe); `topcv_lite.sql` 36 dòng.

### Bài học rút ra
- So sánh hết hạn nên dùng **ngày** (`Y-m-d`), không `strtotime` + `time()` — dễ coi hết hạn sớm trong ngày deadline.
- Trên Windows, pipe file `.sql` tiếng Việt dễ hỏi encoding → chạy migration bằng PHP/PDO hoặc mysql với `--default-character-set=utf8mb4`.

### Kết quả test (user xác nhận 2026-05-29)
- Deadline, expiry, locations, location picker, HTML mô tả (admin + job-edit CKEditor): ✅ pass
- **Trạng thái: HOÀN TẤT — Phase 1.1 đóng**

## 2026-05-29 - Pre-Phase 2: Chuẩn hóa docs (không sửa code)

### Mục tiêu
- Chuẩn hóa nhẹ trước Phase 2: conventions, audit cấu trúc, kế hoạch mini MVC/service layer, mini-plan Phase 2.

### Tài liệu đã tạo
- `docs/coding-conventions.md`
- `docs/pre-phase-2-structure-audit.md`
- `docs/architecture-standardization-plan.md`
- `docs/phase-2-mini-plan.md`

### Kết quả
- User xác nhận **`「xác nhận docs chuẩn hóa」`** — sẵn sàng Phase 2 (chờ confirm 2A).
- Git docs: tuỳ chọn 1 commit trước khi code.

## 2026-05-29 - Phase 2 / Nhóm 2A: Status model user/employer

### Mục tiêu
- Tách `users.status` → `account_status` + `employer_approval_status`.
- Service layer đầu tiên: `UserRepository`, `UserModerationService`, guard `require_employer.php`.

### Những gì đã thay đổi
- Migration `phase-2a-user-status.sql`, `phase-2a-user-status-repair.sql`, `migrate-phase-2a.php`.
- Layer: `UserRepository`, `UserModerationService`, `user_status.php`, `schema_users.php`, `require_employer.php`.
- Pages: `register.php`, `login.php`, `admin/users.php`, `includes/header.php`, `employer/auth_check.php`.

### Bài học rút ra
- Tách cột `status` theo domain sớm — tránh overload một tinyint.
- File trong `includes/guards/` cần `../../config/db.php`, không phải `../config`.
- Migration SQL: chạy cả file một lần; nếu đã DROP `status` thì dùng repair script.
- Sau đổi schema, rà **toàn project** query cột cũ (header.php sót `users.status`).

### Kết quả test (user xác nhận 2026-05-29)
- ✅ **「2A pass」**

## 2026-05-29 - Phase 2 / Nhóm 2B: Soft delete job

### Mục tiêu
- `jobs.deleted_at` — xóa mềm do NTD; public/apply không thấy tin đã xóa.
- Layer: `JobRepository`, `JobService` (mẫu sau 2A).

### Những gì đã thay đổi
- Migration: `migrate-phase-2b.php` (PHP idempotent, tránh lỗi parse comment SQL).
- `job_rules`: `job_sql_not_deleted()`, `job_is_open_for_apply` + soft delete, `job_admin_status_badge_html`.
- Employer: `manage-jobs.php` — POST xóa/khôi phục, tab Đã xóa; CSRF `employer_job_delete_form`, `employer_job_restore_form`.
- Public: `index`, `jobs`, `job-detail`, `apply`, `company-detail`, `employer/dashboard`.
- Admin: badge **Đã xóa (NTD)**; pending + đã xóa NTD không vào hàng chờ duyệt.

### Bài học rút ra
- **Hai trục:** `status` (admin) vs `deleted_at` (NTD) — khôi phục chỉ clear `deleted_at`, giữ `status`.
- Migration: đừng `array_filter` cả statement nếu file SQL có comment đầu dòng (đã lỗi chỉ chạy CREATE INDEX).
- Admin queue phải lọc `deleted_at` khi `status = pending`, tránh duyệt tin NTD đã bỏ.

### Kết quả test (user xác nhận 2026-05-29)
- ✅ **「2B pass」**

## 2026-05-29 - Phase 2 / Nhóm 2C: Moderation audit log

### Mục tiêu
- Ghi lại admin duyệt/từ chối job và employer; trang xem lịch sử.

### Những gì đã thay đổi
- Bảng `moderation_logs`, migration `migrate-phase-2c.php`.
- `ModerationLogRepository`, `ModerationLogService`, `JobModerationService`.
- `UserModerationService::approveEmployer/rejectEmployer` nhận `adminId` để ghi log.
- `admin/jobs.php`, `admin/users.php`, `admin/moderation-log.php`, menu sidebar.

### Bài học rút ra
- Chỉ log sau khi UPDATE thành công (`rowCount` / return bool).
- CSRF fail → redirect sớm, không gọi service → không log.

### Kết quả test (user xác nhận 2026-05-29)
- ✅ **「2C pass」**

## 2026-05-29 - Phase 2 / Nhóm 2D: Saved jobs

### Mục tiêu
- Ứng viên lưu/bỏ lưu tin; tab **Đã lưu** trên `my-jobs.php`.

### Những gì đã thay đổi
- Bảng `saved_jobs`, migration `migrate-phase-2d.php`.
- `SavedJobRepository`, `SavedJobService`, `job_saved_listing_badge_html`.
- `job-detail.php` nút Lưu; `candidate/toggle-save-job.php` (POST+CSRF).
- `candidate/my-jobs.php` tabs Ứng tuyển / Đã lưu.

### Không làm
- `notifications` (defer).

### Kết quả test (user xác nhận 2026-05-29)
- ✅ **「2D pass」**

## 2026-05-29 - CV structured: Xác nhận roadmap + CV-0 spec

### User
- ✅ **`「xác nhận CV roadmap」`**

### Tài liệu
- `docs/structured-cv-roadmap.md` (phase CV-0…F)
- `docs/phase-cv-0-ux-spec.md` (wireframe, IA, map field → DB)

### Tiếp theo
- Chờ **`「xác nhận CV-0」`** → code **CV-A**

## 2026-05-29 - Phase CV / Nhóm CV-A: Schema + Service

### User
- ✅ **`「xác nhận CV-0」`**

### Những gì đã thay đổi
- Bảng `cv_profiles`, `cv_educations`, `cv_experiences`, `cv_skills`
- `CvRepository`, `CvService`, `cv_rules.php`, `schema_cvs.php`
- Migration `migrate-phase-cv-a.php`, `smoke-test-cv-a.php`

### Kết quả test (user xác nhận 2026-05-29)
- ✅ **「CV-A pass」**

## 2026-05-29 - Phase CV / Nhóm CV-B: UI manage + builder

### User
- ✅ **`「xác nhận CV-B」`**

### Những gì đã thay đổi
- `candidate/cv-manage.php` — list, đặt mặc định, xóa (SweetAlert confirm)
- `candidate/cv-builder.php` — form tạo/sửa + sections lặp
- `assets/js/cv-builder.js` — thêm/xóa dòng
- `includes/cv_rules.php` — `cv_parse_builder_post`, filter rows
- `includes/header.php`, `candidate/profile.php`, `includes/footer.php` — điều hướng

### Bổ sung sau (validation, avatar, preview)
- `cv-preview.php`, `cv_avatar.php`, ảnh đại diện + quy chuẩn upload
- SĐT `^0[0-9]{9}$`; tháng/năm ô số → `YYYY-MM` (`char(7)`); ngày sinh tối đa 100 tuổi; giới tính bắt buộc
- Migration: `migrate-phase-cv-b-formats.php`

### Kết quả test
- ✅ **「CV-B pass」**

### Tiếp theo
- **`「xác nhận CV-C」`** — apply snapshot + employer xem CV

## 2026-05-29 - Phase CV / Nhóm CV-C: Apply snapshot + employer

### Những gì đã thay đổi
- `applications.cv_profile_id`, `cv_snapshot_json`
- `ApplicationService::applyToJob`, dropdown CV trên `job-detail.php`
- Employer modal snapshot

### Git
- Commit `640bf08` trên `feature/phase-cv-b-ui` — **không có trên `main`** khi tạo nhánh CV-D
- Khôi phục: `git cherry-pick 640bf08` lên `feature/phase-cv-d-sections`

## 2026-05-29 - Phase CV / Nhóm CV-D: Section + template

### User
- **`「xác nhận CV-D」`**, **`「bắt đầu code CV-D」`**

### Những gì đã thay đổi
- Migration 4 bảng section; template classic/modern; builder/preview mở rộng

### Kết quả test
- ✅ **`「CV-D pass」`**

### Tiếp theo
- Push PR `feature/phase-cv-d-sections` → `main`
- **`「xác nhận CV-E」`** (tùy chọn)

## 2026-06-06 - Phase CV / Nhóm CV-E: Import PDF → AI pre-fill (Mức B)

### User
- **`「xác nhận CV-E」`**, khối E0→E7 pass + commit trên `feature/phase-cv-e-import`

### Kiến trúc
- **Local:** `smalot/pdfparser` trích text PDF (không gửi binary lên AI)
- **AI:** Groq `llama-3.3-70b-versatile` (fallback OpenRouter/Gemini trong code)
- **Pipeline:** extract → clean text → AI JSON → fallback regex → normalize → session draft → builder → Lưu + `attachment_path`

### Những gì đã thay đổi (tóm tắt)
- `cv-import.php`, `cv-manage` CTA, `cv-builder` nhánh `from_import=1`
- `PdfTextExtractor`, `AiCvParserService`, `CvParseService`, `cv_import_text_clean.php`
- Prompt AI chống lẫn mục; `cv_import_prune_incomplete_children`
- Rate limit 5 import/giờ; `docs/setup-cv-import.md`

### Bài học / giới hạn
- PDF **Word export** → text ổn; PDF **Canva/template** → text nhiễu (lặp chữ, ngày tách nội dung)
- Tách mục cứng (`--- Mục N ---`) **kém hơn** prompt AI trên PDF nhiễu — đã revert
- Clean text (dedup, section break) + prompt “thời gian phải kèm trường/công ty” hiệu quả hơn
- Gemini free tier user test bị 429; Groq ổn định hơn cho demo
- **CV-F defer:** OCR scan, Vision multimodal, DOCX

### Kết quả test (user)
- E1–E7: ✅ pass (xem `phase-cv-e-checklist.md` checkpoint log)
- E8 regression: ⏳ chờ `「CV-E pass」`

### Tiếp theo
- User tick regression checklist → **`「CV-E pass」`** → PR merge
- Tuỳ chọn **CV-F** (OCR / Vision)
