# Phase CV-A — Schema + Service layer

> **Xác nhận CV-0:** ✅ — implement **2026-05-29** — ✅ **「CV-A pass」**

## Migration

http://localhost/topcv_lite/docs/migrations/migrate-phase-cv-a.php

hoặc: `php docs/migrations/run-phase-cv-a-structured.php`

## Phạm vi

- Bảng `cv_profiles`, `cv_educations`, `cv_experiences`, `cv_skills`
- `schema_cvs.php`, `cv_rules.php`
- `CvRepository`, `CvService` — CRUD, save children (transaction), `buildSnapshotJson()`
- Script smoke test: `docs/migrations/smoke-test-cv-a.php`

## Không làm (CV-B/C)

- `cv-manage.php`, `cv-builder.php`, UI đẹp
- `applications.cv_profile_id` / snapshot apply
- Section activities, certificates (CV-D)

## Test checklist

- [x] Migration CV-A
- [x] `smoke-test-cv-a.php` → tạo 2 CV test, data tách `cv_id` (chỉ script QA, không seed sản phẩm)
- [x] Chỉ 1 `is_primary` / candidate
- [x] User A không sửa CV user B (service từ chối)

## Git

`phase CV: schema và service CV structured (nhóm CV-A)`
