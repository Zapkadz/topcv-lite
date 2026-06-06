# Current Task — TopCV Lite

> Cập nhật: **2026-06-05**

---

## Trạng thái

| Phase | Status |
|-------|--------|
| CV-A … CV-D | ✅ merged / pass |
| **CV-E** | ✅ pass — merged `main` (PR #12) |
| **CV-F** | ✅ **F0 pass** — chờ code **F1** |

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
- [ ] Chuẩn bị 4 PDF test — có sẵn trong `uploads/cv/` và `uploads/cv/import/`
- [ ] Chờ AI làm **F1** (smart router)

### Quy trình (giống CV-E)

```text
F0 → test → 「F0 pass」 → (commit nếu yêu cầu) → F1 → … → F8 → 「CV-F pass」 → PR
```

F0 đã commit; khối tiếp theo **F1**.

---

## Phase CV-E (đã đóng)

- **Plan:** `docs/phase-cv-e-plan.md`
- **Checklist:** `docs/project-memory/phase-cv-e-checklist.md`
- **Setup:** `docs/setup-cv-import.md`
- **Merge:** PR #12 → `main` @ `ca974de`
