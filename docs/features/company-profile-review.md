# Tổng quan chức năng
Company profile hiển thị tại `company-detail.php`, dữ liệu từ `companies`.

# Flow hiện tại
Public xem company + danh sách jobs approved chưa hết hạn.

# Business logic hiện tại
Công ty là thực thể trung tâm cho employer đăng job.

# Các vấn đề phát hiện
- Code dùng `phone/email/scale` nhưng schema không có.
- Follow company chỉ là UI giả.

# Tình huống thực tế có thể fail
Trang chi tiết hiển thị thông tin thiếu/không đồng nhất gây mất trust.

# Security issues
Thiếu cơ chế report công ty giả mạo.

# Performance issues
Cần index khi công ty có số lượng job lớn.

# UX issues
Thông tin doanh nghiệp chưa đủ để ứng viên đánh giá.

# Edge cases
Company bị block nhưng nội dung cũ có thể vẫn lộ.

# Đề xuất cải thiện
Đồng bộ schema-code, bổ sung verification badge, report flow.

# Mức độ ưu tiên:
- Critical: sửa schema drift
- High: compliance/trust info
- Medium: follow/report thật
- Low: UI polish
