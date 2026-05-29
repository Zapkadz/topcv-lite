# PHASE 1 — Critical Fixes

- **Mục tiêu**: đóng các lỗ hổng có thể gây incident ngay.
- **Task list**:
  - Bổ sung CSRF cho toàn bộ form POST.
  - Thêm unique index `applications(job_id, candidate_id)`.
  - Fix schema drift (`companies` fields) và bug `candidate/profile.php`.
  - Hardening upload file (MIME/size/scan/quarantine).
- **Estimated impact**: giảm lỗi logic + bảo mật tức thì.
- **Risk level**: Medium.
- **Priority**: P0.
- **Recommended order**: DB constraint -> security middleware -> bugfix runtime.

# PHASE 2 — Business Logic Fixes

- **Mục tiêu**: chuẩn hóa domain tuyển dụng để vận hành thật.
- **Task list**:
  - Tách `account_status` và `approval_status`.
  - Bổ sung soft delete + lifecycle cho job/company/user.
  - Triển khai saved jobs, notification persistence, moderation logs.
- **Estimated impact**: giảm sai lệch nghiệp vụ, tăng độ tin cậy sản phẩm.
- **Risk level**: Medium.
- **Priority**: P1.
- **Recommended order**: status model -> lifecycle -> feature gaps.

# PHASE 3 — Performance & Scale

- **Mục tiêu**: chịu tải tăng trưởng user/job/application.
- **Task list**:
  - Thêm composite index + query tuning.
  - Tách file storage sang object storage.
  - Triển khai cache (Redis) + queue worker.
  - Chuẩn bị full-text search/Elastic.
- **Estimated impact**: giảm latency, tăng throughput.
- **Risk level**: Medium-High.
- **Priority**: P1.
- **Recommended order**: index tuning -> cache -> queue -> search engine.

# PHASE 4 — AI Improvements

- **Mục tiêu**: chuyển từ manual portal sang intelligent recruitment.
- **Task list**:
  - CV/JD parsing async.
  - Matching V1 rule-based + explainability.
  - Recommendation V1 + tracking history.
  - Semantic ranking V2.
- **Estimated impact**: tăng quality matching và conversion.
- **Risk level**: High.
- **Priority**: P2.
- **Recommended order**: data foundation -> V1 -> V2.

# PHASE 5 — Production Hardening

- **Mục tiêu**: vận hành ổn định dài hạn.
- **Task list**:
  - CI/CD, env/secret management, migration governance.
  - Centralized logging, metrics, alerting, tracing.
  - Backup/restore drill, runbook incident.
  - Test strategy (unit/integration/e2e/regression).
- **Estimated impact**: giảm downtime, tăng MTTR/độ tin cậy.
- **Risk level**: Medium.
- **Priority**: P2.
- **Recommended order**: observability baseline -> CI/CD -> DR -> testing coverage.
