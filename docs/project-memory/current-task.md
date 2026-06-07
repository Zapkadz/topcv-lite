# Current Task — TopCV Lite

> Cập nhật: **2026-06-06**

---

## Trạng thái

| Phase | Status |
|-------|--------|
| CV-A … CV-F | ✅ merged `main` (PR #12 CV-E, PR #13 CV-F) |
| **EMP-A** | ✅ **`「EMP-A pass」`** — PR mở trên `main` |

---

## Phase EMP-A (Sàng lọc ứng viên — Employer)

- **Plan:** `docs/phase-emp-a-plan.md`
- **Checklist:** `docs/project-memory/phase-emp-a-checklist.md`
- **Nhánh:** `feature/phase-emp-a-screening` (từ `main` @ PR #13)
- **Defer:** AI ranking → EMP-B · VIP → sau

### Thiết kế đã chốt

- Hub **`candidate_screening.php`**: Đang tuyển + Hết hạn (còn CV)
- Chi tiết **`job_candidates.php?job_id=`**
- **`manage-jobs.php`** không gắn screening
- **`applicants.php`** giữ làm Hộp thư CV
- Dashboard: card **Sàng lọc ứng viên** thay Lượt xem tin

### Tiến độ

- [x] **`「xác nhận EMP-A」`** — 2026-06-06
- [x] Nhánh + plan + checklist (A0)
- [x] **`「A1 pass」`** — hub screening + dashboard card
- [x] **`「A2 pass」`** — job_candidates + bảo mật
- [x] **`「A3 pass」`** — breadcrumb, cross-link, applicants regression
- [x] **`「EMP-A pass」`** — 2026-06-06 → PR

### Quy trình

```text
EMP-A đóng (A0–A3). Tiếp theo: merge PR → EMP-B (AI ranking).
```

---

## Phase CV-F (đã đóng)

- Merge PR #13 @ `cc6091b`
- F7 DOCX / F8 docs — bỏ qua (user chốt)

---

## Phase CV-E (đã đóng)

- Merge PR #12 @ `ca974de`
