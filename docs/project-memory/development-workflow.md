# Development Workflow — TopCV Lite

> Quy trình bắt buộc cho **mọi task** (feature, fix, refactor). AI và dev tuân thủ theo thứ tự.

---

## Tóm tắt nhanh

```text
Đánh giá phạm vi → Tạo file phase plan (docs/) → CHỜ user xác nhận
→ Code → Test → Refactoring plan → (tuỳ chọn refactor) → Commit/push (khi user đồng ý)
```

**Không code trước khi có file plan và user xác nhận.**

---

## Bước 0 — Đánh giá phạm vi

- Task **nhỏ** → gom **1 phase** (VD: `CV-G`).
- Task **lớn** → chia **nhiều phase nhỏ** (VD: `VIP-1`, `VIP-2`).

---

## Bước 1 — Tạo phase plan **trước** (bắt buộc, làm nhanh)

**Luôn tạo file plan trong repo trước khi sửa bất kỳ file code nào.**

| Việc | Chi tiết |
|------|----------|
| **File** | `docs/phase-<mã>-plan.md` (VD: `docs/phase-cv-g-plan.md`) |
| **Thời điểm** | Ngay sau khi phân tích yêu cầu — **không code song song** |
| **Nội dung tối thiểu** | Mục tiêu · phạm vi · flow cũ/mới · file đọc/sửa/tạo · DB · rủi ro · checklist test |
| **Cập nhật** | `docs/project-memory/current-task.md` — ghi phase đang chờ xác nhận |

**Dừng lại.** Chờ user xác nhận (VD: `「xác nhận CV-G plan」`).

---

## Bước 2 — Code (chỉ sau khi user xác nhận plan)

- Nhánh: `feature/<mô-tả-ngắn>` từ `main`.
- Chỉ làm đúng phạm vi trong plan.
- Không mở rộng scope không được duyệt.

---

## Bước 3 — Báo cáo sau code

- Tóm tắt đã làm gì.
- Liệt kê file sửa/tạo.
- Hướng dẫn test thủ công (theo checklist trong plan).

**Dừng lại.** Chờ user **`「<phase> pass」`** / xác nhận test pass.

---

## Bước 4 — Refactoring plan (bắt buộc trước khi đóng phase)

Tạo file:

```text
/docs/refactoring/phase-XX-refactoring-plan.md
```

Nguyên tắc:

- No behavior changes
- Keep API compatibility
- Prioritize removing duplication
- Split oversized files
- Improve naming
- Add missing tests
- Show the plan before editing

**Hỏi user:** *「Bạn có muốn thực hiện refactor theo plan này không？」*

| User | Hành động |
|------|-----------|
| **Không** | Bỏ refactor → kết thúc phase |
| **Có** | Refactor (không đổi behavior) · cập nhật `docs/dev-learning-log.md` · test lại · chờ pass |

---

## Bước 5 — Git (chỉ khi test pass + user đồng ý)

- `git status` / diff
- Commit message theo convention dự án
- Push + đề xuất PR (không tự merge trừ khi user yêu cầu)

Chi tiết checkpoint: `docs/project-memory/git-checkpoint-workflow.md`  
GitHub branch/PR: `docs/github-workflow.md`

---

## Mẫu tên file

| Loại | Pattern | Ví dụ |
|------|---------|--------|
| Phase plan | `docs/phase-<mã>-plan.md` | `docs/phase-cv-g-plan.md` |
| Refactoring plan | `docs/refactoring/phase-<mã>-refactoring-plan.md` | `docs/refactoring/phase-CV-G-refactoring-plan.md` |
| Checklist (tuỳ chọn) | `docs/project-memory/phase-<mã>-checklist.md` | `docs/project-memory/phase-cv-g-checklist.md` |

---

## Tín hiệu user (gợi ý)

| User gửi | Ý nghĩa |
|----------|---------|
| Mô tả task / `「bắt đầu …」` | Bước 0–1: phân tích + **tạo file plan** — chưa code |
| `「xác nhận <phase> plan」` | Được phép code (Bước 2) |
| `「<phase> pass」` | Test OK — sang refactoring plan / đóng phase |
| `「commit …」` | Được phép git commit/push |
