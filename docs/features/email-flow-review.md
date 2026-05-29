# Tổng quan chức năng
Email flow transactional chưa triển khai.

# Flow hiện tại
Không có gửi mail đăng ký, reset pass, thông báo trạng thái.

# Business logic hiện tại
Dựa vào thông báo in-app tạm thời.

# Các vấn đề phát hiện
Không có kênh thông báo ngoài phiên đăng nhập.

# Tình huống thực tế có thể fail
Ứng viên bỏ lỡ thông báo mời phỏng vấn.

# Security issues
Khi thêm email cần SPF/DKIM/DMARC, chống spoofing.

# Performance issues
Gửi email sync sẽ chậm request.

# UX issues
Thiếu lịch sử và preference nhận mail.

# Edge cases
Bounced email, retry policy.

# Đề xuất cải thiện
Mail service + queue + template versioning + delivery tracking.

# Mức độ ưu tiên:
- High: transactional email cơ bản
- Medium: delivery tracking
- Low: notification preferences
