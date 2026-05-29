# GitHub Workflow — TopCV Lite

> Quy trình làm việc chuyên nghiệp khi đẩy code lên GitHub (solo hoặc nhóm nhỏ).

---

## 1. Cấu trúc nhánh

| Nhánh | Mục đích |
|-------|----------|
| `main` | Code ổn định, đã test manual trên XAMPP |
| `feature/phase-1-<nhóm>` | Một nhóm fix Phase 1 (vd. `feature/phase-1-2b-csrf`) |
| `fix/<mô-tả-ngắn>` | Sửa lỗi nhỏ ngoài phase (tùy chọn) |

**Không** push trực tiếp lên `main` khi đang refactor Phase 1 — dùng Pull Request.

---

## 2. Vòng đời một nhóm fix (ví dụ Nhóm 2B)

1. `git checkout main && git pull`
2. `git checkout -b feature/phase-1-2b-csrf`
3. Mini-plan → user confirm → code
4. Test manual (checklist trong `docs/dev-learning-log.md`)
5. Cập nhật `docs/project-memory/*`
6. `git add` + `git commit -m "phase 1: ... (nhóm 2B)"`
7. `git push -u origin feature/phase-1-2b-csrf`
8. Tạo **Pull Request** trên GitHub → `main`
9. Review (tự review nếu solo) → **Merge**
10. Xóa branch feature (tùy chọn)

---

## 3. Commit message

Giữ format dự án:

```
phase <số>: <nội dung chính> (nhóm X)
```

Ví dụ:

- `phase 1: mở rộng CSRF cho auth employer admin (nhóm 2B)`
- `chore: cập nhật README hướng dẫn deploy`

---

## 4. Push lên GitHub lần đầu

1. Tạo repo trên GitHub (khuyên **Private** lúc đầu).
2. **Không** tick "Add README" nếu repo local đã có README.
3. Trên máy (đã đổi nhánh `main`):

```bash
git remote add origin https://github.com/<username>/topcv-lite.git
git push -u origin main
```

SSH: `git@github.com:<username>/topcv-lite.git`

---

## 5. Bảo vệ nhánh `main` (GitHub Settings)

Đề xuất bật:

- **Require a pull request before merging**
- **Do not allow bypassing** (kể cả admin — thói quen tốt)
- Tùy chọn: **Require branches to be up to date**

Solo dev vẫn nên dùng PR để có lịch sử review và diff rõ ràng.

---

## 6. File không được commit

| File / pattern | Lý do |
|----------------|--------|
| `config/db.local.php` | Credential local |
| `.env` | Biến môi trường |
| `uploads/cv/*.pdf`, logo user | Dữ liệu người dùng |
| `_git_staging_backup/` | Tạm khi tách commit |

Xem `.gitignore` tại root.

---

## 7. CI (tùy chọn — giai đoạn sau)

File `.github/workflows/php-lint.yml` có thể chạy `php -l` trên mỗi PR. Bật khi team ổn định hơn.

---

## 8. Liên kết nội bộ

- [git-checkpoint-workflow.md](project-memory/git-checkpoint-workflow.md) — checkpoint sau mỗi nhóm
- [session-handoff.md](project-memory/session-handoff.md) — trạng thái phiên làm việc
- [master-refactor-roadmap.md](master-refactor-roadmap.md) — lộ trình refactor
