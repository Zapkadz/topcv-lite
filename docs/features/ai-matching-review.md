# Tổng quan chức năng
AI matching chưa tồn tại trong source code hiện tại.

# Flow hiện tại
Không có thuật toán score/ranking candidate-job.

# Business logic hiện tại
Apply đang là cơ chế thủ công 100%.

# Các vấn đề phát hiện
Không có dữ liệu chuẩn hóa để tính match score.

# Tình huống thực tế có thể fail
Recruiter phải duyệt tay toàn bộ CV khi volume lớn.

# Security issues
Khi thêm AI cần kiểm soát explainability và bias audit.

# Performance issues
Không có batch scoring/feature store.

# UX issues
Thiếu đề xuất việc làm cá nhân hóa.

# Edge cases
Career switch, fresher, keyword spam không được xử lý.

# Đề xuất cải thiện
Xây matching V1 rule-based trước, sau đó hybrid semantic model + explanation.

# Mức độ ưu tiên:
- Critical: xây nền data cho matching
- High: score + explainability
- Medium: ranking optimization
- Low: UI badges
