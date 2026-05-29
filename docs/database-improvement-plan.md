# Mục tiêu refactor database

- Tăng tính toàn vẹn dữ liệu.
- Chống lỗi nghiệp vụ khi concurrent traffic.
- Tối ưu truy vấn cho search/listing/report.
- Mở đường cho AI matching, recommendation, monetization.

# Đề xuất schema mới (trọng tâm)

- `users`: thêm `email_verified_at`, `last_login_at`, `deleted_at`, `account_status`.
- `employer_profiles` tách khỏi `users` (trạng thái duyệt, giấy phép, mức trust).
- `candidate_profiles`: mở rộng trường chuẩn hóa (skills, years_experience, preferred_location, expected_salary_min/max).
- `companies`: thêm `phone`, `email`, `scale`, `verification_status`, `verified_at`.
- `jobs`: chuẩn hóa salary thành numeric range (`salary_min`, `salary_max`, `currency`, `salary_type`), thêm `published_at`, `closed_at`, `deleted_at`.
- `applications`: thêm unique `(job_id, candidate_id)`, thêm `updated_at`, `source_channel`, `ai_score_snapshot`.
- Bảng mới:
  - `saved_jobs`
  - `job_recommendations`
  - `notification_events`
  - `application_status_history`
  - `cv_assets` (metadata + hash + storage key)
  - `moderation_logs`

# Index cần thêm

- `jobs(status, deadline, created_at DESC)`
- `jobs(category_id, location_id, status, deadline)`
- `jobs(company_id, status, created_at DESC)`
- `applications(job_id, status, created_at DESC)`
- `applications(candidate_id, created_at DESC)`
- `candidates(user_id UNIQUE)`
- `companies(user_id UNIQUE)`
- `saved_jobs(candidate_id, job_id UNIQUE)`

# Query optimization

- Chuyển `%LIKE%` sang Fulltext/Elastic (tùy giai đoạn).
- Thêm cursor-based pagination cho bảng lớn (applications/jobs admin).
- Materialized counters hoặc summary table cho dashboard.
- Giảm N+1 bằng query join chuẩn hóa/repository layer.

# Migration strategy

1. **Phase an toàn**: thêm cột/index mới nullable, không phá code cũ.
2. **Backfill**: script migrate dữ liệu cũ sang cấu trúc chuẩn.
3. **Dual-write**: tạm ghi cả schema cũ và mới.
4. **Read switch**: chuyển đọc sang schema mới theo feature flag.
5. **Cleanup**: xóa cột/bảng legacy sau khi ổn định.

# Scaling strategy

- Tách read/write profile (khi traffic tăng) và thêm read-replica.
- Lưu file CV sang object storage (S3-compatible), DB chỉ lưu metadata.
- Caching lớp query nóng (job list, filter dictionary).
- Queue hóa tác vụ nặng: parse CV, indexing search, gửi mail/noti.
- Thiết kế retention policy cho logs/snapshots để tránh phình DB.
