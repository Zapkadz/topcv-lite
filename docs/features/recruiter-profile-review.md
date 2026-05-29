# Tổng quan chức năng
Recruiter profile gắn với công ty, thao tác ở `employer/company.php`.

# Flow hiện tại
Employer login + approved -> tạo/cập nhật company -> vào dashboard.

# Business logic hiện tại
Mỗi employer hiện mapping 1 company.

# Các vấn đề phát hiện
- Không enforce unique `companies.user_id` ở DB.
- Thiếu dữ liệu KYC/verify pháp lý doanh nghiệp.

# Tình huống thực tế có thể fail
Doanh nghiệp giả mạo đăng tin do thiếu quy trình xác thực.

# Security issues
Upload logo chưa kiểm MIME chuẩn.

# Performance issues
N/A.

# UX issues
Form thiếu trường liên hệ và thông tin minh bạch.

# Edge cases
Employer chưa có company bị chặn dashboard nhưng trải nghiệm còn gián đoạn.

# Đề xuất cải thiện
Thêm employer verification pipeline + unique constraint + moderation logs.

# Mức độ ưu tiên:
- Critical: verification doanh nghiệp
- High: unique ownership constraint
- Medium: mở rộng hồ sơ công ty
- Low: UX onboarding
