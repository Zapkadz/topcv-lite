# Phase 1 Critical Fixes Plan

## Mục tiêu của phase

Xử lý các lỗi mức Critical có thể gây:
- lỗi nghiệp vụ nghiêm trọng trong môi trường thật,
- lỗ hổng bảo mật cơ bản,
- dữ liệu sai hoặc trùng lặp khó kiểm soát.

Tuân thủ quy trình kiểm soát:
1. Mỗi lần chỉ xử lý một nhóm lỗi nhỏ.
2. Phải duyệt kế hoạch trước khi sửa.
3. Sửa xong mới chuyển nhóm tiếp theo.

---

## Nhóm 1 — Chặn trùng đơn ứng tuyển ở tầng Database

### Lỗi cần sửa
Hệ thống có thể phát sinh đơn ứng tuyển trùng cho cùng một ứng viên và cùng một job khi có thao tác đồng thời (double click, retry request, network race).

### File liên quan
- `topcv_lite.sql` (schema)
- `apply.php` (logic tạo đơn)
- `docs/database-review.md` (đã ghi nhận vấn đề)

### Nguyên nhân
Code chỉ check trùng ở tầng ứng dụng (`SELECT` trước `INSERT`) nhưng DB chưa có `UNIQUE (job_id, candidate_id)`.

### Ảnh hưởng thực tế
- Recruiter thấy trùng hồ sơ, khó xử lý.
- Analytics sai số lượng apply.
- Có thể bị spam tạo nhiều bản ghi.

### Cách fix đề xuất
1. Thêm unique constraint/composite unique index cho `applications(job_id, candidate_id)`.
2. Cập nhật `apply.php` để xử lý lỗi duplicate key thân thiện (thay vì lỗi kỹ thuật).
3. (Tùy chọn nâng cao) thêm transaction/idempotency token.

### Rủi ro
- Nếu DB hiện có dữ liệu trùng, migration sẽ fail.
- Cần xử lý dữ liệu trùng trước khi add unique.

### Cách test (sau khi sửa)
1. Dùng cùng một tài khoản candidate apply cùng một job 2 lần liên tiếp.
2. Kiểm tra DB chỉ có 1 bản ghi.
3. Kiểm tra UI hiển thị thông báo “đã ứng tuyển” rõ ràng.
4. Test lại apply bình thường với job khác để đảm bảo không ảnh hưởng flow hợp lệ.

---

## Nhóm 2 — Bổ sung CSRF protection cho form POST quan trọng

### Lỗi cần sửa
Các form POST hiện chưa có CSRF token, dễ bị tấn công CSRF.

### File liên quan (ưu tiên cao)
- `apply.php`
- `login.php`
- `register.php`
- `candidate/profile.php`
- `employer/company.php`
- `employer/job-create.php`
- `employer/job-edit.php`
- `employer/applicants.php`
- `admin/users.php`
- `admin/jobs.php`
- `admin/categories.php`

### Nguyên nhân
Chưa có cơ chế tạo/verify token CSRF tập trung.

### Ảnh hưởng thực tế
- Người dùng đăng nhập có thể bị ép thực hiện hành động ngoài ý muốn (đăng tin, sửa trạng thái, duyệt/từ chối...).

### Cách fix đề xuất
1. Tạo helper CSRF chung (generate + validate).
2. Gắn token vào từng form POST.
3. Validate token ở đầu luồng xử lý POST.
4. Trả thông báo lỗi rõ ràng khi token sai/hết hạn.

### Rủi ro
- Dễ làm hỏng form cũ nếu quên gắn token.
- Cần test đủ các form theo role.

### Cách test (sau khi sửa)
1. Submit form hợp lệ có token -> thành công.
2. Submit thiếu token -> bị chặn.
3. Submit token sai -> bị chặn.
4. Test regression các form quan trọng theo từng role.

---

## Nhóm 3 — Khóa lỗ hổng upload file CV/logo cơ bản

### Lỗi cần sửa
Upload hiện chủ yếu check extension, chưa check MIME type/size rõ ràng.

### File liên quan
- `apply.php`
- `candidate/profile.php`
- `employer/company.php`

### Nguyên nhân
Validation file chưa đủ chặt cho môi trường production.

### Ảnh hưởng thực tế
- Nguy cơ upload file độc hại đội lốt.
- Nguy cơ quá tải dung lượng/IO nếu file quá lớn.

### Cách fix đề xuất
1. Thêm whitelist MIME type thực tế bằng `finfo`.
2. Giới hạn kích thước file (ví dụ CV <= 2-5MB, logo <= 2MB).
3. Chuẩn hóa thông báo lỗi upload để user hiểu.
4. (Giai đoạn sau) cân nhắc antivirus scan và object storage.

### Rủi ro
- Có thể chặn nhầm một số file hợp lệ nếu MIME không nhất quán.
- Cần cân bằng giữa bảo mật và UX.

### Cách test (sau khi sửa)
1. Upload file hợp lệ đúng loại -> thành công.
2. Upload file giả đuôi (vd `.pdf` nhưng MIME khác) -> bị chặn.
3. Upload file quá dung lượng -> bị chặn.
4. Re-test flow apply/profile/company không bị vỡ.

---

## Nhóm 4 — Sửa lỗi runtime ở hồ sơ ứng viên (`$profile`)

### Lỗi cần sửa
Trong `candidate/profile.php`, biến `$profile` được dùng để render nhưng chưa thấy khởi tạo query tương ứng.

### File liên quan
- `candidate/profile.php`

### Nguyên nhân
Thiếu đoạn query lấy dữ liệu profile hiện tại trước khi render form.

### Ảnh hưởng thực tế
- Có thể báo lỗi PHP (undefined variable/index) khi mở trang profile.
- Gây gián đoạn flow cập nhật hồ sơ của ứng viên.

### Cách fix đề xuất
1. Thêm query lấy profile theo `user_id` trước phần HTML.
2. Gán mặc định an toàn khi chưa có bản ghi.
3. Đảm bảo luồng insert/update giữ nguyên hành vi nghiệp vụ.

### Rủi ro
- Nếu xử lý sai thứ tự include/session có thể phát sinh warning khác.

### Cách test (sau khi sửa)
1. Candidate chưa có profile mở trang -> không lỗi.
2. Candidate có profile mở trang -> dữ liệu hiển thị đúng.
3. Cập nhật profile + upload CV -> lưu thành công.

---

## Thứ tự triển khai đề xuất (không làm song song)

1. Nhóm 1: Unique apply constraint (ổn định dữ liệu lõi).
2. Nhóm 4: Fix runtime profile (đảm bảo flow ứng viên chạy ổn).
3. Nhóm 2: CSRF protection (đóng lỗ hổng bảo mật diện rộng).
4. Nhóm 3: Upload hardening (bảo mật file input).

Lý do thứ tự:
- Nhóm 1 và 4 tác động hẹp, dễ kiểm soát, giảm lỗi lõi ngay.
- Nhóm 2 và 3 tác động nhiều file, nên làm sau khi hệ lõi đã ổn.

---

## Checklist quy trình cho mỗi lần sửa (bắt buộc)

1. Chốt đúng **một nhóm lỗi nhỏ**.
2. Viết mini-plan trước khi sửa.
3. Bạn xác nhận -> mới sửa code.
4. Sửa xong cập nhật `docs/dev-learning-log.md`.
5. Mình gửi hướng dẫn test thủ công chi tiết.
6. Chỉ chuyển lỗi tiếp theo sau khi bạn test xong và xác nhận.
