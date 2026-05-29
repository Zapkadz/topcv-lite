# TopCV Lite

Nền tảng tuyển dụng MVP (job portal) — PHP thuần, MySQL/MariaDB, Bootstrap 5, chạy trên XAMPP.

## Tính năng chính

- Ứng viên: đăng ký, hồ sơ/CV, ứng tuyển việc làm
- Nhà tuyển dụng: hồ sơ công ty, đăng/sửa tin, xem hồ sơ ứng viên
- Admin: duyệt employer & tin tuyển dụng, quản lý danh mục

## Yêu cầu

- [XAMPP](https://www.apachefriends.org/) (PHP 8.x, Apache, MySQL/MariaDB)
- Git

## Cài đặt nhanh (local)

1. Clone repo vào thư mục web, ví dụ:
   ```
   c:\xampp\htdocs\topcv_lite
   ```
2. Tạo database `topcv_lite` trong phpMyAdmin, import `topcv_lite.sql`.
3. Cấu hình DB (tùy chọn nếu khác mặc định XAMPP):
   ```bash
   copy config\db.example.php config\db.local.php
   ```
   Chỉnh `config/db.local.php` nếu cần (file này **không** được commit).
4. Đảm bảo thư mục ghi được:
   - `uploads/cv/`
   - `uploads/logos/`
5. Mở trình duyệt: `http://localhost/topcv_lite/`

## Cấu trúc thư mục

| Thư mục | Mô tả |
|---------|--------|
| `admin/` | Trang quản trị |
| `candidate/` | Khu vực ứng viên |
| `employer/` | Khu vực nhà tuyển dụng |
| `config/` | Kết nối CSDL (`db.php`, `db.local.php`) |
| `includes/` | Header, footer, CSRF helper |
| `docs/` | Audit, roadmap, project memory |
| `uploads/` | CV & logo (file người dùng — gitignore) |

## Quy trình phát triển

- Nhánh chính: `main` (code đã test)
- Mỗi nhóm fix Phase 1: branch `feature/phase-1-<tên-nhóm>` → PR → merge
- Commit message: `phase <số>: <mô tả> (nhóm X)`

Chi tiết: [docs/github-workflow.md](docs/github-workflow.md) và [docs/project-memory/git-checkpoint-workflow.md](docs/project-memory/git-checkpoint-workflow.md).

## Bảo mật & secrets

- **Không** commit `config/db.local.php`, `.env`, mật khẩu thật, file upload người dùng.
- Mặc định XAMPP (`root` / password rỗng) chỉ dùng cho môi trường local.

## Trạng thái dự án

Đang Phase 1 — critical fixes (unique apply, CSRF một phần, hardening upload). Xem `docs/master-refactor-roadmap.md`.

## License

MIT — xem [LICENSE](LICENSE).
