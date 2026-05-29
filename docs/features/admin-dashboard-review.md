# Tổng quan chức năng
Admin dashboard có thống kê cơ bản và biểu đồ Chart.js.

# Flow hiện tại
Query trực tiếp DB, không có cache layer.

# Business logic hiện tại
Phục vụ snapshot vận hành mức MVP.

# Các vấn đề phát hiện
- Không có RBAC chi tiết cho admin.
- Dữ liệu analytics chưa đảm bảo tính đúng khi scale.

# Tình huống thực tế có thể fail
Dashboard chậm khi dữ liệu lớn do query ad-hoc.

# Security issues
Thiếu audit action admin quan trọng.

# Performance issues
Cần pre-aggregation/materialized metrics.

# UX issues
Thiếu filter thời gian linh hoạt.

# Edge cases
Không xử lý timezone/late-arriving events.

# Đề xuất cải thiện
Thêm analytics tables + cached metrics + audit trail admin actions.

# Mức độ ưu tiên:
- High: audit trail
- High: metrics aggregation
- Medium: dashboard filter
- Low: visual polish
