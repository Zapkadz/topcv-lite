# Session Handoff

> Cập nhật: **2026-05-29**

---

## TL;DR

1. Phase **1 + 1.1** ✅ | Docs chuẩn hóa ✅ | **2A** ✅ | **2B** ✅ pass
2. Migration 2B: `docs/migrations/migrate-phase-2b.php` (parser SQL comment đã fix — dùng logic PHP)
3. Git 2B: `feature/phase-2-2b-job-soft-delete` → commit → PR → `main`
4. Tiếp: **`「xác nhận 2C」`** (moderation audit log)

---

## Phase progress

| Phase | Status |
|-------|--------|
| 2A User status | ✅ pass |
| 2B Job soft delete | ✅ pass |
| 2C Moderation log | Chưa |
| 2D Saved jobs | Chưa |

---

## File map Phase 2B

- `includes/repositories/JobRepository.php`
- `includes/services/JobService.php`
- `includes/schema_jobs.php`
- `includes/job_rules.php` — `job_sql_not_deleted`, `job_admin_status_badge_html`
- `employer/manage-jobs.php` — tab Đã xóa, POST xóa/khôi phục
- `admin/jobs.php` — pending loại tin đã xóa NTD; chặn duyệt/từ chối
- `docs/migrations/migrate-phase-2b.php`, `phase-2b-job-soft-delete.sql`
- `docs/phase-2b-plan.md`

---

## File map Phase 2A (tham chiếu)

- `includes/services/UserModerationService.php`
- `includes/repositories/UserRepository.php`
- `includes/guards/require_employer.php`
- `docs/phase-2a-plan.md`
