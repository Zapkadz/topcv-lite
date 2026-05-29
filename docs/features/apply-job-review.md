# Tổng quan chức năng
Apply job ở `apply.php`, hỗ trợ CV online hoặc upload mới.

# Flow hiện tại
Check login + role -> map candidate -> check duplicate -> lưu `applications`.

# Business logic hiện tại
Tránh apply trùng bằng query check trước insert.

# Các vấn đề phát hiện
- Thiếu unique constraint DB cho `(job_id,candidate_id)`.
- Không verify job còn active trước khi apply ở backend.

# Tình huống thực tế có thể fail
Concurrent click gây đơn trùng.

# Security issues
Thiếu CSRF cho thao tác apply.

# Performance issues
Khi volume cao, check+insert không atomic.

# UX issues
Không có trạng thái nộp theo timeline chi tiết.

# Edge cases
Apply vào job hết hạn/đã ẩn nếu request giả mạo.

# Đề xuất cải thiện
Unique index + transaction + idempotency key + backend validate job status.

# Mức độ ưu tiên:
- Critical: unique index + validate job status
- High: transaction/idempotency
- Medium: timeline status
- Low: UX thông báo chi tiết
