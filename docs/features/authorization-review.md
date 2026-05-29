# Tổng quan chức năng
Phân quyền dựa vào `$_SESSION['role']` kiểm tra trực tiếp ở từng file.

# Flow hiện tại
Mỗi page tự check role; employer có thêm `auth_check.php`.

# Business logic hiện tại
Role: `candidate`, `employer`, `admin`.

# Các vấn đề phát hiện
- Không có middleware tập trung.
- Có route check quyền sau khi include header.

# Tình huống thực tế có thể fail
Route mới bị quên guard, gây lộ dữ liệu.

# Security issues
IDOR risk tại một số thao tác update trạng thái/hồ sơ nếu thiếu check ownership sâu.

# Performance issues
N/A.

# UX issues
Redirect/alert không thống nhất.

# Edge cases
Role đổi trong DB nhưng session cũ chưa refresh.

# Đề xuất cải thiện
Xây middleware RBAC tập trung + policy theo resource.

# Mức độ ưu tiên:
- Critical: middleware tập trung
- High: policy ownership
- Medium: chuẩn hóa phản hồi unauthorized
- Low: tối ưu thông điệp
