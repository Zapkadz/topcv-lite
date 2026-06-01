# Phase 2B — Soft delete job

> Implement: **2026-05-29** — ✅ **「2B pass」** (user, 2026-05-29)

## Migration

http://localhost/topcv_lite/docs/migrations/migrate-phase-2b.php

hoặc: `php docs/migrations/run-phase-2b-job-soft-delete.php`

## Thay đổi chính

- Cột `jobs.deleted_at` (NULL = còn hiệu lực)
- `JobRepository`, `JobService`
- `job_rules`: `job_is_soft_deleted`, `job_sql_not_deleted()`, cập nhật `job_is_open_for_apply`, badge admin
- Employer: POST xóa mềm + khôi phục, tab **Đã xóa**
- Public: `index`, `jobs`, `job-detail`, `apply`, `company-detail` — không hiện tin đã xóa

## CSRF form_key mới

| form_key | Dùng ở |
|----------|--------|
| `employer_job_delete_form` | Xóa mềm |
| `employer_job_restore_form` | Khôi phục |

## Quy tắc nghiệp vụ (2 trục độc lập)

| Trục | Cột | Ai đổi |
|------|-----|--------|
| Kiểm duyệt | `status` (pending/approved/…) | Admin |
| Vòng đời NTD | `deleted_at` | Employer xóa/khôi phục |

- **Khôi phục** chỉ xóa `deleted_at`, **giữ nguyên** `status` → tin đã duyệt không cần duyệt lại.
- Tin **pending + đã xóa NTD**: không vào tab Chờ duyệt; admin không duyệt/từ chối (chỉ thấy badge ở Tất cả tin).

## Test checklist

- [x] Chạy migration 2B
- [x] Employer xóa tin → không còn trên trang chủ / jobs / job-detail
- [x] Tab **Đã xóa** → thấy tin + **Khôi phục**
- [x] Apply tin đã xóa → bị chặn
- [x] Admin badge **Đã xóa (NTD)**; pending đã xóa không vào tab Chờ duyệt
- [x] Regression deadline / expiry

## Git

`phase 2: soft delete và lifecycle job (nhóm 2B)`
