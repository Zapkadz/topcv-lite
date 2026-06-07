# Current Task — TopCV Lite

> Cập nhật: **2026-06-07**

---

## Trạng thái

| Phase | Status |
|-------|--------|
| CV-A … CV-F | ✅ merged `main` |
| **EMP-A** | ✅ merged `main` (PR #14) |
| **EMP-B** | 🔄 **B4 pass** — chờ **`「bắt đầu B5」`** |

---

## Phase EMP-B (AI gợi ý xếp hạng ứng viên)

- **Plan:** `docs/phase-emp-b-plan.md`
- **Checklist:** `docs/project-memory/phase-emp-b-checklist.md`
- **Nhánh B4:** `feature/phase-emp-b-b4-review` · base: B3
- **Integration docs:** `web-cv-jd-input-contract.md`, `php-web-ai-ranking-integration-guide.md`

### Thiết kế đã chốt

- Apply chỉ **CV online** — không PDF apply / không fallback PDF cũ
- **`cv_snapshot_json`** → hiển thị CV (modal)
- **`cv_snapshot_text`** → input AI (lưu lúc apply) ✅ prep `8ed2873`
- PHP gọi **Python CLI** `C:\SEMANTIC_SKILLS_RESUME\main.py` — chưa HTTP API
- Kết quả bảng **`ai_screening_results`**
- UI trên **`job_candidates.php`** — không AI trên hub screening

### Tiến độ

- [x] **`「chốt cv_snapshot_text」`** — helper + migration + apply
- [x] **`「xác nhận EMP-B」`** — 2026-06-06
- [x] Prep committed + pushed
- [x] B0 plan + checklist
- [x] **`「B1 pass」`** — config + build JD + `ai_screening_results`
- [x] **`「B2 pass」`** — AiScreeningService + CLI → DB
- [x] **`「B3 pass」`** — run_ai_screening + UI cột rank
- [x] **`「B4 pass」`** — review modal + error handling
- [ ] **B5** — test full → **`「EMP-B pass」`**

### Quy trình

```text
B4 pass; tiếp B5 (test checklist plan §9) khi user gửi 「bắt đầu B5」.
```

---

## Phase EMP-A (đã đóng)

- Merge PR #14 @ `8bd34ab`
- Plan: `docs/phase-emp-a-plan.md`

---

## Phase CV-F (đã đóng)

- Merge PR #13 · F7/F8 bỏ qua
