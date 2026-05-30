# Pre-Phase 2 Structure Audit — TopCV Lite

> Ngày: **2026-05-29**  
> Mục đích: Đánh giá cấu trúc trước Phase 2 — **không sửa code** trong bước audit này.

---

## 1. Cấu trúc hiện tại đang ổn ở đâu

| Khu vực | Đánh giá | Ghi chú |
|---------|----------|---------|
| Phân tách theo vai trò | ✅ Tốt | `admin/`, `employer/`, `candidate/` + public root |
| Luồng nghiệp vụ cốt lõi | ✅ Chạy E2E | Đăng ký → duyệt NTD → đăng tin → duyệt tin → apply → xử lý hồ sơ |
| PDO prepared statements | ✅ Phần lớn | Pattern ổn cho đồ án |
| Helper tái sử dụng | ✅ Đang hình thành | `csrf`, `upload_validate`, `job_rules`, `html_content`, `location_picker` |
| Config local | ✅ | `db.local.php` không commit |
| Tài liệu & roadmap | ✅ Rất đầy đủ | Phase 1→5, feature reviews, project-memory |
| Git workflow | ✅ | Branch feature + PR + commit theo phase |
| Migration | ✅ | `docs/migrations/` + script PHP UTF-8 (Windows) |

**Kết luận:** Đủ chuẩn **MVP monolith PHP** cho đồ án; không cần rewrite framework.

---

## 2. Technical debt cần biết trước Phase 2

| # | Nợ | Mức | Ảnh hưởng Phase 2 |
|---|-----|-----|-------------------|
| D1 | Logic nằm trong file page (God page) | Trung bình | Soft delete / status dễ copy-paste sai |
| D2 | `users.status` (tinyint) trộn “duyệt employer” | Cao | Phase 2 tách `account_status` vs `approval_status` |
| D3 | Xóa bằng GET `?delete=` | Trung bình | Xung đột với soft delete — cần POST+CSRF |
| D4 | Auth guard không thống nhất | Trung bình | Employer có `auth_check`; admin/candidate rải rác |
| D5 | Hai layout header (public vs admin) | Thấp | Thêm menu Phase 2 phải sửa 2 chỗ |
| D6 | `$base_url` hard-code `/topcv_lite/` | Thấp | Deploy khác path phải sửa tay |
| D7 | Schema drift (`companies` fields) | Trung bình | Cần migration trước khi mở rộng company |
| D8 | Không có audit log / moderation log | Cao (Phase 2 scope) | Cần bảng + service mới |
| D9 | `system-overview.md` / `current-state.md` lệch thực tế một phần | Thấp | Cập nhật khi bắt Phase 2 |
| D10 | Chưa có layer service/repository | Trung bình | Phase 2 nên bắt đầu gom logic mới vào đây |

---

## 3. Những chỗ chưa sửa ngay (cố ý để Phase 2)

| Chỗ | Lý do hoãn |
|-----|------------|
| GET delete (`admin/jobs.php`, `categories.php`, `locations.php`, `employer/manage-jobs.php`) | Sửa cùng lúc chuyển POST + soft delete |
| Tách `users.status` | Thuộc nhóm Phase 2 đầu — migration DB |
| Gom auth guard một file | Làm khi thêm rule trạng thái tài khoản |
| Refactor toàn bộ page sang service | Chỉ **logic mới / logic sửa** — không đụng page ổn định |
| API REST / router | Ngoài scope đồ án MVP |
| Laravel / Composer autoload PSR-4 đầy đủ | Không rewrite |

---

## 4. Quy tắc bắt buộc từ Phase 2 trở đi

1. **Mọi thay đổi trạng thái dữ liệu** → POST + CSRF (`docs/coding-conventions.md`).  
2. **Rule nghiệp vụ job/user/application** → không nhân bản trong 5 file; đưa vào `includes/services/` hoặc `*_rules.php`.  
3. **Schema change** → migration SQL + cập nhật `topcv_lite.sql`.  
4. **Mini-plan → user confirm → code → test → log → Git checkpoint** (giữ quy trình Phase 1).  
5. **Soft delete** → không `DELETE` cứng trừ admin “xóa vĩnh viễn” có confirm riêng.  
6. **Trạng thái** → enum/cột rõ tên; không overload `status` một cột cho nhiều nghĩa.  
7. **Docs** → cập nhật `project-memory` sau mỗi nhóm pass.

---

## 5. Danh sách file dễ bị ảnh hưởng trong Phase 2

### 5.1 Status model (`account_status` / `approval_status`)

| File | Lý do |
|------|-------|
| `topcv_lite.sql` | Thêm/sửa cột |
| `register.php` | Employer mặc định chờ duyệt |
| `login.php` | Chặn login nếu account chưa active |
| `admin/users.php` | Duyệt / khóa tài khoản |
| `employer/auth_check.php` | Kiểm tra employer đã được duyệt |
| `includes/header.php` | UX theo trạng thái |

### 5.2 Job / company lifecycle & soft delete

| File | Lý do |
|------|-------|
| `employer/job-create.php`, `job-edit.php` | Trạng thái draft/hidden |
| `employer/manage-jobs.php` | Ẩn/xóa mềm thay GET delete |
| `admin/jobs.php` | Moderation + badge trạng thái |
| `jobs.php`, `index.php`, `job-detail.php` | Filter không hiện bản ghi đã xóa |
| `apply.php` | Chặn apply job không còn hiệu lực |
| `includes/job_rules.php` | Mở rộng rule “mở nhận hồ sơ” |

### 5.3 Saved jobs / notification / moderation log

| File | Lý do |
|------|-------|
| `job-detail.php` | Nút lưu tin |
| `candidate/my-jobs.php` | Danh sách đã lưu / đã apply |
| `admin/jobs.php` | Log hành động duyệt/từ chối |
| (mới) `includes/services/SavedJobService.php` | Logic lưu tin |
| (mới) bảng `saved_jobs`, `moderation_logs`, `notifications` | Schema |

### 5.4 Helpers có sẵn — mở rộng, không xóa

| File |
|------|
| `includes/csrf.php` |
| `includes/job_rules.php` |
| `includes/html_content.php` |
| `includes/upload_validate.php` |

---

## 6. Ma trận rủi ro nếu bỏ qua chuẩn hóa

| Rủi ro | Hậu quả |
|--------|---------|
| Mỗi page tự query `status` | Lệch logic public vs admin vs employer |
| Soft delete không thống nhất | Tin “ma” vẫn hiện trên `jobs.php` |
| Tiếp tục GET delete | CSRF + không audit được |
| Không migration | DB local vs production lệch schema |

---

## Tham chiếu

- `docs/coding-conventions.md`  
- `docs/architecture-standardization-plan.md`  
- `docs/phase-2-mini-plan.md`  
- `docs/master-refactor-roadmap.md` (Phase 2 — Business Logic Fixes)
