# Phase 2A — Status model (account vs employer approval)

> Implement: **2026-05-29** — chờ user test → 「2A pass」

## Mục tiêu

Tách `users.status` (tinyint lẫn “chờ duyệt NTD” và “khóa”) thành:

| Cột | Giá trị | Ý nghĩa |
|-----|---------|---------|
| `account_status` | `active`, `suspended`, `pending_verification` | Tài khoản có hoạt động không |
| `employer_approval_status` | `pending`, `approved`, `rejected`, `NULL` | Chỉ role `employer` |

## Layer mới

| File | Vai trò |
|------|---------|
| `includes/user_status.php` | Helper + badge HTML |
| `includes/repositories/UserRepository.php` | SQL users |
| `includes/services/UserModerationService.php` | Đăng ký, duyệt, login, guard |
| `includes/guards/require_employer.php` | Chặn employer chưa duyệt |

## Migration (bắt buộc trước khi test)

**Cách 1 — Trình duyệt (khuyến nghị XAMPP):**

http://localhost/topcv_lite/docs/migrations/migrate-phase-2a.php

→ Backup DB → bấm **Chạy migration Phase 2A**

**Cách 2 — CLI:**

```bash
php docs/migrations/run-phase-2a-user-status.php
```

**Cách 3:** Import SQL trong phpMyAdmin.

- Lần đầu (còn cột `status`): `phase-2a-user-status.sql` — chạy **cả file một lần**.
- Đã lỗi `#1054 Unknown column 'status'`: chỉ chạy `phase-2a-user-status-repair.sql`.

**Map dữ liệu cũ:** employer `status=0` → `employer_approval_status=pending`; `status=1` → `approved`. Cột `status` bị **DROP**.

## File đã sửa

- `register.php`, `login.php`, `admin/users.php`, `employer/auth_check.php`
- `includes/header.php` — menu user (đã bỏ `SELECT status` cũ)
- `includes/footer.php` (hiện `login_notice`)
- `topcv_lite.sql`

## CSRF form_key mới

| File | form_key |
|------|----------|
| admin/users.php (từ chối) | `admin_reject_employer_form` |

## Test checklist (user)

- [ ] **Migration:** chạy script trên DB local trước khi test
- [ ] Đăng ký employer mới → `employer_approval_status=pending`
- [ ] Employer pending **login được** → SweetAlert thông báo chờ duyệt
- [ ] Employer pending vào `employer/job-create.php` → bị chặn, redirect trang chủ
- [ ] Admin duyệt → employer đăng tin được
- [ ] Admin từ chối → employer không login được (rejected)
- [ ] Candidate đăng ký/login **không đổi** hành vi

User báo **「2A pass」** khi xong.

## Git gợi ý

```bash
git checkout -b feature/phase-2-2a-user-status
git add .
git commit -m "phase 2: tách trạng thái tài khoản employer (nhóm 2A)"
```
