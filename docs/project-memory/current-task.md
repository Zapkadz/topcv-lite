# Current Task — TopCV Lite

> Cập nhật: **2026-06-09**

---

## Trạng thái

| Phase | Status |
|-------|--------|
| CV-A … CV-F | ✅ merged `main` |
| **EMP-A** | ✅ merged `main` (PR #14) |
| **EMP-B** | ✅ merged `main` (PR #20 + #21 API) |
| **Profile cleanup** | ✅ **P1 + P2 pass** — chờ merge PR → `main` |

---

## Profile cleanup — gỡ upload PDF legacy (`profile.php`)

- **Nhánh:** `feature/profile-p1-cleanup`
- **Mục tiêu:** Apply chỉ CV online — `candidates.cv_path` không còn upload mới

### Tiến độ

- [x] Phân tích + đề xuất P1/P2
- [x] **P1** — gỡ upload PDF; hub CV online; banner legacy `cv_path`
- [x] **`「P1 pass」`** — 2026-06-08
- [x] **P2** — migration `cv_path` → `cv_profiles.attachment_path` (`migrate-phase-profile-cv-path.php`)
- [x] **`「P2 pass」`** — 2026-06-09
- [ ] Merge PR → `main`

### Quy trình

```text
P1 pass → P2 pass → merge PR feature/profile-p1-cleanup → main.
```

### P2 đã verify

- Dry-run + migration CLI: 4/4 `cv_path` → `attachment_path`, pending = 0
- `profile.php`: banner legacy không còn sau migrate

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
