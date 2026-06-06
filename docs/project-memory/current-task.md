# Current Task — TopCV Lite

> Cập nhật: **2026-06-05**

---

## Trạng thái

| Phase | Status |
|-------|--------|
| CV-A … CV-D | ✅ merged / pass |
| **CV-E** | ✅ pass — merged `main` (PR #12) |
| **CV-F** | ✅ **F6 pass** — CV-F done (F7/F8 bỏ qua) → sẵn sàng PR |

---

## Phase CV-F (Mức C — GPT Vision scan PDF)

- **Plan:** `docs/phase-cv-f-plan.md`
- **Checklist:** `docs/project-memory/phase-cv-f-checklist.md`
- **Setup (mở rộng):** `docs/setup-cv-import.md` (sẽ bổ sung OpenAI khi code F0)
- **Nhánh:** `feature/phase-cv-f-vision` (đã tạo từ `main`)
- **Provider vision:** OpenAI `gpt-4o` — `config/ai.local.php` → block `openai` (gitignore)
- **Provider text (CV-E giữ nguyên):** Groq / Gemini — path nhanh qua router

### Trước khi code

- [x] OpenAI/ShopAIKey key trong `ai.local.php` (block `openai`)
- [x] **`「F0 pass」`** — `_test-openai-config.php` OK
- [x] **`「F1 pass」`** — smart router
- [x] **`「F2 pass」`** — GPT vision (ShopAIKey)
- [x] **`「F4 pass」`** — UI chọn Text-base / Chuẩn GPT
- [x] **`「F5 pass」`** — quota GPT 5/tổng đời (DB)
- [x] **`「F6 pass」`** — 4 loại PDF CLI + web OK
- ~~F7 DOCX~~ / ~~F8 docs~~ — **bỏ qua** (user chốt)

### Quy trình

```text
F0–F6 pass + commit → 「CV-F pass」 → PR (không F7/F8)
```

F0–F6 committed trên nhánh `feature/phase-cv-f-vision`.

---

## Phase CV-E (đã đóng)

- **Plan:** `docs/phase-cv-e-plan.md`
- **Checklist:** `docs/project-memory/phase-cv-e-checklist.md`
- **Setup:** `docs/setup-cv-import.md`
- **Merge:** PR #12 → `main` @ `ca974de`
