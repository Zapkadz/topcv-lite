# Tổng quan chức năng
4 mảng logging/error handling/caching/queue hiện chưa có nền tảng production-grade.

# Flow hiện tại
Xử lý lỗi trực tiếp trong page, không có centralized logging.

# Business logic hiện tại
Đủ cho local demo, chưa đủ cho vận hành lớn.

# Các vấn đề phát hiện
- Không có structured log + correlation id.
- Không có exception handler chuẩn.
- Không có cache tầng app/query.
- Không có queue/background jobs.

# Tình huống thực tế có thể fail
Lỗi ngẫu nhiên khó truy vết root cause; tác vụ nặng chặn request.

# Security issues
Không có security event log chuẩn.

# Performance issues
Thiếu cache và async khiến hệ thống không scale tốt.

# UX issues
Người dùng gặp timeout ở thao tác nặng.

# Edge cases
Retry task thất bại không có dead-letter queue.

# Đề xuất cải thiện
Stack đề xuất: Monolog + global error handler + Redis cache + queue worker.

# Mức độ ưu tiên:
- Critical: centralized logging + global error handler
- High: queue cho parse/noti/email
- High: cache query nóng
- Medium: observability dashboard
