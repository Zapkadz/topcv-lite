# Phase 1.1 — Logic nghiệp vụ job

> Hoàn tất implement: **2026-05-29**

## Mục tiêu

Sửa 3 lỗ hổng logic trước Phase 2: deadline quá khứ, tin hết hạn vẫn apply, danh sách địa điểm lỗi thời.

## Nhóm đã làm

| Nhóm | Nội dung | File chính |
|------|----------|------------|
| 1.1-A | Validate deadline ≥ hôm nay | `includes/job_rules.php`, `employer/job-create.php`, `job-edit.php` |
| 1.1-B | Chặn apply khi hết hạn; badge admin | `job-detail.php`, `apply.php`, `admin/jobs.php`, `employer/manage-jobs.php` |
| 1.1-C | 36 locations + admin CRUD | `admin/locations.php`, migration, `topcv_lite.sql` |

## Quy tắc hết hạn

- `deadline` là ngày (DATE): còn hiệu lực **trong cả ngày** deadline.
- Hết hạn khi `deadline < CURDATE()` (so sánh `Y-m-d`).

## Migration locations

```bash
php docs/migrations/run-phase-1-1-locations.php
```

Map cũ → mới: Hà Nội→TP. Hà Nội, HCM→TP. Hồ Chí Minh, Đà Nẵng→TP. Đà Nẵng, Cần Thơ→TP. Cần Thơ, Toàn Quốc→Remote.

## Test checklist (user)

- [ ] Employer: không tạo/sửa job với deadline quá khứ (gợi ý dưới ô ngày + SweetAlert + server)
- [ ] Địa điểm: ô gõ tìm + danh sách cuộn (~12 dòng) trên job-create / job-edit
- [ ] Job đã hết hạn: `job-detail` không mở modal apply; `apply.php` POST bị chặn
- [ ] Admin `jobs.php`: tin `approved` hết hạn → badge **Hết hạn**
- [ ] Admin `locations.php`: thêm/sửa/xóa (xóa bị chặn nếu còn job)
- [ ] Dropdown địa điểm khi tạo job có đủ 36 mục

User báo **「1.1 pass」** khi xong.
