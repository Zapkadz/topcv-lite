# EMP-A — Checklist tiến độ (Sàng lọc ứng viên)

> **Mục đích:** Theo dõi tiến độ từng khối A0→A3. AI đọc file này + `docs/phase-emp-a-plan.md` khi chat mới.  
> **Plan chi tiết:** `docs/phase-emp-a-plan.md`  
> **Nhánh:** `feature/phase-emp-a-screening`  
> **Cập nhật lần cuối:** 2026-06-06

---

## Quy trình bắt buộc (mỗi khối A)

```text
AI làm 1 khối A → báo file + hướng test → USER test → USER gửi 「Ax pass」
→ (tuỳ chọn) USER yêu cầu commit → mới sang khối A tiếp
```

**AI không được:** sửa nặng `manage-jobs.php`; làm AI ranking; làm VIP; làm nhiều khối A một lúc; commit khi user chưa yêu cầu.

---

## Trạng thái tổng

| Mục | Giá trị |
|-----|---------|
| Phase | EMP-A — Employer screening foundation |
| Nhánh | `feature/phase-emp-a-screening` |
| User confirm plan | ✅ **`「xác nhận EMP-A」`** — 2026-06-06 |
| **Khối hiện tại** | **A2** — `job_candidates.php` (chờ **`「bắt đầu A2」`**) |
| Phụ thuộc | CV-C apply snapshot ✅ · job soft delete ✅ |
| Defer | AI ranking → **EMP-B** · VIP → sau |

### Ghi chú thiết kế đã chốt

- **Hết hạn** = không nhận apply mới; **vẫn** xử lý CV đã nộp.
- Hub **2 section:** Đang tuyển / Hết hạn còn CV.
- **`manage-jobs.php`** = lifecycle tin — **không** gắn screening.
- **`applicants.php`** = Hộp thư CV phẳng — **giữ**, không xóa phase A.
- Dashboard: thay card **Lượt xem tin** → **Sàng lọc ứng viên** (số = pending tổng).
- Quick action **Tìm ứng viên** → `candidate_screening.php`.

---

## Bảng tiến độ nhanh

| Khối | Mô tả ngắn | Code | User test | User confirm | Commit |
|------|------------|------|-----------|--------------|--------|
| A0 | Plan + checklist + nhánh | ✅ | — | ✅ | ⬜ |
| A1 | Hub screening + dashboard card | ✅ | ✅ | ✅ | ⬜ |
| A2 | job_candidates.php + bảo mật | ⬜ | ⬜ | ⬜ | ⬜ |
| A3 | Link + reuse CV modal + test | ⬜ | ⬜ | ⬜ | ⬜ |

---

## A0 — Plan & nhánh (~0.25 ngày)

| # | Làm gì | File | Verify |
|---|--------|------|--------|
| A0.1 | Plan phase | `docs/phase-emp-a-plan.md` | User đọc + confirm |
| A0.2 | Checklist | `docs/project-memory/phase-emp-a-checklist.md` | File này |
| A0.3 | Nhánh git | `feature/phase-emp-a-screening` | `git branch` |
| A0.4 | current-task | `docs/project-memory/current-task.md` | Trạng thái EMP-A |

**Pass A0:** User **`「xác nhận EMP-A」`** ✅

---

## A1 — Hub Sàng lọc + dashboard (~0.5 ngày)

| # | Làm gì | File | Verify |
|---|--------|------|--------|
| A1.1 | Helper filter/sort 2 section | `includes/employer_screening_rules.php` | SQL đúng định nghĩa plan §4 |
| A1.2 | Query jobs + `total_apps`, `pending_apps` | Repository hoặc `ApplicationService` | COUNT per job |
| A1.3 | Trang hub 2 section | `employer/candidate_screening.php` | UI 2 bảng |
| A1.4 | Dashboard card thay Lượt xem tin | `employer/dashboard.php` | Link + số pending |
| A1.5 | Quick action Tìm UV → screening | `employer/dashboard.php` | Link đúng |

**Pass A1:** Dashboard → screening → thấy tin đúng section + số UV; không tin deleted/pending admin.

---

## A2 — Chi tiết theo job (~0.5 ngày)

| # | Làm gì | File | Verify |
|---|--------|------|--------|
| A2.1 | `assertJobOwnedByCompany()` | `ApplicationService.php` | job lạ → null/404 |
| A2.2 | `listApplicationsForJob()` | `ApplicationService.php` | Chỉ app của job+company |
| A2.3 | Trang chi tiết UV | `employer/job_candidates.php` | Bảng + banner hết hạn |
| A2.4 | POST đổi status (CSRF) | `job_candidates.php` | Giống applicants.php |
| A2.5 | AI placeholder banner | `job_candidates.php` | Text only, no API |

**Pass A2:** Xem ứng viên đúng tin; employer B không xem job A; banner khi hết hạn.

---

## A3 — Hoàn thiện & regression (~0.25 ngày)

| # | Làm gì | File | Verify |
|---|--------|------|--------|
| A3.1 | Link CV snapshot | reuse `applicant-cv-snapshot.php` | Modal/tab OK |
| A3.2 | Breadcrumb / quay lại screening | `job_candidates.php` | UX |
| A3.3 | `applicants.php` không regression | manual | Hộp thư vẫn OK |
| A3.4 | `manage-jobs.php` không regression | manual | Sửa/xóa tin OK |

**Pass A3 / EMP-A:** Full flow checklist plan §10 tick hết → **`「EMP-A pass」`** → PR.

---

## Regression nhanh (sau EMP-A merge)

- [ ] Candidate apply job → employer thấy trên screening  
- [ ] Employer đổi status → candidate thấy (nếu có UI)  
- [ ] Tin soft delete → biến khỏi screening  
- [ ] CV snapshot employer xem OK  

---

## Checkpoint log

| Ngày | Sự kiện |
|------|---------|
| 2026-06-06 | User **`「xác nhận EMP-A」`** — plan hết hạn ≠ hết xử lý; 2 section hub |
| 2026-06-06 | User **`「A1 pass」`** — hub screening + dashboard card |

---

## Lệnh dev (sau khi code)

```powershell
# Chạy local
http://localhost/topcv_lite/employer/dashboard.php
http://localhost/topcv_lite/employer/candidate_screening.php
http://localhost/topcv_lite/employer/job_candidates.php?job_id=1
```
