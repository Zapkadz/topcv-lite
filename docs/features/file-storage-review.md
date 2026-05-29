# Tổng quan chức năng
File storage hiện lưu local (`uploads/`) cho CV/logo.

# Flow hiện tại
Upload trực tiếp vào server app.

# Business logic hiện tại
Lưu path tương đối vào DB.

# Các vấn đề phát hiện
- Không có abstraction storage.
- Không có lifecycle/retention/versioning.

# Tình huống thực tế có thể fail
Scale ngang nhiều server sẽ mất đồng bộ file.

# Security issues
Quyền truy cập file chưa có signed URL/ACL chuẩn.

# Performance issues
Disk I/O và backup bottleneck.

# UX issues
Không có cơ chế resume/retry upload tốt.

# Edge cases
File orphan khi record DB bị xóa/rollback.

# Đề xuất cải thiện
Object storage (S3-compatible), metadata table, cleanup jobs định kỳ.

# Mức độ ưu tiên:
- High: storage abstraction
- High: access control/signed URL
- Medium: cleanup/orphan policy
- Low: UX upload resilience
