# Session Handoff — Đọc file này TRƯỚC khi làm bất cứ gì

> Cập nhật: **2026-05-29**

---

## TL;DR — Làm gì ngay?

1. **Nhóm 2A đã PASS** — CSRF apply + profile hoàn tất.
3. **Git checkpoint:** ✅ Init git + 4 commit option B (2026-05-29). Xem `git log --oneline`.
4. **Bước tiếp theo:** User duyệt mini-plan Nhóm 2B → implement CSRF form còn lại.
5. **Tuân thủ quy trình:** mini-plan → confirm → sửa → test → log → project-memory → **Git checkpoint**.
6. **Không chuyển Nhóm 2B** nếu user chưa duyệt mini-plan (checkpoint đã xong).

---

## Phase 1 progress

| Nhóm | Status |
|------|--------|
| 1 UNIQUE apply | ✅ |
| 4 profile `$profile` | ✅ |
| 2A CSRF apply+profile | ✅ |
| 2B CSRF còn lại | ⏳ chờ confirm |
| 3 Upload hardening | ❌ |

---

## Prompt gợi ý (chat mới)

```
Đọc docs/project-memory/session-handoff.md.
Nhóm 2A đã pass. Tiếp tục Phase 1 — gửi/confirm mini-plan Nhóm 2B CSRF.
Tuân thủ: mini-plan → confirm → sửa → test → log. Tiếng Việt.
```

---

## File map

- `current-task.md` — mini-plan Nhóm 2B chi tiết
- `current-state.md` — snapshot dự án
- `audit-progress.md` — tiến độ audit + implement
- `known-blockers.md` — không còn active blocker
- `git-checkpoint-workflow.md` — quy trình commit sau mỗi phase/nhóm
