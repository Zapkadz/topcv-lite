# Session Handoff — Đọc file này TRƯỚC khi làm bất cứ gì

> Cập nhật: **2026-05-29**

---

## TL;DR — Làm gì ngay?

1. **Phase 1 + 1.1 code xong** — chờ user test Phase 1.1.
2. **Migration locations (nếu chưa):** `php docs/migrations/run-phase-1-1-locations.php`
3. **Git:** User tự commit branch `feature/phase-1-1-job-logic` (AI không commit trừ khi được yêu cầu).
4. User test xong → **「1.1 pass」** → mới bắt **Phase 2**.
5. **Quy trình:** mini-plan → confirm → sửa → test → log → project-memory.

---

## Phase progress

| Phase | Status |
|-------|--------|
| 1 Critical (1,4,2A,2B,3) | ✅ |
| 1.1 Job logic (deadline, expiry, locations) | ✅ code — ⏳ user test |
| 2 | Chưa |

---

## Prompt gợi ý (chat mới)

```
Đọc docs/project-memory/session-handoff.md và current-task.md.
Phase 1.1 đã code — user test / fix nếu fail. Sau 「1.1 pass」 → Phase 2.
Tiếng Việt. User tự git commit.
```

---

## File map

- `current-task.md` — trạng thái + lệnh git gợi ý
- `docs/phase-1-1-plan.md` — checklist test 1.1
- `includes/job_rules.php` — deadline + expiry helpers
- `docs/migrations/run-phase-1-1-locations.php` — seed locations UTF-8
