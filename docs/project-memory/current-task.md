# Current Task — TopCV Lite

> Cập nhật: **2026-05-29**

---

## Trạng thái

**Phase 1 — Critical Fixes: ✅ HOÀN TẤT** (Nhóm 1, 4, 2A, 2B, 3 — tất cả test pass)

**Phase tiếp theo:** Phase 2 — Business Logic (`docs/master-refactor-roadmap.md`)

---

## Tiến độ Phase 1

| Nhóm | Nội dung | Test |
|------|----------|------|
| 1 | UNIQUE apply | ✅ |
| 4 | Profile runtime | ✅ |
| 2A | CSRF apply + profile | ✅ |
| 2B | CSRF còn lại | ✅ |
| 3 | Upload hardening | ✅ (2026-05-29) |

---

## Bước tiếp theo

1. Merge PR Nhóm 3 vào `main` (nếu chưa merge).
2. Khi bắt đầu Phase 2: reply yêu cầu scope (vd. schema `companies`, validation form...) → mini-plan → confirm → code.

---

## Ghi chú AI

- Không mở Phase 2 trước khi user chọn scope cụ thể.
- Quy trình: mini-plan → confirm → branch `feature/phase-2-...` → test → commit + PR.
