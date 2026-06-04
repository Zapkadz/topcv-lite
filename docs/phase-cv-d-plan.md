# Phase CV-D — Section đầy đủ + template + polish UX

> **Xác nhận:** `「xác nhận CV-D」` — 2026-05-29  
> **Phụ thuộc:** CV-A (schema lõi), CV-B (builder/preview/avatar), CV-C (apply snapshot + employer xem)  
> **Tham chiếu:** `docs/phase-cv-0-ux-spec.md` §3.2, §5, `docs/structured-cv-roadmap.md` (Nhóm CV-D)

---

## Quy trình phase

| # | Việc | Trạng thái |
|---|------|------------|
| 1 | User `「xác nhận CV-D」` | ✅ |
| 2 | Đọc plan này | **Bạn** |
| 3 | Nhánh `feature/phase-cv-d-sections` từ `main` (đã có CV-C) | **Bạn** |
| 4 | User `「bắt đầu code CV-D」` | Chờ |
| 5 | Code + migration + test | AI |
| 6 | User `「CV-D pass」` → commit → push → PR | Bạn + AI (commit khi được yêu cầu) |

**Nhánh bắt buộc:** `feature/phase-cv-d-sections` — **không** code trên `feature/phase-cv-b-ui`.

---

## Mục tiêu

Hoàn thiện CV structured “đủ section TopCV”: form builder + preview + snapshot apply/employer đồng bộ; user chọn **classic** hoặc **modern** khi lưu.

---

## Phạm vi (làm)

### 1. Migration DB

| Bảng | Cột chính | Ghi chú |
|------|-----------|---------|
| `cv_activities` | `cv_id`, `start_date`, `end_date` char(7), `organization`, `role`, `description`, `sort_order` | Giống pattern KN |
| `cv_certificates` | `cv_id`, `issued_at` char(7), `certificate_name`, `sort_order` | Tháng+Năm UI |
| `cv_awards` | `cv_id`, `awarded_at` char(7), `title`, `description`, `sort_order` | Tháng+Năm UI |
| `cv_references` | `cv_id`, `full_name`, `position`, `contact_info`, `sort_order` | Không có kỳ hạn |

- File: `docs/migrations/_cv-d-migrate-steps.php`, `migrate-phase-cv-d.php`, `phase-cv-d-sections.sql`
- `includes/schema_cvs.php` — thêm `cvs_extended_sections_ready()` (hoặc tương đương) kiểm tra 4 bảng
- Cập nhật `topcv_lite.sql`

### 2. Backend

| File | Thay đổi |
|------|----------|
| `includes/repositories/CvRepository.php` | list/insert/delete 4 section; mở rộng `deleteChildren()` |
| `includes/services/CvService.php` | `packFullProfile`, `normalizeChildren`, create/save — gồm section mới |
| `includes/cv_rules.php` | `cv_filter_*_rows`, `cv_parse_builder_post` (+ `interests`, `template_key`), `cv_estimate_completion_percent` |
| `includes/cv_preview_render.php` | Render đủ section; dispatcher theo `template_key` |

**Snapshot / apply (regression CV-C):**

- `buildSnapshotJson()` payload thêm: `activities`, `certificates`, `awards`, `references`
- `cv_render_snapshot_from_json()` (hoặc hàm employer tương đương) hiển thị section mới; đơn cũ thiếu key → bỏ qua section (backward compatible)

### 3. UI ứng viên

| File | Thay đổi |
|------|----------|
| `candidate/cv-builder.php` | Section: Hoạt động, Chứng chỉ, Giải thưởng, Người giới thiệu; textarea **Sở thích**; chọn **Mẫu CV** (`classic` / `modern`) |
| `assets/js/cv-builder.js` | Template clone 4 section; (tuỳ chọn) nút ↑↓ `sort_order` |
| `candidate/cv-preview.php` | Gọi render theo `template_key` |
| `assets/css/` (mới hoặc inline) | `.cv-preview-modern` — layout khác classic (sidebar / accent) |

### 4. Đã có ở CV-B — chỉ regression

| Tính năng | Ghi chú |
|-----------|---------|
| Avatar upload | `includes/cv_avatar.php` — đảm bảo hiện trên **cả 2** template |
| Confirm xóa CV | SweetAlert `cv-manage.php` |
| Badge % hoàn thiện | Cập nhật công thức khi có section mới |

---

## Không làm (CV-D)

- Parse PDF/DOC tự động → **CV-F**
- Upload `attachment_path` / export PDF → **CV-E**
- WYSIWYG từng ô, sidebar TopCV phức tạp
- AI matching JD

---

## Chuẩn field (đồng bộ CV-B)

| Loại | UI | DB |
|------|-----|-----|
| Kỳ hạn (activity, certificate, award) | Tháng (1–12) + Năm → `YYYY-MM` | `char(7)` |
| Reference | Họ tên, chức vụ, liên hệ (text) | varchar/text |
| Sở thích | 1 textarea trên profile | `cv_profiles.interests` (đã có cột) |
| Template | `<select name="template_key">` | `classic` \| `modern` |

Validation: giữ `cv_validate_submission`; section mới **tùy chọn** (chỉ validate format khi user nhập dòng không rỗng).

---

## CSRF

Không đổi key — vẫn `candidate_cv_save_form` trên `cv-builder.php`.

---

## Test checklist

**Chuẩn bị**

- [ ] `main` local đã pull, đang ở nhánh `feature/phase-cv-d-sections`
- [ ] Chạy: http://localhost/topcv_lite/docs/migrations/migrate-phase-cv-d.php

**Builder / preview**

- [ ] Tạo/sửa CV — nhập đủ 4 section mới + sở thích → Lưu → reload đúng data
- [ ] 2 CV cùng user — section tách theo `cv_id`
- [ ] Chọn **modern** → preview đổi layout; **classic** vẫn như cũ
- [ ] Avatar hiển thị trên cả 2 template
- [ ] % hoàn thiện tăng khi điền thêm section (manage list)

**Tích hợp CV-C (regression)**

- [ ] Apply job với CV vừa sửa → employer **CV online** thấy section mới trong snapshot
- [ ] Sửa CV sau apply → employer vẫn thấy bản snapshot cũ (không section mới)
- [ ] Đơn legacy (chỉ file PDF) → **File CV** vẫn mở

**Khác**

- [ ] Xóa CV → confirm; xóa CV mặc định → CV khác được gợi ý primary (đã có CV-B)
- [ ] User B không sửa/xem builder CV user A

---

## File dự kiến (tóm tắt)

**Mới:** `_cv-d-migrate-steps.php`, `migrate-phase-cv-d.php`, `phase-cv-d-sections.sql`, (tuỳ) `assets/css/cv-preview.css`

**Sửa:** `CvRepository.php`, `CvService.php`, `cv_rules.php`, `cv_preview_render.php`, `cv-builder.php`, `cv-builder.js`, `cv-preview.php`, `schema_cvs.php`, `topcv_lite.sql`, `dev-learning-log.md`, `project-memory/current-task.md`

---

## Git

```
phase CV: đầy đủ section và template CV (nhóm CV-D)
```

PR: `feature/phase-cv-d-sections` → `main`

---

## Sau CV-D

| Phase | Nội dung |
|-------|----------|
| **CV-E** | Upload PDF đính kèm, export PDF |
| **CV-F** | Parse file / AI (defer) |

User gửi **`「bắt đầu code CV-D」`** sau khi đã tạo nhánh và đọc xong plan.
