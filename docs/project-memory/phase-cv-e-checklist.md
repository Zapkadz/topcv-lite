# CV-E — Checklist tiến độ (Import PDF → AI → pre-fill)

> **Mục đích:** Theo dõi tiến độ từng khối E0→E8. Nếu chat bị full, AI đọc file này + `docs/phase-cv-e-plan.md` để biết đã làm tới đâu.  
> **Plan chi tiết:** `docs/phase-cv-e-plan.md`  
> **Nhánh:** `feature/phase-cv-e-import`  
> **Cập nhật lần cuối:** 2026-06-05

---

## Quy trình bắt buộc (mỗi khối E)

```text
AI làm 1 khối E → báo file + lệnh test → USER test → USER gửi 「Ex pass」
→ (tuỳ chọn) USER yêu cầu commit → mới sang khối E tiếp
```

**AI không được:** làm nhiều khối E một lúc; commit khi user chưa yêu cầu.

---

## Trạng thái tổng

| Mục | Giá trị |
|-----|---------|
| Phase | CV-E — Mức B (pdfparser + Groq/OpenRouter/Gemini) |
| Nhánh | `feature/phase-cv-e-import` |
| **Khối hiện tại** | ✅ **CV-E pass** — PR → merge `main` |
| Khối tiếp theo | PR → merge `main` |
| Commit CV-E | ✅ E4–E7 (E0–E3 trước đó) |
| PR | ❌ Chưa tạo |
| Setup doc | `docs/setup-cv-import.md` |

### Ghi chú phiên

- Provider AI production demo: **Groq** (`llama-3.3-70b-versatile`).
- PDF thiết kế Canva: text nhiễu — clean + prompt AI; không tách mục cứng (đã revert `structure_for_ai`).

---

## Bảng tiến độ nhanh

| Khối | Code | User test | User confirm | Commit |
|------|------|-----------|--------------|--------|
| E0 | ✅ | ✅ | ✅ | ✅ |
| E1 | ✅ | ✅ | ✅ | ✅ |
| E2 | ✅ | ✅ | ✅ | ✅ |
| E3 | ✅ | ✅ | ✅ | ✅ |
| E4 | ✅ | ✅ | ✅ | ✅ |
| E5 | ✅ | ✅ | ✅ | ✅ |
| E6 | ✅ | ✅ | ✅ | ✅ |
| E7 | ✅ | ✅ | ✅ | ✅ |
| E8 | ✅ | ✅ | ✅ | ✅ |

Chú thích: ✅ xong | ⏳ đang chờ | ❌ chưa | 🟡 code có nhưng chưa confirm

---

## E0 — Tiền đề & cấu hình

**Pass khi:** `composer install` OK; `ai_config_ready()` false khi chưa key — không crash.

| # | Việc | Trạng thái | Ghi chú |
|---|------|------------|---------|
| E0.1 | Pull `main` | ✅ | Base merge PR #11 |
| E0.2 | Nhánh `feature/phase-cv-e-import` | ✅ | |
| E0.3 | `composer.json` | ✅ | |
| E0.4 | `smalot/pdfparser` cài | ✅ | Dùng `--prefer-source` (thiếu zip ext) |
| E0.5 | `includes/composer_bootstrap.php` | ✅ | |
| E0.6 | `.gitignore` vendor, ai.local | ✅ | |
| E0.7 | `config/ai.example.php` | ✅ | |
| E0.8 | `includes/ai_config.php` | ✅ | |
| E0.9 | Gitignore `ai.local.php` | ✅ | |
| E0.10 | User tạo `config/ai.local.php` | ⏳ | Cần cho E2+ |
| E0.11 | `uploads/cv/import/.gitkeep` | ✅ | |

### Lệnh verify E0

```powershell
cd c:\xampp\htdocs\topcv_lite
php -r "require 'includes/composer_bootstrap.php'; echo class_exists('Smalot\\PdfParser\\Parser') ? 'pdfparser_ok' : 'fail';"
php -r "require 'includes/ai_config.php'; echo ai_config_ready() ? 'ai_ready' : 'ai_not_ready';"
```

**User confirm:** `「E0 pass」`

**Commit gợi ý:** `chore(cv-e): composer pdfparser và cấu hình AI`

---

## E1 — Trích xuất text PDF

**Pass khi:** PDF text 1–2 trang → `ok=true`, `text_len` > 200.

| # | Việc | Trạng thái | File |
|---|------|------------|------|
| E1.1 | `PdfTextExtractor::extract()` | ✅ | `includes/services/PdfTextExtractor.php` |
| E1.2 | Xử lý lỗi file/exception/text rỗng | ✅ | |
| E1.3 | `cv_import_truncate_text()` | ✅ | `includes/cv_import_rules.php` |
| E1.4 | `cv_import_min_text_len()` | ✅ | |
| E1.5 | Script test | ✅ | `docs/migrations/_test-pdf-extract.php` |
| E1.6 | User test PDF thật | ✅ | `cv_apply_6_8_1780123295.pdf` — ok=true, text_len=1492 |

### Lệnh verify E1

```powershell
php docs\migrations\_test-pdf-extract.php "đường_dẫn\file.pdf"
```

Kỳ vọng: `ok=true` + `text_len=...` (PDF có chữ); scan ảnh → `ok=false`.

**User confirm:** `「E1 pass」`

---

## E2 — AI parser (Gemini)

**Pass khi:** Text CV mẫu → JSON có `full_name`, `email`, ≥1 education/experience.

| # | Việc | Trạng thái | File |
|---|------|------------|------|
| E2.1 | Prompt builder | ✅ | `includes/cv_parse_prompt.php` |
| E2.2 | `AiCvParserService` | ✅ | `includes/services/AiCvParserService.php` |
| E2.3 | Gemini / OpenRouter / **Groq** HTTP + JSON mode | ✅ | Groq/OpenRouter = OpenAI chat API |
| E2.4 | Timeout + retry | ✅ | |
| E2.5 | Script `_test-ai-parse.php` | ✅ | `docs/migrations/_test-ai-parse.php` |
| E2.6 | User có `ai.local.php` | ✅ | user báo `ai_ready` |
| E2.7 | User test AI parse | ✅ | Groq `llama-3.3-70b-versatile` — ok=true, edu=2, exp=2, skills=6 |

### Lệnh verify E2 (sau khi có ai.local.php)

```powershell
php docs\migrations\_test-ai-parse.php "đường_dẫn\cv_text_mẫu.txt"
```

**User confirm:** `「E2 pass」`

---

## E3 — Fallback + normalize + orchestrator

**Pass khi:** API key sai → fallback vẫn có vài field; API đúng → `parse_source=ai`.

| # | Việc | Trạng thái | File |
|---|------|------------|------|
| E3.1 | `cv_parse_fallback_from_text()` | ✅ | `includes/cv_parse_fallback.php` |
| E3.2 | `cv_normalize_import_draft()` | ✅ | `includes/cv_import_rules.php` |
| E3.3 | Filter rows qua `cv_rules` | ✅ | |
| E3.4 | `CvParseService::importFromPdfPath()` | ✅ | `includes/services/CvParseService.php` |
| E3.5 | Meta `parse_source`, `warnings` | ✅ | |
| E3.6 | Test pipeline full PDF | ✅ | user `「E3 pass」` — parse_source=ai, edu/exp OK |

**User confirm:** `「E3 pass」`

**Commit gợi ý (gộp E1–E3):** `feat(cv-e): service parse PDF text + AI + fallback`

---

## E4 — Upload UI (`cv-import.php`)

**Pass khi:** Upload PDF hợp lệ → redirect builder ≤ 30s.

| # | Việc | Trạng thái | File |
|---|------|------------|------|
| E4.1 | Kind `cv_pdf_import` trong upload_validate | ✅ | `includes/upload_validate.php` |
| E4.2 | Trang `cv-import.php` GET/POST | ✅ | `candidate/cv-import.php` |
| E4.3 | CSRF `candidate_cv_import_form` | ✅ | |
| E4.4 | Lưu file `uploads/cv/import/...` | ✅ | |
| E4.5 | Session `cv_import_draft` | ✅ | |
| E4.6 | Nút「Tạo CV từ PDF」trên cv-manage | ✅ | `candidate/cv-manage.php` |
| E4.7 | Spinner / loading UX | ✅ | |

**User confirm:** `「E4 pass」` ✅

---

## E5 — Builder pre-fill

**Pass khi:** Form hiển thị đủ row child; user sửa được trước Lưu.

| # | Việc | Trạng thái | File |
|---|------|------------|------|
| E5.1 | Nhánh `from_import=1` load session | ✅ | `candidate/cv-builder.php` |
| E5.2 | Banner + link PDF gốc | ✅ | |
| E5.3 | Hidden `attachment_path` | ✅ | |
| E5.4 | Merge defaults từ `users` | ✅ | |
| E5.5 | Repeater rows từ draft | ✅ | `cv-builder.php` / JS nếu cần |
| E5.6 | Chặn import khi `?id=` edit | ✅ | |

**User confirm:** `「E5 pass」` ✅

**Commit gợi ý (gộp E4–E5):** `feat(cv-e): trang import và pre-fill builder`

---

## E6 — Lưu CV + attachment

**Pass khi:** Lưu xong → DB `attachment_path` đúng; preview + snapshot OK.

| # | Việc | Trạng thái | File |
|---|------|------------|------|
| E6.1 | `cv_parse_builder_post` nhận `attachment_path` | ✅ | `includes/cv_rules.php` |
| E6.2 | Validate path chống traversal | ✅ | `includes/cv_import_rules.php` |
| E6.3 | Clear session sau create OK | ✅ | `cv-builder.php` |
| E6.4 | (Tuỳ chọn) icon đính kèm trên cv-manage | ✅ | `candidate/cv-manage.php` |

**User confirm:** `「E6 pass」` ✅

---

## E7 — Bảo mật & vận hành

**Pass khi:** Rate limit 5/giờ; user B không đọc draft user A.

| # | Việc | Trạng thái | File |
|---|------|------------|------|
| E7.1 | `cv_import_rate_limit_check()` | ✅ | `includes/cv_import_rules.php` |
| E7.2 | Gọi rate limit ở cv-import POST | ✅ | `candidate/cv-import.php` |
| E7.3 | `docs/setup-cv-import.md` | ✅ | |
| E7.4 | Ghi chú `max_execution_time` XAMPP | ✅ | `docs/setup-cv-import.md` |

**User confirm:** `「E7 pass」` ✅

**Commit gợi ý (gộp E6–E7):** `feat(cv-e): lưu attachment và rate limit import`

---

## E8 — Docs & regression

**Pass khi:** Checklist regression bên dưới tick hết; user `「CV-E pass」`.

| # | Việc | Trạng thái | File |
|---|------|------------|------|
| E8.1 | `structured-cv-roadmap.md` | ✅ | CV-E = import Mức B |
| E8.2 | `dev-learning-log.md` | ✅ | mục CV-E |
| E8.3 | `current-task.md` | ✅ | E8 coded |
| E8.4 | Regression apply snapshot CV-C | ⏳ | user test |
| E8.5 | Regression builder thủ công CV-D | ⏳ | user test |
| E8.6 | Đánh dấu script test dev-only | ✅ | `docs/migrations/_test-*.php` |
| E8.7 | Cập nhật checklist | ✅ | file này |

**User confirm:** `「CV-E pass」` ✅

**Commit gợi ý:** `docs(cv-e): learning log, roadmap va regression checklist`

### Checklist regression (user — trước `「CV-E pass」`)

**Import happy path**

- [ ] `cv-manage` → **Tạo CV từ PDF** → upload → builder pre-fill
- [ ] Banner + link PDF gốc; sửa field → **Lưu** → icon 📎 trên manage
- [ ] **Preview** CV import OK

**Regression CV-C / CV-D**

- [ ] **Apply job** với CV vừa import → employer modal **CV online** có đủ section (snapshot)
- [ ] **Tạo CV thủ công** (không import) vẫn OK
- [ ] **Sửa CV** `?id=` không bị lẫn draft import
- [ ] **Xóa CV** có `attachment_path` — không crash (PDF orphan OK)

**Edge (đã test một phần — tick nếu OK)**

- [ ] Rate limit: lần 6 import/giờ bị chặn
- [ ] File > 5MB / MIME sai → từ chối
- [ ] PDF scan → thông báo rõ

---

## File đã tạo / sửa (CV-E, chưa commit)

### File mới (CV-E)

- `composer.json`, `composer.lock`
- `config/ai.example.php`
- `includes/ai_config.php`
- `includes/composer_bootstrap.php`
- `includes/cv_import_rules.php`
- `includes/cv_parse_prompt.php` ← E2, chưa confirm
- `includes/services/PdfTextExtractor.php` ← E1
- `includes/services/AiCvParserService.php` ← E2, chưa confirm
- `docs/migrations/_test-pdf-extract.php` ← E1
- `docs/phase-cv-e-plan.md`
- `uploads/cv/import/.gitkeep`

### File sửa (CV-E)

- `.gitignore`

### File sửa KHÔNG thuộc CV-E (có trên worktree — cẩn thận khi commit)

- `assets/js/cv-builder.js`
- `candidate/cv-builder.php`
- `includes/repositories/CvRepository.php`
- `topcv_lite.sql`
- `docs/structured-cv-roadmap.md`
- `docs/project-memory/current-task.md`

---

## Hướng dẫn tiếp tục (cho AI / user)

1. Đọc **「Trạng thái tổng」** và **「Khối hiện tại」** ở đầu file.
2. Nếu user chưa gửi `「E0 pass」` / `「E1 pass」` → **không** làm E2+; chỉ hỗ trợ test E0/E1.
3. Sau `「E1 pass」` → bắt đầu E2: tạo `_test-ai-parse.php`, user tạo `ai.local.php`, test Gemini.
4. Sau `「E2 pass」` → làm E3 (fallback + CvParseService).
5. Mỗi khối xong → cập nhật bảng **「Bảng tiến độ nhanh」** và tick checklist trong file này.
6. Commit chỉ khi user yêu cầu rõ (vd: `「commit E0」`).

### Lệnh user cần gửi

| Ý | Gửi |
|---|-----|
| E0 test OK | `「E0 pass」` |
| E1 test OK | `「E1 pass」` |
| … | `「E2 pass」` … `「E8 pass」` |
| Làm tiếp khối Ex | `「tiếp tục E2」` (sau khi Ex-1 pass) |
| Commit | `「commit E0」` hoặc mô tả scope commit |

---

## Checkpoint log (CV-E)

| Ngày | Khối | User confirm | Commit | Ghi chú |
|------|------|--------------|--------|---------|
| 2026-06-05 | E0–E3 | ✅ | 64de128, 918695b, 2d9b66b | pdfparser + Groq pipeline |
| 2026-06-05 | E4–E5 | ✅ | bf8c43f | import UI + pre-fill |
| 2026-06-06 | E6 | ✅ | c171380 | attachment_path |
| 2026-06-06 | E7 | ✅ | dec7231 | rate limit + setup doc |
| 2026-06-06 | E8 / CV-E | ✅ | 483dec3 | learning log, roadmap, dev-only scripts |
