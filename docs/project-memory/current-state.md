# Current State — TopCV Lite

> Cập nhật lần cuối: **2026-05-29**  
> Mục đích: AI mới đọc file này để biết dự án đang ở đâu, không cần hỏi lại.

---

## 1. Tổng quan dự án

| Hạng mục | Chi tiết |
|----------|----------|
| Tên | TopCV Lite |
| Loại | Job Portal / Recruitment Platform (MVP) |
| Stack | PHP thuần (server-rendered), PDO, MariaDB/MySQL, Bootstrap 5, session auth |
| Môi trường | XAMPP local — `http://localhost/topcv_lite/` |
| DB | `topcv_lite` trên `127.0.0.1`, user `root`, password rỗng |
| Kiến trúc | Monolith page-based, không framework MVC, không API layer |

**Vai trò người dùng:** `candidate`, `employer`, `admin` (cột `users.role` + `users.status` cho duyệt employer).

**Bảng DB chính:** `users`, `candidates`, `companies`, `jobs`, `applications`, `categories`, `locations`.

**Chưa có thật:** AI matching, CV parsing, recommendation, payment/subscription, chat, notification persistence, saved jobs backend, queue/cache.

---

## 2. Các file đã sửa (code)

| File | Thay đổi | Phase/Nhóm |
|------|----------|------------|
| `topcv_lite.sql` | Thêm `UNIQUE KEY uniq_job_candidate (job_id, candidate_id)` trên bảng `applications` | Phase 1 / Nhóm 1 |
| `apply.php` | Bắt `PDOException` duplicate key 1062; validate CSRF `apply_job_form`; ép kiểu `job_id` int | Nhóm 1 + 2A |
| `candidate/profile.php` | Query `$profile` trước render; fallback mảng rỗng; validate CSRF `candidate_profile_form` | Nhóm 4 + 2A |
| `job-detail.php` | Include `includes/csrf.php`; hidden `csrf_token` trong form apply modal | Nhóm 2A |

---

## 3. Các file đã tạo (code)

| File | Mục đích |
|------|----------|
| `includes/csrf.php` | Helper CSRF: `csrf_token($form_key)`, `csrf_validate($form_key, $token)` dùng session + `hash_equals` |

**Nhóm 2B:** CSRF trên `login.php`, `register.php`, `employer/company.php`, `job-create.php`, `job-edit.php`, `applicants.php`, `admin/users.php`, `jobs.php`, `categories.php` — ✅ test pass 2026-05-29.

---

## 4. Các file tài liệu đã tạo

### Audit tổng thể
- `docs/system-overview.md`
- `docs/database-review.md`
- `docs/database-improvement-plan.md`
- `docs/ai-system-review.md`
- `docs/ai-improvement-roadmap.md`
- `docs/production-readiness-report.md`
- `docs/master-refactor-roadmap.md`
- `docs/phase-1-critical-fixes-plan.md`
- `docs/dev-learning-log.md`

### Feature reviews (`docs/features/`)
- `auth-review.md`, `authorization-review.md`, `role-management-review.md`
- `user-profile-review.md`, `recruiter-profile-review.md`, `company-profile-review.md`
- `cv-upload-review.md`, `resume-parsing-review.md`, `ai-matching-review.md`
- `job-posting-review.md`, `job-searching-review.md`, `recommendation-system-review.md`
- `apply-job-review.md`, `saved-jobs-review.md`, `notification-system-review.md`
- `chat-system-review.md`, `admin-dashboard-review.md`, `analytics-review.md`
- `payment-subscription-review.md`, `subscription-review.md`
- `report-system-review.md`, `moderation-review.md`, `search-filter-sort-review.md`
- `email-flow-review.md`, `file-storage-review.md`
- `api-security-rate-limit-review.md`, `logging-error-handling-caching-queue-review.md`

### Project memory (thư mục này)
- `docs/project-memory/current-state.md` (file này)
- `docs/project-memory/current-task.md`
- `docs/project-memory/audit-progress.md`
- `docs/project-memory/session-handoff.md` *(đề xuất thêm — hướng dẫn nhanh cho chat mới)*
- `docs/project-memory/known-blockers.md` *(đề xuất thêm — blocker đang active)*

---

## 5. Các vấn đề đã phát hiện (tóm tắt audit)

### Critical
- Thiếu `UNIQUE(job_id, candidate_id)` trên `applications` → race condition apply trùng *(đã fix schema file + code, nhưng DB live bị hỏng — xem blocker)*
- Bảng `applications` trên DB live bị corruption error 1932 *(BLOCKER hiện tại)*
- Schema drift: `company-detail.php` đọc `companies.phone`, `email`, `scale` nhưng DB dump không có các cột này
- Thiếu CSRF toàn hệ thống *(đang fix từng nhóm — mới xong 2A)*

### High
- `$profile` chưa khởi tạo ở `candidate/profile.php` *(đã fix)*
- Upload chỉ check extension, không check MIME/size
- Auth check không đồng nhất giữa các route
- Không có AI thật dù product positioning có AI

### Medium/Low
- Saved jobs chỉ UI (icon tim), không có backend
- Pagination/search dùng `%LIKE%`, thiếu index composite
- Config DB hard-code trong `config/db.php`
- Không có test tự động, CI/CD, logging tập trung

---

## 6. Các vấn đề đã fix

| Vấn đề | Cách fix | Trạng thái test |
|--------|----------|-----------------|
| Apply trùng (race condition) | UNIQUE constraint + catch duplicate trong `apply.php` | User test Nhóm 1 ✅ |
| `$profile` undefined ở profile page | Query + fallback trong `candidate/profile.php` | User test Nhóm 4 ✅ |
| CSRF cho apply + profile | `includes/csrf.php` + token/validate 2 luồng | User test Nhóm 2A ✅ (2026-05-29) |
| DB `applications` corruption 1932 | Recreate bảng trên DB live | Resolved — test 2A pass |

---

## 7. Các vấn đề chưa fix

| Ưu tiên | Vấn đề | Ghi chú |
|---------|--------|---------|
| P1 | CSRF cho các form còn lại (Nhóm 2B) | ✅ Done (2026-05-29) |
| P1 | Upload hardening (Nhóm 3) | MIME, size limit |
| P1 | Schema drift companies (phone/email/scale) | Chưa làm |
| P2 | Các phase 2–5 trong master roadmap | Chưa bắt đầu implement |

---

## 8. Các test đã thực hiện (user xác nhận)

- **Nhóm 1:** Apply lần đầu OK; apply lại cùng job bị chặn; apply job khác OK; `my-jobs` không trùng bản ghi.
- **Nhóm 4:** Candidate chưa có profile mở trang OK; lưu profile OK; reload hiển thị đúng; candidate có profile hiển thị đúng.
- **Nhóm 2A (2026-05-29):** Apply OK; profile OK; CSRF sai/thiếu bị chặn. **PASS**

---

## 9. Các test chưa thực hiện

- Nhóm 2B CSRF (login, register, employer/*, admin/*).
- Nhóm 3 upload hardening.

---

## 10. Quy trình làm việc user yêu cầu (BẮT BUỘC tuân thủ)

1. Không sửa nhiều phần cùng lúc — mỗi lần một nhóm lỗi nhỏ.
2. Trước khi sửa: viết mini-plan (lỗi, file, nguyên nhân, ảnh hưởng, cách fix, rủi ro, cách test).
3. **Chờ user xác nhận** mới được sửa code.
4. Sau sửa: cập nhật `docs/dev-learning-log.md`.
5. Hướng dẫn test thủ công chi tiết.
6. Không chuyển nhóm tiếp theo nếu nhóm hiện tại chưa test xong.
7. Trước khi kết thúc phiên: cập nhật `docs/project-memory/*`.
8. **Git checkpoint** sau mỗi phase/nhóm xong test — xem `docs/project-memory/git-checkpoint-workflow.md`. Không chuyển phase tiếp theo nếu chưa checkpoint (trừ khi user đồng ý bỏ qua).

**Ngôn ngữ trả lời:** Tiếng Việt.

---

## 11. Tham chiếu nhanh

- Kế hoạch Phase 1: `docs/phase-1-critical-fixes-plan.md`
- Log học tập / fix: `docs/dev-learning-log.md`
- Roadmap tổng: `docs/master-refactor-roadmap.md`
- Handoff nhanh chat mới: `docs/project-memory/session-handoff.md`
