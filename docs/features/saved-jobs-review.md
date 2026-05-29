# Tổng quan chức năng
Saved jobs chưa được triển khai backend; hiện chỉ có icon tim trên UI.

# Flow hiện tại
Không có bảng lưu và không có API xử lý.

# Business logic hiện tại
N/A.

# Các vấn đề phát hiện
Mismatch giữa UX và capability thực tế.

# Tình huống thực tế có thể fail
User tưởng đã lưu nhưng dữ liệu không tồn tại.

# Security issues
N/A.

# Performance issues
N/A.

# UX issues
Gây mất niềm tin do chức năng "ảo".

# Edge cases
N/A.

# Đề xuất cải thiện
Tạo bảng `saved_jobs(candidate_id, job_id, created_at)` + toggle endpoint + listing.

# Mức độ ưu tiên:
- High: triển khai backend thật
- Medium: UX feedback rõ
- Low: analytics saved-to-apply
