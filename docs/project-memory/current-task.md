# Current Task — TopCV Lite

> Cập nhật: **2026-06-06**

---

## Trạng thái

| Phase | Status |
|-------|--------|
| CV-A … CV-F | ✅ merged `main` |
| **EMP-A** | ✅ merged `main` (PR #14) |
| **EMP-B** | 🔄 **B3 code xong** — chờ test 「B3 pass」 |

---

## Phase EMP-B (AI gợi ý xếp hạng ứng viên)

- **Plan:** `docs/phase-emp-b-plan.md`
- **Checklist:** `docs/project-memory/phase-emp-b-checklist.md`
- **Nhánh B3:** `feature/phase-emp-b-b3-ui` · base: B2
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
- [x] **`「B2 pass」`** — AiScreeningService + CLI → DB (test job 8 / company 2)
- [ ] **B3** — run_ai_screening + UI
- [ ] **B4** — review modal + errors
- [ ] **B5** — test → **`「EMP-B pass」`**

### Quy trình

```text
B2 pass; tiếp B3 khi user gửi 「bắt đầu B3」.
```

---

## Phase EMP-A (đã đóng)

- Merge PR #14 @ `8bd34ab`
- Plan: `docs/phase-emp-a-plan.md`

---

## Phase CV-F (đã đóng)

- Merge PR #13 · F7/F8 bỏ qua
