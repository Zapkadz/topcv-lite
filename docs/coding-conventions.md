# Coding Conventions — TopCV Lite

> Áp dụng từ **sau Phase 1.1** cho mọi thay đổi mới (đặc biệt Phase 2 trở đi).  
> Mục tiêu: PHP thuần, page-based, **không framework**, dễ đọc cho đồ án và AI pair-programming.

---

## 1. Quy ước cấu trúc thư mục

| Thư mục | Vai trò | Quy tắc |
|---------|---------|---------|
| `/` (root) | Trang **public** (guest + đã login) | Chỉ file “cổng” portal: `index.php`, `jobs.php`, `job-detail.php`, `login.php`, … |
| `admin/` | Khu admin | Mỗi file = 1 màn hình; layout: `admin/includes/header.php` |
| `employer/` | Khu nhà tuyển dụng | Có `auth_check.php` dùng chung |
| `candidate/` | Khu ứng viên | Guard role trong từng file hoặc helper (xem mục 6) |
| `config/` | Cấu hình | `db.php` + `db.example.php`; override local: `db.local.php` (**không commit**) |
| `includes/` | Code dùng chung | Helper, guard, service, repository (mở rộng dần) |
| `includes/services/` | Logic nghiệp vụ | Phase 2+: tách khỏi page |
| `includes/repositories/` | Truy vấn PDO thuần | Chỉ SQL + fetch, không SweetAlert/redirect |
| `includes/validators/` | Kiểm tra input | Trả về `['ok' => bool, 'message' => string]` |
| `assets/css`, `assets/js` | Static tách file | UI component tái sử dụng (vd. location-picker) |
| `uploads/` | File người dùng | **Gitignore** nội dung; giữ `.gitkeep` |
| `docs/` | Tài liệu, migration, memory | Mỗi phase có plan + learning log |

**Không** thêm framework, **không** đổi URL hiện tại khi refactor.

---

## 2. Quy ước đặt file

| Loại | Quy tắc | Ví dụ |
|------|---------|-------|
| Page (controller/view) | `kebab-case` hoặc tên nghiệp vụ ngắn, `.php` | `job-create.php`, `my-jobs.php` |
| Helper / service | `snake_case.php` | `job_rules.php`, `JobService.php` (class PSR-ish tùy file) |
| Migration SQL | `docs/migrations/phase-X-Y-mô-tả.sql` | `phase-1-1-locations-seed.sql` |
| Script chạy 1 lần | `docs/migrations/run-*.php` | `run-phase-1-1-locations.php` |
| Plan phase | `docs/phase-X-plan.md` hoặc `phase-X-Y-plan.md` | `phase-1-1-plan.md` |

**Một file page** nên theo thứ tự block (comment rõ nếu file dài):

1. Bootstrap (session, config, helpers, guard)  
2. Xử lý POST/GET (action)  
3. Load dữ liệu cho view  
4. HTML + include header/footer  

---

## 3. Quy ước xử lý POST / GET

| Hành động | Method | Ghi chú |
|-----------|--------|---------|
| Đăng nhập, đăng ký, tạo/sửa, duyệt, từ chối | **POST** | Bắt buộc CSRF (mục 4) |
| Lọc, phân trang, xem danh sách | **GET** | Không đổi dữ liệu |
| Xóa / ẩn / đổi trạng thái | **POST** (Phase 2+) | **Không** dùng `?delete=` (nợ Phase 1 còn sót) |

Sau POST thành công: **PRG** (Post-Redirect-Get) — `header('Location: ...'); exit;`

Tham số ID từ URL: `intval()` hoặc `(int)`; kiểm tra tồn tại + quyền sở hữu (employer chỉ sửa job của mình).

---

## 4. Quy ước CSRF

- Helper: `includes/csrf.php` — `csrf_token($form_key)`, `csrf_validate($form_key, $token)`.
- Mỗi form POST có **`form_key` riêng** (không dùng chung một key cho cả site).
- Form HTML: `<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('...')) ?>">`
- Đầu handler POST:

```php
if (!csrf_validate('employer_job_create_form', $_POST['csrf_token'] ?? '')) {
    $_SESSION['swal_icon'] = 'error';
    $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    header('Location: ...');
    exit;
}
```

**Danh sách `form_key` đã dùng:** ghi trong `docs/dev-learning-log.md` hoặc bảng trong plan từng phase.

---

## 5. Quy ước validate dữ liệu

- Rule **theo domain** đặt trong `includes/*_rules.php` hoặc `includes/validators/`.
- Hàm validate trả về: `['ok' => bool, 'message' => string]` (thống nhất với `job_validate_deadline()`).
- Validate **cả client lẫn server** (client = UX; server = tin cậy).
- Không tin `$_POST` / `$_GET` — ép kiểu, whitelist enum (status job, role, …).
- HTML từ CKEditor: lưu HTML có kiểm soát; hiển thị qua `html_display()` / `html_to_plain()` (`includes/html_content.php`).

---

## 6. Quy ước auth / role guard

| Vai trò | Cách hiện tại | Hướng Phase 2+ |
|---------|----------------|----------------|
| Employer | `employer/auth_check.php` | Giữ; có thể bọc thành `require_role('employer')` |
| Admin | Trong `admin/includes/header.php` | Tách `includes/guards/require_admin.php` |
| Candidate | Inline trong từng file | `includes/guards/require_candidate.php` |

Session tối thiểu: `user_id`, `role`, (sau Phase 2) có thể thêm flag trạng thái tài khoản.

**Không** tin `role` từ client — luôn đọc từ session sau `login.php`.

---

## 7. Quy ước database query

- Kết nối: biến `$conn` (PDO) từ `config/db.php`.
- **Luôn** dùng `prepare()` + `execute([...])` cho input động.
- Query đọc list: `ORDER BY` rõ ràng; cột cần index ghi chú trong migration.
- Logic nghiệp vụ phức tạp (duyệt job, soft delete): Phase 2 đưa vào **repository** hoặc **service**, page chỉ gọi 1–2 hàm.
- Schema thay đổi: file trong `docs/migrations/` + cập nhật `topcv_lite.sql` cho cài mới.

---

## 8. Quy ước include / require_once

| Ưu tiên | Cú pháp |
|---------|----------|
| Helper trong project | `require_once __DIR__ . '/../includes/xxx.php';` |
| Config DB | `include` hoặc `require_once` từ `config/db.php` (đã có `$conn`) |
| Guard employer | `include 'auth_check.php';` (trong thư mục employer) |

- **Helper / class logic:** `require_once` (tránh load 2 lần).  
- **View fragment (header/footer):** `include` chấp nhận được.  
- **Không** dùng đường dẫn tương đối mơ hồ kiểu `include '../includes/x.php'` nếu có thể thay bằng `__DIR__`.

---

## 9. Quy ước redirect / thông báo lỗi

- Flash UX: `$_SESSION['swal_icon']`, `$_SESSION['swal_title']` + SweetAlert ở layout (pattern hiện có).
- Lỗi validate: set flash → redirect về cùng form (giữ context `?id=` nếu edit).
- Lỗi DB duplicate / constraint: bắt `PDOException`, message thân thiện (không lộ SQL).
- **Không** `echo` + `exit` lỗi giữa HTML trừ trường hợp debug local.

---

## 10. Quy ước file upload

- Helper: `includes/upload_validate.php` — `upload_validate($file, 'cv'|'image')`.
- CV: pdf/doc/docx, max 5MB, MIME `finfo`.  
- Logo: jpg/png/webp, max 2MB.  
- Tên file lưu disk: random/unique; đường dẫn trong DB tương đối từ root project.  
- Upload fail → không commit phần còn lại của form (transaction logic nếu có).

---

## 11. Quy ước cập nhật `docs/project-memory`

Sau mỗi nhóm/phase **test pass**:

| File | Nội dung cập nhật |
|------|-------------------|
| `current-task.md` | Phase đang làm, bước tiếp theo |
| `current-state.md` | File code đã đổi / helper mới |
| `session-handoff.md` | TL;DR cho chat mới |
| `dev-learning-log.md` | Bài học + checklist test |
| `audit-progress.md` | Đánh dấu mục roadmap xong |
| `known-blockers.md` | Blocker mới / đã xử lý |

---

## 12. Quy ước Git checkpoint sau mỗi phase

1. User xác nhận test (`「X pass」`).  
2. `git status` — không commit secret, file rác (`5`, dump DB, upload user).  
3. Branch: `feature/phase-X-<tên-nhóm>`.  
4. Commit message:

```text
phase <số>: <mô tả ngắn> (nhóm Y)
```

5. Push → PR → merge `main` (theo `docs/github-workflow.md`).  
6. Cập nhật bảng checkpoint trong `docs/project-memory/git-checkpoint-workflow.md`.

**Docs-only commit** (conventions, plan): `docs: chuẩn hóa conventions và pre-phase-2 audit`

---

## Tham chiếu

- `docs/architecture-standardization-plan.md` — lộ trình tách service/repository  
- `docs/pre-phase-2-structure-audit.md` — nợ kỹ thuật & file Phase 2  
- `docs/master-refactor-roadmap.md` — roadmap tổng
