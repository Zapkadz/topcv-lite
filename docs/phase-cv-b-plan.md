# Phase CV-B — UI quản lý & tạo CV online

> **Xác nhận:** `「xác nhận CV-B」` — 2026-05-29  
> **Phụ thuộc:** CV-0 UX spec, CV-A (`CvService`, migration)

## Phạm vi

| File | Mô tả |
|------|--------|
| `candidate/cv-manage.php` | Danh sách CV, đặt mặc định, xóa (POST + CSRF) |
| `candidate/cv-builder.php` | Form tạo/sửa CV + sections lặp |
| `candidate/cv-preview.php` | Xem trước read-only (classic) từ manage |
| `includes/cv_avatar.php` | Upload ảnh đại diện + quy chuẩn |
| `assets/js/cv-builder.js` | Thêm/xóa dòng học vấn, KN, kỹ năng |
| `includes/header.php` | Nav + dropdown candidate |
| `candidate/profile.php` | Khối 3 CV gần nhất + link hub |

## CSRF

| form_key | Trang |
|----------|-------|
| `candidate_cv_save_form` | cv-builder |
| `candidate_cv_delete_form` | cv-manage |
| `candidate_cv_primary_form` | cv-manage |

## Không làm (CV-C trở đi)

- Template đẹp / 2 layout, apply dropdown, employer modal snapshot
- (CV-B đã có `cv-preview.php` classic cơ bản + nút Xem trên manage)
- Section activities, certificates, template đẹp (avatar MVP đã có CV-B)

## Chuẩn dữ liệu (báo cáo / hội đồng)

| Field | DB | UI | Validation |
|-------|-----|-----|------------|
| `phone` | `varchar(10)` | `type=tel`, pattern `0[0-9]{9}` | Bắt buộc, regex `^0[0-9]{9}$` |
| `start_date` / `end_date` | `char(7)` | 2 ô số: Tháng (1–12) + Năm → `YYYY-MM` | Bắt đầu bắt buộc; kết thúc ≥ bắt đầu; để trống = đang học/làm |
| Họ tên, vị trí, email | — | required | Server `cv_validate_profile` |
| `avatar_path` | `varchar(255)` | file + Xóa ảnh | JPG/PNG/WEBP, 2MB, 200–2000px, gần vuông → `uploads/cv/avatars/` |
| `date_of_birth` | `date` | `type=date` | Tùy chọn; không tương lai; tuổi tối đa **100**; preview hiển thị `dd/mm/yyyy` |
| `gender` | `varchar(20)` | select | **Bắt buộc:** Nam / Nữ / Khác |

**Migration DB cũ (đã chạy CV-A):**  
http://localhost/topcv_lite/docs/migrations/migrate-phase-cv-b-formats.php

## Test checklist

- [ ] Migration CV-A + **CV-B formats** (nếu DB tạo trước 2026-05-29)
- [ ] SĐT: từ chối `912345678`, `09123456789`, chấp nhận `0912345678`
- [ ] Học vấn / KN: chỉ chọn tháng-năm, không gõ text tự do
- [ ] `cv-manage.php` — empty state, list, đặt mặc định, xóa (+ confirm)
- [ ] `cv-builder.php` — tạo mới, sửa, lưu children lặp
- [ ] 2 CV cùng user — data tách `cv_id`
- [ ] User khác không mở được `?id=` của người khác
- [ ] `profile.php` hiển thị list rút gọn

## Git

`phase CV: UI quản lý và builder CV online (nhóm CV-B)`
