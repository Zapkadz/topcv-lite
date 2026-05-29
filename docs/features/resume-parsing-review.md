# Tổng quan chức năng
Chức năng resume parsing chưa được triển khai trong code hiện tại.

# Flow hiện tại
Không có parser service, không có extract skill/experience.

# Business logic hiện tại
Hệ thống chỉ lưu file CV path, không đọc nội dung.

# Các vấn đề phát hiện
Thiếu toàn bộ pipeline parsing nên không thể AI matching thực sự.

# Tình huống thực tế có thể fail
Recruiter không thể lọc ứng viên theo skill từ CV.

# Security issues
Khi triển khai parser sau này cần sandbox xử lý file.

# Performance issues
Nếu parse synchronous sẽ block request.

# UX issues
Candidate không biết CV đã parse thành công hay chưa.

# Edge cases
CV đa ngôn ngữ, scan image PDF, format lỗi.

# Đề xuất cải thiện
Xây async parsing queue + structured candidate skill profile + parse status.

# Mức độ ưu tiên:
- Critical: bổ sung parsing pipeline
- High: async architecture
- Medium: parse quality monitoring
- Low: UX trạng thái parse
