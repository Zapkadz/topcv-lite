# Tổng quan chức năng
Hệ thống chưa theo kiến trúc API-first; security controls phân tán.

# Flow hiện tại
Server-rendered PHP + một endpoint JSON nội bộ.

# Business logic hiện tại
Không có API gateway/rate limiting chuẩn.

# Các vấn đề phát hiện
- Thiếu CSRF cho form POST.
- Thiếu rate limit chống scraping/spam apply/login.

# Tình huống thực tế có thể fail
Bot gửi hàng loạt request apply/login gây nghẽn.

# Security issues
Thiếu WAF rule tối thiểu, thiếu request signature ở webhook tương lai.

# Performance issues
Không có backpressure controls.

# UX issues
Khi bị tấn công có thể làm người dùng thật bị ảnh hưởng trực tiếp.

# Edge cases
Burst traffic không có circuit breaker.

# Đề xuất cải thiện
Thêm CSRF middleware, rate limit per IP/user/action, audit request log.

# Mức độ ưu tiên:
- Critical: CSRF + rate limit
- High: anti-bot challenge
- Medium: API policy chuẩn hóa
- Low: dashboard security metrics
