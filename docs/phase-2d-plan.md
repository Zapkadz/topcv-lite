# Phase 2D — Saved jobs

> **Xác nhận:** user `「xác nhận 2D」` — implement **2026-05-29** — ✅ **「2D pass」**

## Migration

http://localhost/topcv_lite/docs/migrations/migrate-phase-2d.php

hoặc: `php docs/migrations/run-phase-2d-saved-jobs.php`

## Phạm vi

- Bảng `saved_jobs` (`candidate_id`, `job_id`, `created_at`), UNIQUE `(candidate_id, job_id)`
- `SavedJobRepository`, `SavedJobService` — toggle lưu / bỏ lưu
- `job-detail.php` — nút Lưu tin / Bỏ lưu (candidate)
- `candidate/toggle-save-job.php` — POST + CSRF
- `candidate/my-jobs.php` — tab **Đã ứng tuyển** | **Đã lưu** (badge hết hạn / đã xóa)

## Không làm (defer)

- Bảng `notifications` / đếm chưa đọc

## Test checklist

- [x] Migration 2D
- [x] Lưu / bỏ lưu không trùng bản ghi
- [x] Chỉ candidate (POST + role)
- [x] Tab Đã lưu: tin hết hạn / đã xóa có badge, vẫn hiện trong list
- [x] Employer/admin không toggle được

## Git

`phase 2: saved jobs cho candidate (nhóm 2D)`
