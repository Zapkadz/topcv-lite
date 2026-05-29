# Tổng quan chức năng
Moderation hiện có cho employer approval và job approval.

# Flow hiện tại
Admin duyệt thủ công qua `users.php` và `jobs.php`.

# Business logic hiện tại
Có trạng thái pending/approved/rejected + admin note cơ bản.

# Các vấn đề phát hiện
- Thiếu SLA, thiếu hàng đợi kiểm duyệt theo mức rủi ro.
- Thiếu lịch sử thay đổi trạng thái đầy đủ.

# Tình huống thực tế có thể fail
Peak giờ cao điểm -> backlog duyệt kéo dài, ảnh hưởng doanh thu.

# Security issues
Không có 2-step review cho case nhạy cảm.

# Performance issues
Không có priority queue moderation.

# UX issues
Employer chưa có tracking rõ tiến trình duyệt.

# Edge cases
Tin bị report nhiều lần nhưng chưa auto-escalation.

# Đề xuất cải thiện
Moderation engine theo rule/risk score + audit timeline + escalation queue.

# Mức độ ưu tiên:
- High: audit timeline
- High: queue ưu tiên
- Medium: auto-risk rules
- Low: UX status page
