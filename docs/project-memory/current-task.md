# Current Task — TopCV Lite

> Cập nhật: **2026-05-29**

---

## Trạng thái

**Phase 1 Critical Fixes:** ✅ Hoàn tất

**Phase 1.1 — Logic nghiệp vụ job:** ✅ **PASS** (user xác nhận 2026-05-29)

**Phase 2:** Sẵn sàng bắt đầu — chọn scope từ `docs/master-refactor-roadmap.md`

---

## Phase 1.1 — đã implement

| Nhóm | Nội dung | Status |
|------|----------|--------|
| 1.1-A | `job_validate_deadline()` + `min` date trên form | ✅ |
| 1.1-B | `job_is_expired`, chặn apply, badge admin, banner job-detail | ✅ |
| 1.1-C | `admin/locations.php`, seed 36, `run-phase-1-1-locations.php` | ✅ |

**Helper:** `includes/job_rules.php`

**Docs:** `docs/phase-1-1-plan.md`

---

## Bước tiếp theo (user)

1. Test theo checklist trong `docs/phase-1-1-plan.md`
2. Reply **「1.1 pass」** hoặc báo lỗi cụ thể
3. Git (tự commit):

```bash
git checkout -b feature/phase-1-1-job-logic
git add includes/job_rules.php employer/job-create.php employer/job-edit.php employer/manage-jobs.php job-detail.php apply.php admin/jobs.php admin/locations.php admin/includes/header.php topcv_lite.sql docs/
git commit -m "phase 1.1: deadline, expiry, locations seed + admin CRUD"
```

4. Sau pass → chọn scope Phase 2 từ `docs/master-refactor-roadmap.md`

**Git:** User tự commit/push; AI chỉ hướng dẫn lệnh.
