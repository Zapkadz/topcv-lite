# Phase 2 — Mini Plan (Business Logic Fixes)

> **Trạng thái:** Chờ user confirm từng nhóm trước khi code.  
> **Tiền đề:** Phase 1 + 1.1 ✅ | Docs chuẩn hóa ✅  
> **Kiến trúc:** Page Controller + Service/Repository (`docs/architecture-standardization-plan.md`)

---

## Mục tiêu Phase 2 (từ master roadmap)

1. Tách **`account_status`** và **`approval_status`** (employer / user).  
2. **Soft delete** + lifecycle cho job / company / user.  
3. **Saved jobs**, notification persistence (cơ bản), **moderation logs**.

**Không** rewrite framework. **Không** đổi URL.

---

## Thứ tự nhóm đề xuất

| Nhóm | Tên | Priority | Lý do thứ tự |
|------|-----|----------|--------------|
| **2A** | Status model (user/employer) | P0 | Nền cho login, duyệt NTD, guard |
| **2B** | Job lifecycle + soft delete | P0 | Dính `job_rules`, public listing, manage-jobs |
| **2C** | Moderation logs | P1 | Admin audit — dùng sau khi 2A/2B ổn |
| **2D** | Saved jobs (+ notification tối thiểu) | P1 | Feature candidate — schema riêng |

*Có thể gộp 2C+2D nếu muốn ít PR hơn — vẫn test từng khối.*

---

## Nhóm 2A — Status model (account vs approval)

### Phạm vi

- Migration: tách ý nghĩa `users.status` hiện tại (duyệt employer) thành cột rõ ràng, ví dụ:
  - `account_status`: `active` | `suspended` | `pending_verification`
  - `employer_approval_status`: `pending` | `approved` | `rejected` (chỉ role employer) hoặc bảng phụ
- `UserModerationService` + `UserRepository`
- `includes/guards/require_employer.php` — chặn employer chưa approved
- Cập nhật: `register.php`, `login.php`, `admin/users.php`, `employer/auth_check.php`

### Không làm trong 2A

- Soft delete job  
- Saved jobs  

### File dự kiến

`topcv_lite.sql`, `docs/migrations/phase-2a-user-status.sql`, `includes/services/UserModerationService.php`, `includes/repositories/UserRepository.php`, `register.php`, `login.php`, `admin/users.php`, `employer/auth_check.php`

### Test checklist (user)

- [ ] Employer mới đăng ký: không đăng tin cho đến khi admin duyệt  
- [ ] Admin duyệt/từ chối: trạng thái lưu đúng  
- [ ] Login employer pending: thông báo rõ  
- [ ] Candidate không bị ảnh hưởng  

### Commit gợi ý

`phase 2: tách trạng thái tài khoản employer (nhóm 2A)`

---

## Nhóm 2B — Job lifecycle + soft delete

### Phạm vi

- Cột `jobs.deleted_at` (nullable) hoặc `is_deleted` + chuẩn query “chỉ tin còn sống”
- `JobService` + `JobRepository` — soft delete, restore (optional), hidden vs approved
- Đổi `employer/manage-jobs.php` xóa **GET** → **POST** + CSRF
- Filter `jobs.php`, `index.php`, `job-detail.php`, `apply.php` — không thấy tin đã xóa
- Mở rộng `job_rules` / `JobService::isOpenForApply()`

### File dự kiến

`employer/manage-jobs.php`, `employer/job-create.php`, `employer/job-edit.php`, `admin/jobs.php`, `jobs.php`, `index.php`, `job-detail.php`, `apply.php`, `includes/services/JobService.php`, `includes/repositories/JobRepository.php`

### Test checklist

- [ ] Employer “xóa” tin → không hiện public, vẫn thấy trong manage (tab đã xóa hoặc badge)
- [ ] Admin duyệt tin đã xóa mềm — hành vi rõ ràng
- [ ] Apply vào tin đã xóa → bị chặn  
- [ ] Regression deadline/expiry Phase 1.1  

### Commit gợi ý

`phase 2: soft delete và lifecycle job (nhóm 2B)`

---

## Nhóm 2C — Moderation logs

### Phạm vi

- Bảng `moderation_logs` (admin_id, entity_type, entity_id, action, note, created_at)
- Ghi log khi admin duyệt/từ chối job và employer
- Hiển thị lịch sử tối thiểu trên `admin/jobs.php` hoặc trang `admin/moderation-log.php` (mới, tùy scope)

### Test checklist

- [ ] Mỗi lần duyệt/từ chối có 1 dòng log  
- [ ] Không log khi CSRF fail  

### Commit gợi ý

`phase 2: moderation audit log (nhóm 2C)`

---

## Nhóm 2D — Saved jobs (+ notification cơ bản)

### Phạm vi

- Bảng `saved_jobs` (candidate_id, job_id, created_at), UNIQUE (candidate_id, job_id)
- `SavedJobService` — toggle lưu / bỏ lưu
- UI: `job-detail.php` (nút lưu), `candidate/my-jobs.php` (tab “Đã lưu”)
- Notification persistence (tối thiểu): bảng `notifications` + đếm chưa đọc (optional nếu hết giờ — ghi rõ defer)

### Test checklist

- [ ] Lưu / bỏ lưu không trùng bản ghi  
- [ ] Chỉ candidate được lưu  
- [ ] Tin hết hạn / đã xóa: hành vi thống nhất (ẩn hoặc badge “không còn hiệu lực”)  

### Commit gợi ý

`phase 2: saved jobs cho candidate (nhóm 2D)`

---

## Quy trình mỗi nhóm (giống Phase 1)

1. AI gửi **mini-plan nhóm** (file này đã có — user confirm: `「xác nhận 2A」`)  
2. Code + migration  
3. User test → `「2A pass」`  
4. Cập nhật `dev-learning-log.md`, `project-memory/*`  
5. Git checkpoint → PR → merge  

---

## Git checkpoint Phase 1.1 (tham chiếu)

User đã commit Phase 1.1 — **không cần commit lại code 1.1**.

**Checkpoint docs (tuỳ chọn, 1 commit):**

```powershell
git add docs/coding-conventions.md docs/pre-phase-2-structure-audit.md docs/architecture-standardization-plan.md docs/phase-2-mini-plan.md docs/project-memory/
git commit -m "docs: conventions, pre-phase-2 audit, architecture plan, phase 2 mini-plan"
git push
```

---

## Confirm tiếp theo

Reply một trong các cách:

- `「xác nhận docs chuẩn hóa」` — chỉ merge docs, chưa code Phase 2  
- `「xác nhận 2A」` — bắt đầu implement Nhóm 2A  

---

## Tham chiếu

- `docs/master-refactor-roadmap.md`  
- `docs/coding-conventions.md`  
- `docs/pre-phase-2-structure-audit.md`  
- `docs/architecture-standardization-plan.md`
