# Tổng quan chức năng
Candidate cập nhật title, bio, CV mặc định tại `candidate/profile.php`.

# Flow hiện tại
Form POST -> upload file local -> insert/update `candidates`.

# Business logic hiện tại
Profile tối giản, chưa có thông tin nghề nghiệp chi tiết.

# Các vấn đề phát hiện
- Biến `$profile` được dùng nhưng không thấy query khởi tạo trong file (bug runtime).
- Upload chỉ check extension.

# Tình huống thực tế có thể fail
Ứng viên mở profile lần đầu có thể lỗi undefined variable.

# Security issues
Thiếu kiểm tra MIME/size/scan file CV.

# Performance issues
N/A hiện tại, nhưng listing profile thiếu cache/index mở rộng.

# UX issues
Thiếu progress completeness, thiếu field quan trọng.

# Edge cases
Upload thất bại im lặng, user không biết nguyên nhân.

# Đề xuất cải thiện
Fix query profile, chuẩn hóa validation, mở rộng structured profile.

# Mức độ ưu tiên:
- Critical: fix bug `$profile`
- High: upload validation
- Medium: bổ sung profile fields
- Low: UX completion meter
