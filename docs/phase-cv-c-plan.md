# Phase CV-C — Apply snapshot + Employer xem CV structured

> **Xác nhận phase:** `「xác nhận CV-C」` — 2026-05-29  
> **Phụ thuộc:** CV-A (`CvService`, schema), CV-B (UI + `cv-preview`, validation)  
> **Tài liệu UX:** `docs/phase-cv-0-ux-spec.md` §3.3, §3.4, §4 (luồng 6–7)

---

## 0. Quy trình (đọc kỹ)

| Bước | Ai | Việc |
|------|-----|------|
| 1 | User | `「xác nhận CV-C」` ✅ |
| 2 | Dev | Soạn file plan này ✅ |
| 3 | **User** | **`「bắt đầu code CV-C」`** ✅ |
| 4 | Dev | Code ✅ — chờ test |
| 5 | User | Test → `「CV-C pass」` → commit |

**Đã code** — chờ `「CV-C pass」`.

---

## 1. Mục tiêu

| Hiện tại | Sau CV-C |
|----------|----------|
| Apply "CV online" = copy file `candidates.cv_path` (PDF) | Apply chọn **bản `cv_profiles`** → lưu **JSON snapshot** bất biến |
| NTD chỉ mở link file PDF | NTD xem **CV structured** (read-only) từ snapshot + link file nếu có |
| Sửa CV sau apply → NTD có thể thấy bản mới (nếu dùng file gốc) | NTD **luôn** thấy bản lúc ứng tuyển |

**Apply (CV-C):** chỉ chọn **một CV** trong **Quản lý CV online** (`cv_profiles`) — không upload file tại modal ứng tuyển.

**Upload PDF/DOC:** chuyển về **Quản lý CV online** (tạo/sửa CV) — phase sau **scan → điền form** (CV-E/F). CV-C **không** làm parse.

**Regression:** đơn ứng tuyển **cũ** (chỉ có `cv_snapshot` file) — employer vẫn mở PDF.

**Không làm (CV-D+):** template thứ 2, section đầy đủ, parse file, export PDF.

**Đã có CV-B (không làm lại):** `candidate/cv-preview.php` classic — CV-C chỉ **tái sử dụng** renderer cho employer.

---

## 2. Database

### 2.1 Migration `applications`

```sql
ALTER TABLE `applications`
  ADD COLUMN `cv_profile_id` int(11) DEFAULT NULL COMMENT 'FK cv_profiles lúc apply' AFTER `candidate_id`,
  ADD COLUMN `cv_snapshot_json` longtext DEFAULT NULL COMMENT 'Bản CV structured bất biến' AFTER `cv_snapshot`,
  MODIFY COLUMN `cv_snapshot` varchar(255) DEFAULT NULL COMMENT 'Đường dẫn file nếu apply upload';

ALTER TABLE `applications`
  ADD KEY `idx_applications_cv_profile` (`cv_profile_id`),
  ADD CONSTRAINT `applications_cv_profile_fk`
    FOREIGN KEY (`cv_profile_id`) REFERENCES `cv_profiles` (`id`) ON DELETE SET NULL;
```

| Cột | Apply mới (CV-C) | Đơn cũ (trước CV-C) |
|-----|------------------|---------------------|
| `cv_profile_id` | ID CV đã chọn | `NULL` |
| `cv_snapshot_json` | JSON `buildSnapshotJson()` | `NULL` |
| `cv_snapshot` | `NULL` | path file (giữ nguyên) |

> **Lưu ý:** `cv_snapshot` hiện **NOT NULL** — migration phải đổi **NULL** để apply chỉ structured không bắt buộc file giả.

### 2.2 File migration

| File | Mô tả |
|------|--------|
| `docs/migrations/phase-cv-c-apply-snapshot.sql` | SQL trên |
| `docs/migrations/migrate-phase-cv-c.php` | Chạy localhost (giống CV-A/B) |
| `includes/schema_applications_cv.php` | `applications_cv_columns_ready()` |
| `topcv_lite.sql` | Cập nhật dump |

### 2.3 Dữ liệu cũ

- Application cũ chỉ có `cv_snapshot` (file) → employer vẫn mở PDF như hiện tại (`cv_snapshot_json` = NULL).
- Không migrate JSON ngược cho đơn cũ.

---

## 3. Service layer

### 3.1 `CvService` (mở rộng)

```php
/**
 * @return array{ok: bool, message: string, cv_profile_id: int|null, snapshot_json: string|null}
 */
public static function snapshotForApply(PDO $conn, int $userId, int $cvProfileId): array
```

- Kiểm tra `cvs_schema_ready`, CV thuộc `userId`.
- Gọi `buildSnapshotJson($conn, $cvProfileId)`.
- Trả về JSON hoặc lỗi (CV không tồn tại / không quyền).

### 3.2 `ApplicationService` (mới, khuyến nghị)

Tách logic `apply.php` dài → `includes/services/ApplicationService.php`:

```php
public static function applyToJob(
    PDO $conn,
    int $userId,
    int $jobId,
    int $cvProfileId,
    string $coverLetter
): array
```

- Validate job mở (`job_is_open_for_apply`).
- Resolve / tạo `candidate_id`.
- Chống apply trùng.
- `snapshotForApply` → INSERT `cv_profile_id`, `cv_snapshot_json`; `cv_snapshot` = NULL.
- CSRF validate **ở `apply.php`** trước khi gọi service (giữ pattern hiện tại).

---

## 4. UI Candidate — `job-detail.php` + `apply.php`

### 4.1 Modal apply (wireframe — đã chốt)

```
Ứng tuyển: [Tên job]
────────────────────────────────────
Chọn CV để nộp * 
    [dropdown: CV IT Fresher ★ | CV Marketing | ...]
    ★ = CV mặc định (pre-select, đổi được)
    [Xem trước] → cv-preview.php?id= (tab mới)

    (Nếu chưa có CV: không submit được)
    → "Bạn chưa có CV online" + [Tạo CV] → cv-manage.php

Thư giới thiệu: [textarea]

[Gửi hồ sơ]
```

**Không có** trên modal apply: upload file, radio `cv_type`, `candidates.cv_path`.

### 4.2 Thay đổi kỹ thuật

| File | Thay đổi |
|------|----------|
| `job-detail.php` | Load `CvService::listForUser()`; dropdown `cv_profile_id` (required); pre-select `is_primary`; link Xem trước; **gỡ** radio upload / copy PDF |
| `apply.php` | POST `cv_profile_id` + `cover_letter`; `ApplicationService::applyToJob()`; bỏ `cv_type`, bỏ `$_FILES` |

### 4.3 Validation apply

| Rule | Thông báo |
|------|-----------|
| Không chọn `cv_profile_id` | Vui lòng chọn CV để nộp |
| CV không thuộc user | Không có quyền / CV không tồn tại |
| Chưa có CV nào | Chưa có CV online — tạo CV trước khi ứng tuyển |
| Schema CV chưa có | Thông báo migration + chặn apply |

### 4.4 Upload CV — không nằm ở apply (lộ trình)

| Giai đoạn | Vị trí | Hành vi |
|-----------|--------|---------|
| **CV-C** | `cv-manage` / `cv-builder` | Chỉ form thủ công (như CV-B). Có thể thêm nút **"Tải CV (sắp có)"** disabled + tooltip |
| **CV-E/F** | `cv-manage` → Tạo CV | Upload PDF/DOC → **scan/parse** → điền sẵn các field → user chỉnh → Lưu |
| **profile.php** | Hồ sơ cũ | Giữ `cv_path` tạm hoặc link "Quản lý CV online" — deprecate dần |

`cv_profiles.attachment_path` (nếu cần) lưu file gốc sau import — phase E, không CV-C.

---

## 5. UI Employer — `employer/applicants.php`

### 5.1 Cột "Hồ sơ"

| Loại đơn | Nút |
|----------|-----|
| Có `cv_snapshot_json` | **Xem CV online** (modal) |
| Có `cv_snapshot` (file) | **Tải file CV** (link, như cũ) |
| Cả hai (hiếm, tương lai) | Cả hai nút |

### 5.2 Modal (Bootstrap)

```
[Hồ sơ: Nguyễn Văn A — CV IT Fresher]
────────────────────────────────────
[Tab: CV online] | [Tab: File đính kèm]   ← Tab File ẩn nếu không có file

Tab CV online:
  → render read-only (dùng cv_render_snapshot_from_json())
Tab File:
  → iframe/link cv_snapshot
```

### 5.3 Render snapshot

| File | Mô tả |
|------|--------|
| `includes/cv_preview_render.php` | Thêm `cv_render_snapshot_from_json(string $json): string` — decode JSON, gọi lại `cv_render_preview_html()` |
| Hoặc `includes/cv_snapshot_view.php` | Helper parse + escape an toàn |

**Bảo mật:** Employer chỉ xem application thuộc job của company mình (query đã filter `company_id` — thêm check khi mở modal theo `app_id` nếu cần).

---

## 6. CSRF & bảo mật

| form_key | Trang | Ghi chú |
|----------|-------|---------|
| `apply_job_form` | job-detail → apply.php | **Giữ** |
| `employer_applicant_status_form` | applicants | **Giữ** |

- `cv_profile_id` POST: server validate ownership (không tin client).
- JSON snapshot: chỉ **ghi** lúc apply, employer **không** sửa.

---

## 7. Luồng test (checklist)

- [ ] Chạy `migrate-phase-cv-c.php`
- [ ] User có 2 CV → apply job A chọn CV1 → employer modal thấy đúng CV1
- [ ] Ứng viên **sửa CV1** sau apply → employer vẫn thấy nội dung cũ (snapshot)
- [ ] Apply job B chọn CV2 → data khác CV1
- [ ] Chưa có CV → không apply được; link `cv-manage.php`
- [ ] Đơn **cũ** (chỉ file PDF) → employer vẫn mở PDF
- [ ] Apply trùng job → cảnh báo như cũ
- [ ] Đơn apply **cũ** (chỉ có file) → employer vẫn mở PDF bình thường
- [ ] CSRF / apply khi job đóng / employer tự apply → chặn như cũ

---

## 8. File dự kiến (khi code)

| Loại | File |
|------|------|
| Migration | `phase-cv-c-apply-snapshot.sql`, `migrate-phase-cv-c.php`, `schema_applications_cv.php` |
| Service | `ApplicationService.php`, mở rộng `CvService.php` |
| Candidate | `job-detail.php`, `apply.php` |
| Employer | `employer/applicants.php`, có thể `assets/js/applicant-cv-modal.js` |
| Render | mở rộng `cv_preview_render.php` |
| Docs/SQL | `topcv_lite.sql`, `dev-learning-log.md`, `current-task.md` |

**Ước lượng:** ~8–12 file, không thêm trang mới (preview đã có).

---

## 9. Git

**Nhánh gợi ý:** `feature/phase-cv-c-apply` (tách từ `main` sau merge CV-B)

**Commit message:**
```
phase CV: apply snapshot và employer xem CV structured (nhóm CV-C)
```

---

## 10. Sau CV-C

| Phase | Nội dung |
|-------|----------|
| **CV-D** | Section đầy đủ, 2 template, polish editor |
| **CV-E** | Export PDF, `attachment_path` |

---

## 11. Quyết định sản phẩm (đã chốt theo user)

### Câu 1 — Pre-select CV mặc định?

**Đề xuất: Có pre-select, không bắt buộc dùng mặc định.**

| | |
|--|--|
| **Làm** | Dropdown liệt kê mọi CV trong Quản lý CV online; **chọn sẵn** CV có `is_primary = 1` (kèm ★ trong label). |
| **User** | Được đổi sang CV khác trước khi Gửi — mỗi lần apply **chỉ một** `cv_profile_id`. |
| **Lý do** | Tiện (đa số nộp CV chính), linh hoạt (nộp CV theo từng job), khớp đa CV. |

### Câu 2 — PDF hồ sơ cũ / upload trong modal apply?

**Đề xuất: Không — apply chỉ chọn CV structured.**

| | |
|--|--|
| **Apply** | Chỉ dropdown `cv_profiles` — không upload, không `candidates.cv_path`. |
| **Upload** | Chuyển sang **Quản lý CV online** → sau này upload + **scan điền form** (CV-E/F), không nhập tay từ đầu. |
| **Lý do** | Một luồng rõ: tạo/chuẩn hóa CV ở hub → apply chọn bản → snapshot JSON; tránh 3 cách nộp CV gây nhầm. |

### Câu 3 — Employer xem CV: modal hay trang riêng?

**Đề xuất: Modal (Bootstrap) trên `applicants.php`.**

| | |
|--|--|
| **Làm** | Nút "Xem CV online" → modal render `cv_snapshot_json`; tab/link file chỉ khi đơn cũ có `cv_snapshot`. |
| **Sau** | Có thể thêm `employer/view-application.php?id=` nếu cần in/fullscreen — không bắt buộc CV-C. |
| **Lý do** | Đủ demo đồ án, ít file, NTD không rời danh sách ứng viên. |

---

Nếu đồng ý → gửi **`「bắt đầu code CV-C」`** (theo plan đã cập nhật §4, §11).
