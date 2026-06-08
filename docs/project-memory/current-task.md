# Current Task — TopCV Lite

> Cập nhật: **2026-06-08**

---

## Trạng thái

| Phase | Status |
|-------|--------|
| CV-A … CV-F | ✅ merged `main` |
| **EMP-A** | ✅ merged `main` (PR #14) |
| **EMP-B** | ✅ merged `main` (PR #20 + #21 API) |
| **Profile cleanup** | ✅ **P1 pass** — chờ merge PR / **P2** |

---

## Profile cleanup — gỡ upload PDF legacy (`profile.php`)

- **Nhánh:** `feature/profile-p1-cleanup`
- **Mục tiêu:** Apply chỉ CV online — `candidates.cv_path` không còn upload mới

### Tiến độ

- [x] Phân tích + đề xuất P1/P2
- [x] **P1** — gỡ upload PDF; hub CV online; banner legacy `cv_path`
- [x] **`「P1 pass」`** — 2026-06-08
- [ ] **P2** — migration/deprecate `cv_path` DB (backlog)
- [ ] Merge PR → `main`

### Quy trình

```text
P1 pass → merge PR profile-p1-cleanup → (tuỳ chọn) P2 cv_path migration.
```

---

## Phase EMP-B (đã đóng)

- Merge PR #20 (CLI/UI) + PR #21 (FastAPI driver)
- Plan: `docs/phase-emp-b-plan.md`

---

## Phase EMP-A (đã đóng)

- Merge PR #14 @ `8bd34ab`

---

## Phase CV-F (đã đóng)

- Merge PR #13 · F7/F8 bỏ qua
