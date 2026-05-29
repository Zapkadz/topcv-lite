# Tổng quan chức năng
Notification system chưa có (ngoài flash message session trong web).

# Flow hiện tại
Chỉ popup nội trang sau redirect.

# Business logic hiện tại
Không có noti persistence, không có read/unread.

# Các vấn đề phát hiện
Mất toàn bộ event quan trọng khi user không online đúng thời điểm.

# Tình huống thực tế có thể fail
Candidate bỏ lỡ lời mời phỏng vấn.

# Security issues
Khi triển khai cần chống lộ thông tin nhạy cảm trong noti payload.

# Performance issues
Không có queue gửi noti/email.

# UX issues
Không có notification center.

# Edge cases
Retry thất bại gửi mail/push.

# Đề xuất cải thiện
Thiết kế `notifications` + `notification_deliveries` + worker retry.

# Mức độ ưu tiên:
- High: persistence + retry
- Medium: notification center
- Low: đa kênh push
