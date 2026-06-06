# CV-F — Checklist tiến độ (GPT Vision scan PDF)

> **Mục đích:** Theo dõi tiến độ từng khối F0→F8. AI đọc file này + `docs/phase-cv-f-plan.md` khi chat mới.  
> **Plan chi tiết:** `docs/phase-cv-f-plan.md`  
> **Nhánh:** `feature/phase-cv-f-vision`  
> **Cập nhật lần cuối:** 2026-06-05

---

## Quy trình bắt buộc (mỗi khối F)

```text
AI làm 1 khối F → báo file + lệnh test → USER test → USER gửi 「Fx pass」
→ (tuỳ chọn) USER yêu cầu commit → mới sang khối F tiếp
```

**AI không được:** làm nhiều khối F một lúc; commit khi user chưa yêu cầu; code trước `「bắt đầu code CV-F」`.

---

## Trạng thái tổng

| Mục | Giá trị |
|-----|---------|
| Phase | CV-F — Mức C (GPT-4o PDF vision + smart router) |
| Nhánh | `feature/phase-cv-f-vision` |
| User confirm plan | ✅ **`「xác nhận CV-F」`** — 2026-06-05 |
| **Khối hiện tại** | **F1** — smart router (sau F0 pass) |
| Phụ thuộc | CV-E merged `main` ✅ |
| MVP scope | PDF + GPT vision; **DOCX defer F7** (optional sau pass) |

### Ghi chú thiết kế đã chốt

- **Không thay CV-E:** Groq/text path = default nhanh; GPT vision khi scan / noisy / user ép.
- **Structured Outputs:** OpenAI `json_schema` strict — schema mirror CV-D.
- **Không Tesseract** local — user dùng GPT scan.
- **Không queue async** — sync XAMPP, timeout 60s.

---

## Bảng tiến độ nhanh

| Khối | Mô tả ngắn | Code | User test | User confirm | Commit |
|------|------------|------|-----------|--------------|--------|
| F0 | Config OpenAI + ai_config | ✅ | ✅ | ✅ | ⬜ |
| F1 | PDF quality router | ⬜ | ⬜ | ⬜ | ⬜ |
| F2 | OpenAiCvVisionParserService + schema + prompt | ⬜ | ⬜ | ⬜ | ⬜ |
| F3 | CvParseService integrate | ⬜ | ⬜ | ⬜ | ⬜ |
| F4 | cv-import toggle + builder banner | ⬜ | ⬜ | ⬜ | ⬜ |
| F5 | Rate limit GPT | ⬜ | ⬜ | ⬜ | ⬜ |
| F6 | Test 4 PDF mẫu | ⬜ | ⬜ | ⬜ | ⬜ |
| F7 | DOCX (optional — defer) | ⏸️ | — | — | — |
| F8 | Docs + regression CV-E | ⬜ | ⬜ | ⬜ | ⬜ |

---

## F0 — Config OpenAI (~0.5 ngày)

**Mục tiêu:** Máy dev đọc được OpenAI key; `ai_openai_ready()` — chưa gọi vision.

| # | Làm gì | File | Verify |
|---|--------|------|--------|
| F0.1 | Mở rộng `ai.example.php` — block `openai` | `config/ai.example.php` | model, timeout, max_pdf_pages |
| F0.2 | `ai_openai_ready()` + `base_url` ShopAIKey | `includes/ai_config.php` | default `api.shopaikey.com/v1` |
| F0.3 | User điền `openai.api_key` | `config/ai.local.php` | `ai_openai_ready()` → true |
| F0.4 | Script test curl/CLI (dev only) | `docs/migrations/_test-openai-config.php` | HTTP 401 vs 200 ping |

**Pass F0:** Config load OK; thiếu key → không crash import (fallback text path).

---

## F1 — Smart router (~0.5 ngày)

| # | Làm gì | File | Verify |
|---|--------|------|--------|
| F1.1 | `cv_import_analyze_pdf_quality()` | `includes/cv_import_pdf_quality.php` | score + signals |
| F1.2 | Route: auto / text / vision | `CvParseService` | meta.parse_mode |
| F1.3 | Scan (len<80) → vision | service | T3 PDF → vision_gpt |

**Pass F1:** Unit logic: text sạch → text_fast; scan → vision_gpt.

---

## F2 — GPT Vision parser (~1–1.5 ngày)

| # | Làm gì | File | Verify |
|---|--------|------|--------|
| F2.1 | JSON Schema strict | `includes/cv_parse_schema.json` | khớp CV-D fields |
| F2.2 | Vision prompts | `includes/cv_parse_vision_prompt.php` | layout-aware rules |
| F2.3 | OpenAI Responses API | `OpenAiCvVisionParserService.php` | PDF → JSON |
| F2.4 | Retry 429/5xx + invalid JSON | service | 1 retry |
| F2.5 | Test script | `_test-cv-vision-parse.php` | 1 PDF → draft |

**Pass F2:** 1 PDF Canva hoặc scan → JSON có full_name + ≥1 section child.

---

## F3 — Pipeline integrate (~0.5 ngày)

| # | Làm gì | File | Verify |
|---|--------|------|--------|
| F3.1 | `importFromPdfPath($path, $options)` | `CvParseService.php` | chain đầy đủ |
| F3.2 | GPT fail → text path / fallback | service | warnings[] |
| F3.3 | meta: parse_mode, provider | session draft | builder đọc được |

**Pass F3:** Full pipeline script với 3 mode auto/text/vision.

---

## F4 — UI (~0.5 ngày)

| # | Làm gì | File | Verify |
|---|--------|------|--------|
| F4.1 | Radio parse mode | `cv-import.php` | auto / text / vision |
| F4.2 | Cảnh báo thiếu OpenAI key | GET import | vẫn upload text path |
| F4.3 | Banner parse_mode | `cv-builder.php` | hiển thị GPT vs Groq |

**Pass F4:** Upload + chọn mode → builder banner đúng.

---

## F5 — Rate limit GPT (~0.25 ngày)

| # | Làm gì | File | Verify |
|---|--------|------|--------|
| F5.1 | Max 3 GPT vision / user / giờ | `cv_import_rules.php` | lần 4 bị chặn |
| F5.2 | Text path vẫn 5/h (CV-E) | rules | không regression |

**Pass F5:** Spam GPT import → SweetAlert rate limit.

---

## F6 — Test PDF bộ mẫu (~0.5–1 ngày)

| PDF | Kỳ vọng |
|-----|---------|
| T1 TopCV text | auto → text_fast, ≈ CV-E |
| T2 Canva 2 cột | vision, ≥1 KN/HV đúng tên |
| T3 Scan ảnh | vision, có contact |
| T4 CV EN 2 trang | vision, dates YYYY-MM |

**Pass F6:** User tick 4 PDF + Lưu → preview OK.

---

## F7 — DOCX (optional — defer)

⏸️ Không làm trong MVP trừ khi user yêu cầu sau F6 pass.

---

## F8 — Docs + regression (~0.5 ngày)

| # | Làm gì | File |
|---|--------|------|
| F8.1 | Setup OpenAI | `docs/setup-cv-import.md` |
| F8.2 | Learning log | `docs/dev-learning-log.md` |
| F8.3 | Roadmap CV-F | `docs/structured-cv-roadmap.md` |
| F8.4 | Regression CV-E text import | manual |
| F8.5 | Builder thủ công, apply snapshot | manual |

**Pass F8 / phase:** User **`「CV-F pass」`** → PR.

---

## Checkpoint log

| Ngày | Sự kiện |
|------|---------|
| 2026-06-05 | User **`「xác nhận CV-F」`**; nhánh `feature/phase-cv-f-vision` tạo từ `main` |
| 2026-06-05 | User **`「bắt đầu code CV-F」`** → F0 code xong |
| 2026-06-05 | Fix ShopAIKey `base_url`; user **`「F0 pass」`** + commit |
