# Current Task — TopCV Lite

> Cập nhật: **2026-05-29**

---

## Trạng thái

| Phase | Status |
|-------|--------|
| CV-B | ✅ pass + commit |
| **CV-C** | ✅ code — chờ test **`「CV-C pass」`** |

---

## CV-C — đã implement

- Migration: `migrate-phase-cv-c.php`
- `ApplicationService`, `CvService::snapshotForApply`
- `apply.php` + modal `job-detail.php` (dropdown CV)
- `employer/applicants.php` + `applicant-cv-snapshot.php`

## Test nhanh

1. http://localhost/topcv_lite/docs/migrations/migrate-phase-cv-c.php
2. Candidate: có CV → apply job → chọn CV
3. Employer: applicants → **CV online**
4. Sửa CV sau apply → employer vẫn thấy snapshot cũ
5. Đơn cũ (file PDF) → **File CV** vẫn mở được

## Sau pass

`「CV-C pass」` → commit → `「xác nhận CV-D」` (tùy đồ án)
