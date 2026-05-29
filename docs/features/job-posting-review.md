# Tổng quan chức năng
Employer tạo/sửa/xóa job qua module `employer/*`; admin duyệt ở `admin/jobs.php`.

# Flow hiện tại
Create/Edit job => `status=pending` => admin approve/reject.

# Business logic hiện tại
Có moderation cơ bản, có admin_note khi reject.

# Các vấn đề phát hiện
- Thiếu kiểm soát chất lượng nội dung tự động.
- Xóa cứng job trong `manage-jobs.php` và admin.

# Tình huống thực tế có thể fail
Recruiter đăng 1000 jobs spam gây ngập marketplace.

# Security issues
Thiếu anti-spam/rate limit khi đăng job.

# Performance issues
Listing job quản trị thiếu tối ưu khi data lớn.

# UX issues
Chưa có version history của tin đã sửa.

# Edge cases
Job hết hạn/ẩn/chặn công ty chưa có lifecycle rõ.

# Đề xuất cải thiện
Job lifecycle state machine + soft delete + anti-spam scoring.

# Mức độ ưu tiên:
- Critical: soft delete + anti-spam
- High: moderation workflow nâng cao
- Medium: version history
- Low: UX editor
