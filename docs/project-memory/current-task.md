# Current Task — TopCV Lite

> Cập nhật: **2026-05-29**

---

## Trạng thái

| Phase | Status |
|-------|--------|
| Phase 2 (2A–2D) | ✅ |
| CV-0 | ✅ |
| CV-A | ✅ pass |
| **CV-B** | ✅ **`「CV-B pass」`** — chờ user commit |
| **CV-C** | Chờ **`「xác nhận CV-C」`** |

---

## Bước tiếp theo (theo quy trình)

### Ngay bây giờ (bạn làm)

1. ~~**`「CV-B pass」`**~~ ✅
2. **Commit git** (tự commit), gợi ý message:
   - `phase CV: UI quản lý và builder CV online (nhóm CV-B)`
   - (hoặc 2 commit: UI + `fix: validation SĐT, tháng/năm, avatar, preview`)
3. Chạy migration nếu chưa: `docs/migrations/migrate-phase-cv-b-formats.php`
4. Push / PR lên `main` (tùy chiến lược nhánh hiện tại)

### Sau đó (bắt đầu CV-C)

5. Gửi **`「xác nhận CV-C」`** → mình soạn `docs/phase-cv-c-plan.md` → **chờ bạn đọc** → mới code
6. Phạm vi **CV-C** (MVP bắt buộc — xem `docs/structured-cv-roadmap.md`):
   - Migration: `applications.cv_profile_id`, `cv_snapshot_json`
   - **Apply:** `job-detail.php` / `apply.php` — dropdown chọn CV online + snapshot khi nộp
   - **Employer:** `employer/applicants.php` — xem CV structured từ snapshot (read-only)
   - Regression: vẫn apply bằng upload file PDF/DOC
7. Test xong → **`「CV-C pass」`** → commit → CV-D (section đầy đủ + template đẹp) nếu cần cho đồ án

**Lưu ý:** Preview classic + nút Xem đã có ở CV-B; CV-C tập trung **luồng ứng tuyển + NTD**, không làm lại editor.

**Tham chiếu:** `docs/phase-cv-0-ux-spec.md`, `docs/structured-cv-roadmap.md`
