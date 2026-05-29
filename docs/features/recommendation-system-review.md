# Tổng quan chức năng
Recommendation system chưa được triển khai.

# Flow hiện tại
Không có đề xuất cá nhân hóa candidate/job.

# Business logic hiện tại
User tự tìm thủ công.

# Các vấn đề phát hiện
Không có bảng lưu hành vi (click/save/apply history chuẩn hóa).

# Tình huống thực tế có thể fail
Retention thấp vì candidate không được gợi ý phù hợp.

# Security issues
Khi thêm recommendation cần chính sách privacy dữ liệu hành vi.

# Performance issues
Thiếu pipeline precompute ranking.

# UX issues
Trang chủ chưa cá nhân hóa theo user.

# Edge cases
Cold-start user mới.

# Đề xuất cải thiện
Tạo recommendation V1 theo rule + activity signals, lưu recommendation history.

# Mức độ ưu tiên:
- High: tracking events
- High: recommendation table/service
- Medium: cold-start strategy
- Low: UI personalization
