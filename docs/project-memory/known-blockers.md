# Known Blockers — Active Issues

> Chỉ liệt kê vấn đề **đang chặn** công việc. Resolved → chuyển sang `dev-learning-log.md`.

---

## BLOCKER-001: Bảng `applications` corruption (error 1932) — ✅ RESOLVED

| Field | Value |
|-------|-------|
| **Status** | ✅ RESOLVED — 2026-05-29 (user recreate bảng + test Nhóm 2A pass) |
| **Phát hiện** | 2026-05-29 |
| **Ảnh hưởng** | Không apply job, không xem `candidate/my-jobs.php`, không test Nhóm 2A |
| **Error** | `SQLSTATE[42S02]: Base table or view not found: 1932 Table 'topcv_lite.applications' doesn't exist in engine` |
| **File crash** | `job-detail.php:57`, `candidate/my-jobs.php:26`, `apply.php` (insert) |
| **Nguyên nhân** | ALTER TABLE thêm UNIQUE (Nhóm 1) có thể fail → metadata orphan |
| **Verify** | `SHOW TABLES` có `applications`; `SELECT * FROM applications` → 1932 |

### Fix (copy-paste ready)

```sql
USE topcv_lite;
DROP TABLE IF EXISTS applications;
CREATE TABLE applications (
  id int(11) NOT NULL AUTO_INCREMENT,
  job_id int(11) NOT NULL,
  candidate_id int(11) NOT NULL,
  cv_snapshot varchar(255) NOT NULL,
  cover_letter text DEFAULT NULL,
  status enum('pending','viewed','interview','rejected') DEFAULT 'pending',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY uniq_job_candidate (job_id, candidate_id),
  KEY job_id (job_id),
  KEY candidate_id (candidate_id),
  CONSTRAINT applications_ibfk_1 FOREIGN KEY (job_id) REFERENCES jobs (id) ON DELETE CASCADE,
  CONSTRAINT applications_ibfk_2 FOREIGN KEY (candidate_id) REFERENCES candidates (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Sau khi fix — verify

```sql
SELECT COUNT(*) FROM applications;  -- phải trả 0, không lỗi
```

### Data loss

- Đơn ứng tuyển cũ **mất** (bảng hỏng không đọc được). User apply lại thủ công.

---

## Không phải blocker (nhưng đừng nhầm)

| Issue | Ghi chú |
|-------|---------|
| UI hiện "Đã ứng tuyển" | Hành vi đúng khi DB còn data; hiện crash trước khi render vì query fail |
| CSRF 2A | Code OK, chỉ bị block bởi BLOCKER-001 |

---

## Resolved blockers (archive)

### BLOCKER-001 (2026-05-29)
- **Vấn đề:** error 1932 trên bảng `applications`
- **Fix:** DROP + CREATE lại bảng (SQL trong `current-task.md` hoặc log trên)
- **Xác nhận:** User test Nhóm 2A pass sau khi fix

---

## Active blockers

*(Không có blocker đang active — cập nhật 2026-05-29)*
