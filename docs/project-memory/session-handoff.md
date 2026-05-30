# Session Handoff — Đọc file này TRƯỚC khi làm bất cứ gì

> Cập nhật: **2026-05-29**

---

## TL;DR — Làm gì ngay?

1. **Phase 1 + 1.1** ✅ — user đã commit + test pass.
2. **Docs chuẩn hóa pre-Phase 2** ✅ — user confirm **`「xác nhận docs chuẩn hóa」`**.
3. **Git docs (tuỳ chọn):** 1 commit trước khi code Phase 2.
4. **Phase 2:** chờ **`「xác nhận 2A」`** → status model (`phase-2-mini-plan.md`).
5. **Quy trình:** mini-plan → confirm → code → test → log → Git checkpoint.

---

## Phase progress

| Phase | Status |
|-------|--------|
| 1 Critical + 1.1 | ✅ |
| Docs chuẩn hóa | ✅ confirmed |
| 2 (2A→2D) | Chưa — chờ confirm 2A |

---

## Prompt gợi ý (chat mới)

```
Đọc docs/project-memory/session-handoff.md và current-task.md.
Docs chuẩn hóa đã confirm. Phase 2 chờ 「xác nhận 2A」.
Tuân thủ coding-conventions + architecture-standardization-plan.
Tiếng Việt. User tự git commit.
```

---

## File map

| File | Mục đích |
|------|----------|
| `docs/coding-conventions.md` | Quy ước code Phase 2+ |
| `docs/pre-phase-2-structure-audit.md` | Audit + file dễ bị ảnh hưởng |
| `docs/architecture-standardization-plan.md` | Service layer + đồ án |
| `docs/phase-2-mini-plan.md` | Mini-plan 2A→2D |
| `current-task.md` | Trạng thái hiện tại |
