# Tổng quan chức năng
Subscription chưa triển khai riêng biệt.

# Flow hiện tại
Chưa có gói dịch vụ, quota, hạn sử dụng.

# Business logic hiện tại
Tất cả employer gần như cùng quyền sau khi duyệt.

# Các vấn đề phát hiện
Không thể thương mại hóa theo tier và không kiểm soát entitlement.

# Tình huống thực tế có thể fail
Khách trả tiền và khách free không có khác biệt tính năng.

# Security issues
Khi triển khai cần chống giả mạo entitlement.

# Performance issues
N/A.

# UX issues
Không có trang quản lý gói/hóa đơn.

# Edge cases
Gói hết hạn giữa phiên thao tác đăng tin.

# Đề xuất cải thiện
Subscription state machine + quota guard ở mọi action nhạy cảm.

# Mức độ ưu tiên:
- High: domain model entitlement
- Medium: lifecycle upgrade/downgrade
- Low: self-service portal
