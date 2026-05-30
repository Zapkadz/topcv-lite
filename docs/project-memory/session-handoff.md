# Session Handoff

> Cập nhật: **2026-05-29**

---

## TL;DR

1. Phase **1 + 1.1** ✅ | Docs chuẩn hóa ✅ | Phase **2A** ✅ pass
2. Migration 2A: `docs/migrations/migrate-phase-2a.php` hoặc `phase-2a-user-status-repair.sql`
3. Git 2A: branch `feature/phase-2-2a-user-status` → PR → `main`
4. Tiếp: **`「xác nhận 2B」`** (soft delete job)

---

## Phase progress

| Phase | Status |
|-------|--------|
| 2A User status | ✅ pass |
| 2B Job lifecycle | Chưa |
| 2C Moderation log | Chưa |
| 2D Saved jobs | Chưa |

---

## File map Phase 2A

- `includes/services/UserModerationService.php`
- `includes/repositories/UserRepository.php`
- `includes/guards/require_employer.php`
- `docs/phase-2a-plan.md`
