# Tổng quan chức năng
CV upload xuất hiện ở profile và apply flow.

# Flow hiện tại
Upload local file -> lưu path trong DB (`candidates.cv_path` / `applications.cv_snapshot`).

# Business logic hiện tại
Khi apply bằng CV online thì copy thành snapshot.

# Các vấn đề phát hiện
- Chỉ kiểm extension; thiếu kiểm MIME/size/virus.
- Dùng thư mục local, chưa có storage abstraction.

# Tình huống thực tế có thể fail
100k CV gây quá tải disk/backup khó khăn.

# Security issues
Rủi ro upload file độc hại/đổi đuôi.

# Performance issues
I/O local bottleneck khi tải file đồng thời.

# UX issues
Thiếu progress, thiếu lỗi chi tiết.

# Edge cases
Copy snapshot lỗi thì fallback file gốc -> mất tính immutability.

# Đề xuất cải thiện
Object storage + signed URL + scan pipeline + metadata table.

# Mức độ ưu tiên:
- Critical: upload security controls
- High: tách storage service
- Medium: file lifecycle policy
- Low: UX upload
