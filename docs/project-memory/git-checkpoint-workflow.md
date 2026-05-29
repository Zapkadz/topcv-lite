# Git Checkpoint Workflow

> Quy trình bắt buộc sau khi hoàn thành mỗi **phase** hoặc **nhóm con** trong Phase 1 (1, 4, 2A, 2B, 3).

---

## Khi nào thực hiện

- Sau khi user xác nhận **test pass** cho nhóm/phase vừa xong.
- **Không** chuyển sang nhóm/phase tiếp theo nếu chưa qua Git checkpoint (hoặc user từ chối checkpoint có lý do).

---

## 6 bước Git checkpoint

| # | Bước | Ai làm |
|---|------|--------|
| 1 | `git status` — kiểm tra thay đổi | AI chạy + báo cáo |
| 2 | Tóm tắt file đã thay đổi (`git diff --stat` hoặc liệt kê) | AI |
| 3 | Xác nhận test phase/nhóm đã pass | User (đã confirm trước đó) |
| 4 | Cập nhật `docs/project-memory/*` | AI |
| 5 | Gợi ý commit message theo format | AI |
| 6 | **Chỉ commit khi user xác nhận** | User confirm → AI chạy `git add` + `git commit` |

---

## Format commit message

```
phase <số>: <nội dung chính>
```

**Ví dụ (Phase 1 — từng nhóm):**
- `phase 1: chặn apply trùng với unique constraint (nhóm 1)`
- `phase 1: sửa runtime profile candidate (nhóm 4)`
- `phase 1: thêm CSRF cho apply và profile (nhóm 2A)`
- `phase 1: mở rộng CSRF cho auth employer admin (nhóm 2B)`
- `phase 1: hardening upload CV và logo (nhóm 3)`

**Ví dụ (khi đóng cả Phase 1):**
- `phase 1: hoàn tất critical fixes (security + data integrity)`

---

## Lưu ý dự án TopCV Lite

- ~~Tính đến 2026-05-29: thư mục **chưa có** `.git`~~ — đã `git init` 2026-05-29; 4 commit option B (nhóm 1, 4, 2A, docs).
- Không commit file nhạy cảm: `.env`, credential, dump DB có password thật.
- `uploads/cv/*`, `uploads/logos/*` — đã gitignore; giữ thư mục bằng `.gitkeep`.
- `config/db.local.php` — override local, không commit; mẫu: `config/db.example.php`.
- Quy trình GitHub (branch, PR, push): `docs/github-workflow.md`.

---

## Checkpoint log

| Ngày | Scope | Test | Commit | Ghi chú |
|------|-------|------|--------|---------|
| 2026-05-29 | Nhóm 1+4+2A + docs audit | ✅ Pass | ✅ `94dda96` `f712836` `c3fd3b0` + docs commit | Init git, 4 commit tách (option B) |
| 2026-05-29 | Chuẩn bị GitHub pro | — | ✅ `8ec806f` | README, db.local, `main`, PR template, php-lint CI |
