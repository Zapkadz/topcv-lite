# Current Task — TopCV Lite

> Cập nhật: **2026-05-29**  
> Đọc file này để biết **đang làm gì**, **dừng ở đâu**, **bước tiếp theo chính xác**.

---

## Trạng thái hiện tại

**Phase:** Phase 1 — Critical Fixes  
**Nhóm vừa hoàn tất:** Nhóm 2A (CSRF apply + profile) — **✅ Code + Test PASSED** (user xác nhận 2026-05-29)  
**Nhóm tiếp theo (chưa bắt đầu):** Nhóm 2B (CSRF các form còn lại) — **chờ user duyệt mini-plan**

---

## BLOCKER

**Không còn blocker active.**  
BLOCKER-001 (bảng `applications` error 1932) đã resolved — user recreate bảng và test pass.

---

## Tiến độ Phase 1 Critical Fixes

| Nhóm | Nội dung | Code | Test user |
|------|----------|------|-----------|
| **1** | UNIQUE apply + duplicate handling | ✅ | ✅ Passed |
| **4** | Fix `$profile` runtime | ✅ | ✅ Passed |
| **2A** | CSRF apply + profile | ✅ | ✅ Passed (2026-05-29) |
| **2B** | CSRF các form còn lại | ❌ Chưa | ❌ |
| **3** | Upload hardening | ❌ Chưa | ❌ |

---

## Nhóm 2A — Đã hoàn tất

### File liên quan
- `includes/csrf.php`
- `job-detail.php`, `apply.php`, `candidate/profile.php`

### Test đã pass
1. ✅ Apply job bình thường
2. ✅ Cập nhật profile bình thường
3. ✅ Xóa/sai CSRF token → bị chặn

---

## Bước tiếp theo chính xác

### Bước 1 — User duyệt mini-plan Nhóm 2B (xem bên dưới)
### Bước 2 — Implement CSRF cho form còn lại (sau khi confirm)
### Bước 3 — Test Nhóm 2B
### Bước 4 — Mini-plan Nhóm 3 (upload hardening)

---

## Mini-plan Nhóm 2B (CHỜ USER XÁC NHẬN — chưa sửa code)

### 1) Lỗi cần sửa
Các form POST còn lại chưa có CSRF token → vẫn có thể bị tấn công CSRF.

### 2) File liên quan (phạm vi đề xuất)
**Auth (public):**
- `login.php` — form POST đăng nhập
- `register.php` — form POST đăng ký

**Employer:**
- `employer/company.php`
- `employer/job-create.php`
- `employer/job-edit.php`
- `employer/applicants.php` (POST đổi trạng thái)
- `employer/manage-jobs.php` (GET delete — cân nhắc chuyển POST + CSRF ở phase sau; **Nhóm 2B chỉ POST forms**)

**Admin:**
- `admin/users.php` (POST duyệt employer)
- `admin/jobs.php` (POST approve/reject)
- `admin/categories.php` (POST add/edit)

**Helper:** tái sử dụng `includes/csrf.php` (đã có).

### 3) Nguyên nhân
Nhóm 2A mới phủ 2 luồng candidate; các role/form khác vẫn thiếu.

### 4) Ảnh hưởng thực tế
Attacker có thể lợi dụng session admin/employer để thực hiện hành động ngoài ý muốn (duyệt tin, sửa job, duyệt NTD...).

### 5) Cách fix
- Mỗi form POST: thêm `csrf_token` hidden với `form_key` riêng (vd: `login_form`, `register_form`, `employer_company_form`...).
- Mỗi handler POST: `csrf_validate()` ở đầu, fail → flash message + redirect.

### 6) Rủi ro
- Quên 1 form → submit hợp lệ bị chặn (regression).
- Form admin/employer nhiều → cần test theo từng role.

### 7) Cách test
- Mỗi form: submit hợp lệ OK; xóa token → bị chặn.
- Smoke test theo role: candidate (đã xong 2A), employer, admin.

---

## Ghi chú cho AI chat mới

- User xác nhận pass Nhóm 2A ngày 2026-05-29.
- **Không sửa Nhóm 2B** cho đến khi user nói "xác nhận mini-plan 2B".
- Quy trình: mini-plan → confirm → sửa → dev-learning-log → test → project-memory.
