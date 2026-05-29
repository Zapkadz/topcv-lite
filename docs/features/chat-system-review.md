# Tổng quan chức năng
Chat system chưa có.

# Flow hiện tại
Không có real-time messaging recruiter-candidate.

# Business logic hiện tại
Trao đổi ngoài hệ thống.

# Các vấn đề phát hiện
Mất dữ liệu trao đổi, khó tracking tuyển dụng.

# Tình huống thực tế có thể fail
Dispute giữa candidate và recruiter không có log đối soát.

# Security issues
Cần anti-abuse, content moderation, privacy retention.

# Performance issues
Cần websocket infra và message persistence.

# UX issues
Không có kênh liên lạc in-platform.

# Edge cases
Spam chat, file đính kèm độc hại.

# Đề xuất cải thiện
Triển khai messaging service + moderation + report/block.

# Mức độ ưu tiên:
- Medium: nếu định vị nền tảng full-cycle
- Low: nếu giai đoạn chỉ tập trung listing/apply
