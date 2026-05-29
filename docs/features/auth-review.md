# Tổng quan chức năng
Đăng ký/đăng nhập dùng session + password hash, chưa có email verification/2FA.

# Flow hiện tại
`register.php` tạo user -> `login.php` verify password -> set `$_SESSION`.

# Business logic hiện tại
Employer đăng ký bị `status=0` chờ duyệt; candidate active ngay.

# Các vấn đề phát hiện
- Thiếu CSRF, thiếu throttle login, thiếu lockout.
- `status` dùng đa nghĩa (pending/locked/active).

# Tình huống thực tế có thể fail
Bruteforce mật khẩu hoặc bot spam account.

# Security issues
Session hardening và anti-automation chưa có.

# Performance issues
Chưa nghiêm trọng ở quy mô nhỏ.

# UX issues
Thông báo lỗi còn chung chung, thiếu hướng dẫn khôi phục tài khoản.

# Edge cases
Tài khoản bị khóa vẫn có thể có session cũ nếu chưa re-check ở mọi route.

# Đề xuất cải thiện
Thêm CSRF, rate limit, lockout policy, email verification, password reset.

# Mức độ ưu tiên:
- Critical: CSRF + login rate limit
- High: account status model chuẩn
- Medium: email verification/reset
- Low: UX copy
