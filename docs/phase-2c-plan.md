# Phase 2C — Moderation audit log

> **Xác nhận:** user `「xác nhận 2C」` — implement **2026-05-29** — ✅ **「2C pass」**

## Migration

http://localhost/topcv_lite/docs/migrations/migrate-phase-2c.php

hoặc: `php docs/migrations/run-phase-2c-moderation-logs.php`

## Phạm vi

- Bảng `moderation_logs` (admin_id, entity_type, entity_id, action, note, created_at)
- `ModerationLogRepository`, `ModerationLogService`
- Ghi log: admin duyệt/từ chối **job** (`admin/jobs.php`)
- Ghi log: admin duyệt/từ chối **employer** (`UserModerationService` + `admin/users.php`)
- Trang `admin/moderation-log.php` — xem lịch sử, lọc theo loại

## Không làm

- Log khi CSRF fail hoặc thao tác thất bại
- Log hard delete job (GET `?delete=`)
- Saved jobs (2D)

## Test checklist

- [x] Chạy migration 2C
- [x] Duyệt/từ chối job → 1 dòng log
- [x] Duyệt/từ chối employer → 1 dòng log
- [x] CSRF fail → không có log mới
- [x] `moderation-log.php` hiển thị đúng admin, thời gian, ghi chú

## Git

`phase 2: moderation audit log (nhóm 2C)`
