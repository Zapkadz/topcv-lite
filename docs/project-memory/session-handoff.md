# Session Handoff — Đọc file này TRƯỚC khi làm bất cứ gì

> Cập nhật: **2026-05-29**

---

## TL;DR — Làm gì ngay?

1. **Phase 1 + 1.1** ✅ — user đã commit.
2. **Docs chuẩn hóa pre-Phase 2** ✅ — `coding-conventions.md`, `pre-phase-2-structure-audit.md`, `architecture-standardization-plan.md`, `phase-2-mini-plan.md`.
3. **Git docs (tuỳ chọn):** 1 commit docs trước khi code Phase 2.
4. **Phase 2:** confirm `「xác nhận 2A」` → status model (xem `phase-2-mini-plan.md`).
5. **Quy trình:** mini-plan → confirm → code → test → log → Git checkpoint.

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
