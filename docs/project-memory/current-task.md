# Current Task — TopCV Lite

> Cập nhật: **2026-06-09**

---

## Trạng thái

| Phase | Status |
|-------|--------|
| CV-A … CV-F | ✅ merged `main` |
| **EMP-A** | ✅ merged `main` (PR #14) |
| **EMP-B** | ✅ merged `main` (PR #20 + #21 API) |
| **Profile cleanup** | ✅ merged `main` (PR #22) |
| **Admin Taxonomy** | ✅ merged `main` (PR #23) |
| **CV-G** | ✅ Refactor xong — chờ test lại / commit |

---

## CV-G — Template picker trước CV builder

- **Plan:** `docs/phase-cv-g-plan.md`
- **Refactor plan:** `docs/refactoring/phase-CV-G-refactoring-plan.md` (đã refactor)
- **Nhánh:** `feature/cv-g-template-picker`
- **Trạng thái:** Test pass + refactor xong — chờ test smoke / commit

---

## Admin Taxonomy Suggestions (đã đóng)

- **Merge:** PR #23 @ `7a4db0a`
- **Prompt:** `docs/cursor-prompt-topcv-lite-admin-taxonomy-suggestions.md`
- **Mục tiêu:** Admin import/duyệt taxonomy suggestions → DB → export `skills_merged.json` → AI screening dùng merged path

### Tiến độ

- [x] **T0** — SQL migration + `schema_ai_taxonomy.php` + `config/ai_taxonomy.example.php`
- [x] **T1** — `AiTaxonomyRepository` + `AiTaxonomyService` (import/merge/export atomic)
- [x] **T2** — Admin UI: list / import / review / export
- [x] **T3** — AI screening: `taxonomy_path` trong API payload + CLI fallback merged path
- [x] Test manual — migration, export merged 57 skills, schema OK
- [x] **`「T pass」`** — 2026-06-09
- [x] Merge PR #23 → `main` @ `7a4db0a`

### File chính

| File | Vai trò |
|------|---------|
| `docs/migrations/phase-admin-taxonomy.sql` | Schema 3 bảng |
| `docs/migrations/migrate-phase-admin-taxonomy.php` | Chạy migration |
| `includes/services/AiTaxonomyService.php` | Import, decision, merge, export |
| `admin/ai_taxonomy_suggestions.php` | Danh sách + filter |
| `admin/ai_taxonomy_suggestion_import.php` | Import config path / upload |
| `admin/ai_taxonomy_suggestion_review.php` | Duyệt suggestion |
| `admin/ai_taxonomy_export.php` | POST export merged JSON |

### Paths runtime

- Base: `C:\SEMANTIC_SKILLS_RESUME\data\taxonomy\skills.json`
- Queue: `C:\SEMANTIC_SKILLS_RESUME\outputs\taxonomy_suggestions.json`
- Merged (AI dùng): `C:\topcv_ai_runtime\taxonomy\skills_merged.json`

### Test nhanh

```powershell
php docs/migrations/migrate-phase-admin-taxonomy.php
php docs/migrations/_test-admin-taxonomy.php
# Admin: http://localhost/topcv_lite/admin/ai_taxonomy_suggestions.php
```

### T đã verify

- Migration 3 bảng OK · export `C:\topcv_ai_runtime\taxonomy\skills_merged.json`
- AI screening dùng `ai_taxonomy_effective_screening_path()` (merged → base fallback)

---

## Profile cleanup (đã đóng)

- Merge PR #22 @ `5f14ea4`

---

## Phase EMP-B (đã đóng)

- Merge PR #20 (CLI/UI) + PR #21 (FastAPI driver)
