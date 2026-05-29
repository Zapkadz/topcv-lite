# Tổng quan chức năng
Analytics hiện chỉ có dashboard admin mức cơ bản.

# Flow hiện tại
Query trực tiếp bảng nghiệp vụ để vẽ biểu đồ.

# Business logic hiện tại
Không có data warehouse/event tracking riêng.

# Các vấn đề phát hiện
Thiếu chỉ số funnel: view -> save -> apply -> interview -> hire.

# Tình huống thực tế có thể fail
Không đo được hiệu quả tuyển dụng theo nguồn/kênh.

# Security issues
Thiếu policy phân quyền xem số liệu nhạy cảm.

# Performance issues
Ad-hoc query sẽ chậm khi data tăng.

# UX issues
Dashboard chưa hỗ trợ drill-down.

# Edge cases
Sai lệch dữ liệu do timezone/late events.

# Đề xuất cải thiện
Thiết kế event schema + summary tables + BI-ready exports.

# Mức độ ưu tiên:
- High: event tracking chuẩn hóa
- Medium: funnel metrics
- Low: advanced dashboard UI
