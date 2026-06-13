# Phase Job-A — Hiển thị số ứng viên đã ứng tuyển (job detail)

> **Trạng thái:** ✅ implemented — chờ test / commit

## Mục tiêu

Ứng viên xem **chi tiết tin tuyển dụng** (`job-detail.php`) thấy được **số người đã nộp hồ sơ** — tạo cảm giác mức độ cạnh tranh (tương tự “lượt xem”).

## Vị trí UI (theo mock user)

**Header tin** — hàng metadata cạnh địa điểm / hạn nộp / lượt xem:

```
📍 TP. Đà Nẵng  |  🕐 Hạn nộp: 20/08/2026  |  👁 4 lượt xem  |  👥 12 ứng viên đã ứng tuyển
```

Icon đề xuất: `fa-users` (Font Awesome, đã dùng trong project).

**Không thêm** sidebar “Thông tin chung” ở phase này (tránh trùng “Số lượng tuyển: 4 người”).

## Phạm vi kỹ thuật

| Hạng mục | Chi tiết |
|----------|----------|
| DB | Không migration — đếm từ bảng `applications` (`job_id`) |
| Service | `ApplicationService::countForJob(PDO $conn, int $jobId): int` |
| Page | `job-detail.php` — 1 query count + 1 dòng HTML |
| Quyền xem | Public (guest + candidate + employer đều thấy) |

### Query

```sql
SELECT COUNT(*) FROM applications WHERE job_id = ?
```

Mỗi candidate chỉ 1 application/tin (unique constraint hiện có) → count = số ứng viên thực tế.

### Copy hiển thị

| Count | Text |
|-------|------|
| 0 | `Chưa có ứng viên` |
| 1 | `1 ứng viên đã ứng tuyển` |
| n ≥ 2 | `{n} ứng viên đã ứng tuyển` |

## Không làm (defer)

- Cache / cột `application_count` trên `jobs` (chưa cần — traffic nhỏ)
- Hiển thị trên `jobs.php` / trang chủ (có thể phase sau)
- Ẩn số khi quá ít (vd. &lt; 5) — luôn hiện thật

## Test checklist

- [ ] Job chưa ai apply → “Chưa có ứng viên”
- [ ] Sau khi 1 UV apply → count tăng, F5 job detail đúng
- [ ] Guest (chưa login) vẫn thấy số
- [ ] Employer xem tin công khai vẫn thấy số (không lộ thông tin cá nhân)

## Git (khi pass)

`feat(job): show applicant count on public job detail page`
