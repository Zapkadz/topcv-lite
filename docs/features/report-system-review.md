# Tổng quan chức năng
Report system (user report job/company/content) chưa có.

# Flow hiện tại
Không có endpoint/report queue cho abuse reporting.

# Business logic hiện tại
Admin kiểm duyệt chủ yếu thủ công qua danh sách.

# Các vấn đề phát hiện
Thiếu kênh phản hồi từ cộng đồng.

# Tình huống thực tế có thể fail
Tin lừa đảo tồn tại lâu vì không có cơ chế report.

# Security issues
Không có bằng chứng và SLA xử lý vi phạm.

# Performance issues
N/A.

# UX issues
User không biết báo xấu ở đâu.

# Edge cases
Report spam cần rate limit.

# Đề xuất cải thiện
Tạo `reports`, `report_actions`, `report_reasons` + moderation workflow.

# Mức độ ưu tiên:
- High: triển khai report abuse
- Medium: SLA moderation
- Low: UX tracking tiến độ report
