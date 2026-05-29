# Tổng quan chức năng
Search/filter jobs tại `index.php` và `jobs.php`.

# Flow hiện tại
Build SQL điều kiện theo query params, lọc status approved + deadline.

# Business logic hiện tại
Hỗ trợ lọc keyword, category, location, experience, salary text.

# Các vấn đề phát hiện
- `%LIKE%` thuần văn bản, không có fulltext.
- Field salary/experience chưa chuẩn hóa.

# Tình huống thực tế có thể fail
Traffic lớn sẽ full scan, kết quả chậm.

# Security issues
Prepared statement khá ổn, nhưng thiếu rate limit search scraping.

# Performance issues
Thiếu composite index và search engine.

# UX issues
Kết quả liên quan thấp, thiếu sort relevance/newest/hot rõ ràng.

# Edge cases
Keyword typo, viết tắt, đa ngôn ngữ.

# Đề xuất cải thiện
Chuẩn hóa dữ liệu + fulltext/Elastic + tracking search analytics.

# Mức độ ưu tiên:
- Critical: index/search optimization
- High: chuẩn hóa trường filter
- Medium: typo tolerance
- Low: UI filter nâng cao
