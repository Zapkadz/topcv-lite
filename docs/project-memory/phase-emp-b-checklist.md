# EMP-B — Checklist tiến độ (AI gợi ý xếp hạng ứng viên)

> **Mục đích:** Theo dõi tiến độ từng khối B0→B5. AI đọc file này + `docs/phase-emp-b-plan.md` khi chat mới.  
> **Plan chi tiết:** `docs/phase-emp-b-plan.md`  
> **Nhánh:** `feature/phase-emp-b-cv-snapshot-text`  
> **Cập nhật lần cuối:** 2026-06-07

---

## Quy trình bắt buộc (mỗi khối B)

```text
AI làm 1 khối B → báo file + hướng test → USER test → USER gửi 「Bx pass」
→ (tuỳ chọn) USER yêu cầu commit → mới sang khối B tiếp
```

**AI không được:** sửa Python `SEMANTIC_SKILLS_RESUME`; làm HTTP API; PDF/OCR fallback; VIP; nhiều khối B một lúc; commit khi user chưa yêu cầu.

---

## Trạng thái tổng

| Mục | Giá trị |
|-----|---------|
| Phase | EMP-B — AI candidate ranking (CLI) |
| Nhánh | `feature/phase-emp-b-b5-verify` (prep → B0 → … → B5) |
| User confirm plan | ✅ **`「xác nhận EMP-B」`** — 2026-06-06 |
| **Khối hiện tại** | **B5** — smoke CLI OK, chờ manual UI + **`「EMP-B pass」`** |
| Phụ thuộc | EMP-A ✅ · cv_snapshot_text prep ✅ |
| Defer | FastAPI · VIP · AI trên hub screening |

### Ghi chú thiết kế đã chốt

- Apply **chỉ CV online** — không PDF apply; không fallback PDF cũ.
- **`cv_snapshot_json`** = hiển thị CV online (modal).
- **`cv_snapshot_text`** = input AI (lưu lúc apply).
- Python CLI absolute path — **không** copy AI vào `topcv_lite`.
- Kết quả lưu bảng **`ai_screening_results`** — không thêm cột vào `applications`.

---

## Bảng tiến độ nhanh

| Khối | Mô tả ngắn | Code | User test | User confirm | Commit |
|------|------------|------|-----------|--------------|--------|
| Prep | cv_snapshot_text + migration | ✅ | — | ✅ | ✅ `8ed2873` |
| B0 | Plan + checklist + confirm | ✅ | — | ✅ | ✅ |
| B1 | Config + build JD + DB results | ✅ | ✅ | ✅ | ✅ |
| B2 | AiScreeningService + CLI | ✅ | ✅ | ✅ | ✅ |
| B3 | run_ai_screening + UI cột rank | ✅ | ✅ | ✅ | ✅ |
| B4 | Review modal + errors | ✅ | ✅ | ✅ | ✅ |
| B5 | Test full + regression | 🔄 | 🔲 | ⬜ | ⬜ |

---

## Prep — cv_snapshot_text (trước B0)

| # | Làm gì | File | Verify |
|---|--------|------|--------|
| P.1 | Helper JSON → plain text | `includes/cv_snapshot_text.php` | `_test-cv-snapshot-text.php` OK |
| P.2 | Apply lưu text | `ApplicationService.php` | Apply mới có cột text |
| P.3 | Migration + backfill | `migrate-phase-emp-b-cv-snapshot-text.php` | Chạy localhost |

---

## B0 — Plan & nhánh (~0.25 ngày)

| # | Làm gì | File | Verify |
|---|--------|------|--------|
| B0.1 | Plan phase | `docs/phase-emp-b-plan.md` | User đọc + confirm |
| B0.2 | Checklist | File này | |
| B0.3 | Nhánh git | `feature/phase-emp-b-cv-snapshot-text` | Đã có + pushed |

**Pass B0:** User **`「xác nhận EMP-B」`**.

---

## B1 — Foundation (~0.5 ngày)

| # | Làm gì | File | Verify |
|---|--------|------|--------|
| B1.1 | Config Python paths | `config/ai_screening.example.php` | Path máy dev đúng |
| B1.2 | `build_job_text()` | `includes/ai_screening_job_text.php` | In JD text mẫu |
| B1.3 | Migration `ai_screening_results` | `docs/migrations/phase-emp-b-ai-screening.sql` | phpMyAdmin |
| B1.4 | Schema helper | `includes/schema_ai_screening.php` | Hint nếu chưa migrate |

**Pass B1:** JD text + bảng DB sẵn sàng.

---

## B2 — CLI integration (~0.5 ngày)

| # | Làm gì | File | Verify |
|---|--------|------|--------|
| B2.1 | Runtime folder + file naming | `AiScreeningService.php` | Folder `topcv_ai_runtime` |
| B2.2 | Gọi Python CLI | `AiScreeningService.php` | Exit 0 + JSON tồn tại |
| B2.3 | Parse `source_file` → app_id | helper | Map đúng application |
| B2.4 | Upsert DB | `AiScreeningRepository.php` | Rows trong `ai_screening_results` |

**Pass B2:** Chạy service từ script test → DB có rank.

---

## B3 — Endpoint + UI (~0.5 ngày)

| # | Làm gì | File | Verify |
|---|--------|------|--------|
| B3.1 | Action chạy AI | `employer/run_ai_screening.php` | CSRF + redirect |
| B3.2 | Nút + panel | `job_candidates.php` | Thay placeholder |
| B3.3 | Cột rank/score/rec | `job_candidates.php` | Sau chạy AI |
| B3.4 | Sort theo ai_rank | `job_candidates.php` | UV xếp đúng |

**Pass B3:** UI full flow bấm nút → thấy cột AI.

---

## B4 — Review + errors (~0.25 ngày)

| # | Làm gì | File | Verify |
|---|--------|------|--------|
| B4.1 | Modal review card | `job_candidates.php` | Summary + strengths… |
| B4.2 | Error messages | service + action | Python fail → Swal |
| B4.3 | UV thiếu text / JD rỗng | service | Message rõ |
| B4.4 | Regression EMP-A | manual | Status + CV modal OK |

**Pass B4:** Edge cases + modal OK.

---

## B5 — Hoàn thiện (~0.25 ngày)

| # | Làm gì | Verify |
|---|--------|--------|
| B5.1 | Script smoke CLI | `php docs/migrations/_test-ai-screening-b5-verify.php --run` |
| B5.2 | Manual UI plan §9 | Tick checklist bên dưới |
| B5.3 | PR EMP-B | User **`「EMP-B pass」`** |

### Checklist manual (plan §9)

| # | Test | Cách verify | Auto |
|---|------|-------------|------|
| 1 | Migration `cv_snapshot_text` | Apply mới có text | ✅ B5.1 |
| 2 | Job ≥1 UV có text → chạy AI | job 17 / company 2 (3 UV) | ✅ `--run` |
| 3 | Bảng rank / score / rec | `job_candidates.php?job_id=17` | ✅ DB |
| 4 | Modal review card | Nút **AI review** | 🔲 UI |
| 5 | Employer B ≠ job A | Wrong company bị từ chối | ✅ B5.6 |
| 6 | JD thiếu nội dung | Message rõ, không crash | ✅ B5.5 |
| 7 | UV thiếu text → skip | job 8 (1/2 có text) — Swal skip count | 🔲 UI |
| 8 | Python path sai | Sửa path sai → Swal + log | 🔲 UI |
| 9 | Regression EMP-A | Status + CV modal | 🔲 UI |

**Pass B5 / EMP-B:** Plan §9 tick hết → **`「EMP-B pass」`** → PR.

---

## Checkpoint log

| Ngày | Sự kiện |
|------|---------|
| 2026-06-06 | User **`「chốt cv_snapshot_text」`** — lưu text lúc apply |
| 2026-06-06 | Commit prep `8ed2873` + push nhánh |
| 2026-06-06 | User **`「xác nhận EMP-B」`** — plan CLI integration |
| 2026-06-06 | User **`「B1 pass」`** — config + JD text + ai_screening_results |
| 2026-06-07 | User **`「B2 pass」`** — CLI → DB (job 8 / company 2) |
| 2026-06-07 | User **`「B3 pass」`** — run_ai_screening + UI rank/score/rec |
| 2026-06-07 | User **`「B4 pass」`** — review modal + Swal errors + normalize HTML |

---

## Lệnh dev

```powershell
# Migration text (chạy trước khi test apply)
http://localhost/topcv_lite/docs/migrations/migrate-phase-emp-b-cv-snapshot-text.php

# Trang chính
http://localhost/topcv_lite/employer/job_candidates.php?job_id=1

# Python CLI (verify máy dev)
C:\SEMANTIC_SKILLS_RESUME\.venv\Scripts\python.exe C:\SEMANTIC_SKILLS_RESUME\main.py --help

# B5 smoke (automated + optional CLI run)
php docs/migrations/_test-ai-screening-b5-verify.php
php docs/migrations/_test-ai-screening-b5-verify.php --run

# UI test job gợi ý
http://localhost/topcv_lite/employer/job_candidates.php?job_id=17
http://localhost/topcv_lite/employer/job_candidates.php?job_id=8
```
