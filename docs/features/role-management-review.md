# Tổng quan chức năng
Role management hiện phụ thuộc trực tiếp vào cột `users.role` và `users.status`.

# Flow hiện tại
Admin duyệt employer tại `admin/users.php` bằng set `status=1`.

# Business logic hiện tại
Candidate/employer/admin trộn cùng bảng users.

# Các vấn đề phát hiện
- Thiếu phân quyền chi tiết (permission-level).
- Thiếu audit trail cho thay đổi role/status.

# Tình huống thực tế có thể fail
Khó truy vết ai đã duyệt/khóa tài khoản khi có dispute.

# Security issues
Không có nguyên tắc least privilege cho admin sub-role.

# Performance issues
N/A.

# UX issues
Admin chưa có batch action hoặc filter sâu.

# Edge cases
Employer bị khóa khi đang có job active: chưa có chính sách xử lý đồng bộ.

# Đề xuất cải thiện
Tách RBAC tables (`roles`, `permissions`, `user_roles`, `audit_logs`).

# Mức độ ưu tiên:
- Critical: audit logs thay đổi quyền
- High: tách account_status khỏi approval_status
- Medium: granular permissions
- Low: UX admin
