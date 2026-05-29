# Tổng quan chức năng
Payment và Subscription chưa triển khai.

# Flow hiện tại
Không có billing model, không có entitlement.

# Business logic hiện tại
Nâng cấp tài khoản mới dừng ở nút UI.

# Các vấn đề phát hiện
Không có monetization engine cho sản phẩm thật.

# Tình huống thực tế có thể fail
Không thể giới hạn quota theo gói dịch vụ.

# Security issues
Khi triển khai cần PCI-safe flow, webhook verification, anti-fraud.

# Performance issues
N/A hiện tại.

# UX issues
Không có pricing, hóa đơn, lịch sử giao dịch.

# Edge cases
Thanh toán pending/refund/chargeback.

# Đề xuất cải thiện
Xây module plans/subscriptions/invoices/payments + webhook-driven state machine.

# Mức độ ưu tiên:
- High: billing domain model
- High: entitlement checks
- Medium: invoice/reporting
- Low: self-serve billing UI
