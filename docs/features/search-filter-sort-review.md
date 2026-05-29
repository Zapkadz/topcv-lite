# Tổng quan chức năng
Search/filter đã có cơ bản; sort còn cố định theo `created_at DESC`.

# Flow hiện tại
Filter qua query params trong `index.php` và `jobs.php`.

# Business logic hiện tại
Lọc theo location/category/experience/salary text.

# Các vấn đề phát hiện
Sort chưa linh hoạt (relevance, deadline gần, lương cao).

# Tình huống thực tế có thể fail
Candidate khó tìm job phù hợp nhanh khi data lớn.

# Security issues
Thiếu bảo vệ chống scraping query hàng loạt.

# Performance issues
LIKE và filter text chưa tối ưu index.

# UX issues
Filter state giữa trang chưa nhất quán hoàn toàn.

# Edge cases
Kết quả rỗng khi filter kết hợp nhiều điều kiện nhưng thiếu gợi ý nới lỏng.

# Đề xuất cải thiện
Thêm sort options + query planner + search suggestions.

# Mức độ ưu tiên:
- High: sort/relevance
- High: index/filter optimization
- Medium: search suggestions
- Low: UX refine filters
