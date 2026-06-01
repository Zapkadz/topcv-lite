# Phase CV-0 — UX / IA CV có cấu trúc

> **Roadmap:** `docs/structured-cv-roadmap.md` — user **`「xác nhận CV roadmap」`** (2026-05-29)  
> **Phase này:** CHỈ tài liệu — không code PHP/migration.  
> **Bước tiếp:** User đọc file này → **`「xác nhận CV-0」`** → bắt đầu **CV-A**.

---

## 1. Mục tiêu UX

| Vấn đề cũ | Mục tiêu mới |
|-----------|--------------|
| Chỉ upload PDF/DOC | Form chuẩn, NTD đọc trên web |
| 1 file / ứng viên | **Nhiều CV** (cv1, cv2…) — data tách biệt |
| Apply = copy file | Apply = chọn bản CV + **snapshot JSON** bất biến |

**Nguyên tắc UI MVP:** Bootstrap 5, form dọc, nút **+ Thêm dòng** / **Xóa dòng** — **không** sidebar TopCV, **không** WYSIWYG từng ô (phase CV-D có template đẹp hơn).

---

## 2. Sơ đồ màn hình (IA)

```text
[Header candidate]
  ├── Hồ sơ & CV (profile.php) — bio + upload PDF cũ + list CV rút gọn
  ├── Quản lý CV online (cv-manage.php) — HUB chính
  └── Việc đã nộp (my-jobs.php) — không đổi

cv-manage.php
  ├── [+ Tạo CV mới] → cv-builder.php (không id)
  ├── Bảng/card: Tên CV | Cập nhật | Mặc định | Hành động
  │     ├── Sửa → cv-builder.php?id=
  │     ├── Xem trước → cv-preview.php?id=
  │     ├── Đặt làm mặc định (POST)
  │     └── Xóa (POST + confirm)
  └── Empty: "Chưa có CV — Tạo CV đầu tiên"

cv-builder.php?id=
  ├── Ô "Tên CV" (title) — bắt buộc
  ├── Section: Thông tin cá nhân
  ├── Section: Mục tiêu nghề nghiệp
  ├── Section: Học vấn (lặp)
  ├── Section: Kinh nghiệm (lặp)
  ├── Section: Kỹ năng (lặp) — MVP CV-A/B
  └── [Lưu CV] [Hủy → cv-manage]

cv-preview.php?id=
  └── HTML template classic (1 cột, in được)

job-detail.php (candidate)
  └── Modal apply: radio chọn CV online | upload file

employer/applicants.php
  └── Modal/tab: Xem CV structured (từ snapshot) + link file nếu có
```

---

## 3. Wireframe mô tả (text)

### 3.1 `candidate/cv-manage.php`

```
┌─────────────────────────────────────────────────────────┐
│  Quản lý CV online                    [+ Tạo CV mới]    │
├─────────────────────────────────────────────────────────┤
│  Tên CV          │ Cập nhật   │ Mặc định │ Thao tác    │
│  CV IT Fresher   │ 29/05/2026 │ ★ Chính  │ Sửa | Xem | Xóa │
│  CV Marketing    │ 28/05/2026 │          │ Sửa | Xem | Đặt mặc định | Xóa │
└─────────────────────────────────────────────────────────┘
```

### 3.2 `candidate/cv-builder.php`

```
┌─────────────────────────────────────────────────────────┐
│  Tạo / Sửa CV: [ Tên CV (vd: CV IT Fresher 2026)    ]  │
├─────────────────────────────────────────────────────────┤
│  THÔNG TIN CÁ NHÂN                                      │
│  Họ tên* | Vị trí ứng tuyển* | Email* | SĐT*          │
│  Ngày sinh | Giới tính | Website | Địa chỉ             │
│  [Avatar — CV-D]                                        │
├─────────────────────────────────────────────────────────┤
│  MỤC TIÊU NGHỀ NGHIỆP [textarea]                        │
├─────────────────────────────────────────────────────────┤
│  HỌC VẤN                          [+ Thêm học vấn]     │
│  ┌ Bắt đầu | Kết thúc | Trường | Ngành | Mô tả [Xóa] ┐ │
│  └ (lặp)                                              ┘ │
├─────────────────────────────────────────────────────────┤
│  KINH NGHIỆM                      [+ Thêm kinh nghiệm] │
│  ┌ Từ-Đến | Công ty | Vị trí | Mô tả            [Xóa] ┐ │
├─────────────────────────────────────────────────────────┤
│  KỸ NĂNG                          [+ Thêm kỹ năng]      │
│  ┌ Tên kỹ năng | Mô tả                           [Xóa] ┐ │
├─────────────────────────────────────────────────────────┤
│              [Lưu CV]  [Quay lại quản lý]               │
└─────────────────────────────────────────────────────────┘
```

### 3.3 Apply (`job-detail.php` modal)

```
Chọn CV để nộp:
  ( ) CV online: [dropdown: CV IT Fresher ★ | CV Marketing ]
  ( ) Tải file mới: [file input]
Thư giới thiệu: [textarea]
[Gửi hồ sơ]
```

### 3.4 Employer xem (`employer/applicants.php`)

```
Modal "Hồ sơ ứng viên"
  Tab 1: CV online (render từ snapshot — read-only)
  Tab 2: File đính kèm (nếu apply bằng upload)
```

---

## 4. Luồng nghiệp vụ (bắt buộc)

| # | Hành động | URL / method | Kết quả DB |
|---|-----------|--------------|------------|
| 1 | Tạo CV mới | `cv-builder.php` POST Lưu (chưa có id) | INSERT `cv_profiles` + children |
| 2 | Sửa cv1 | `cv-builder.php?id=1` POST | UPDATE profile id=1 + replace children id=1 |
| 3 | Tạo cv2 | Tạo mới (không sửa tên cv1 thành cv2 trên cùng form) | INSERT bản ghi mới |
| 4 | Đặt mặc định | `cv-manage.php` POST | `is_primary=0` all, `is_primary=1` cho 1 cv |
| 5 | Xóa CV | POST + SweetAlert confirm | DELETE cascade children (hoặc soft — **hard delete MVP**) |
| 6 | Apply chọn CV | `apply.php` | `applications.cv_profile_id` + `cv_snapshot_json` |
| 7 | Sửa CV sau apply | — | Employer vẫn thấy snapshot cũ |

**CSRF form_key (dự kiến):**

| form_key | Trang |
|----------|-------|
| `candidate_cv_save_form` | cv-builder |
| `candidate_cv_delete_form` | cv-manage |
| `candidate_cv_primary_form` | cv-manage |
| `apply_job_form` | giữ hiện tại |

---

## 5. Map field → database (MVP CV-A/B)

### `cv_profiles`

| Field UI | Cột | Bắt buộc MVP |
|----------|-----|-------------|
| Tên CV | `title` | ✅ |
| Họ tên | `full_name` | ✅ |
| Vị trí ứng tuyển | `target_position` | ✅ |
| Ngày sinh | `date_of_birth` | |
| Giới tính | `gender` | |
| SĐT | `phone` | ✅ |
| Email | `email` | ✅ |
| Website | `website` | |
| Địa chỉ | `address` | |
| Mục tiêu | `career_objective` | |
| Mặc định | `is_primary` | auto |
| Template | `template_key` | default `classic` (CV-D) |

### Bảng con (MVP)

| Section | Bảng | Field lặp |
|---------|------|-----------|
| Học vấn | `cv_educations` | start, end, school, major, description |
| Kinh nghiệm | `cv_experiences` | start, end, company, position, description |
| Kỹ năng | `cv_skills` | skill_name, description |

### Section phase CV-D (chưa form MVP)

`cv_activities`, `cv_certificates`, `cv_awards`, `cv_references`, `interests` trên profile.

---

## 6. Menu & điều hướng (thay đổi dự kiến)

| Vị trí | Thay đổi |
|--------|----------|
| Header (candidate) | Thêm link **Quản lý CV online** → `cv-manage.php` |
| Dropdown candidate | Mục **Quản lý CV** |
| `profile.php` | Khối 3–5 CV gần nhất + link "Xem tất cả" |
| `profile.php` | **Giữ** upload PDF (legacy) đến hết CV-E |

---

## 7. Quy tắc hiển thị / validation (UX copy)

| Rule | Thông báo / hành vi |
|------|---------------------|
| Tên CV trống | "Vui lòng đặt tên cho CV" |
| Email/SĐT sai format | Validator `cv_rules.php` |
| Xóa CV mặc định | Confirm; nếu còn CV khác → gợi ý chọn mặc định mới |
| Chưa có CV khi apply | Radio online disabled + link "Tạo CV online" |
| Employer xem | Chỉ read-only snapshot, không sửa |

---

## 8. ERD tóm tắt (cho CV-A)

```text
candidates 1 ──* cv_profiles 1 ──* cv_educations
                              ├──* cv_experiences
                              └──* cv_skills
                              (CV-D: + activities, certificates, awards, references)

applications ── optional cv_profile_id
              └── cv_snapshot_json (TEXT/JSON)
```

---

## 9. Phạm vi theo phase (nhắc lại)

| Phase | UI |
|-------|-----|
| CV-A | Không UI đẹp (service only) |
| CV-B | cv-manage + cv-builder (section lõi) |
| CV-C | cv-preview + apply dropdown + employer modal |
| CV-D | Đủ section TopCV + 2 template + avatar |

---

## 10. Checklist hoàn thành CV-0

- [x] Wireframe text 4 màn chính
- [x] Luồng đa CV (tạo / sửa / xóa / primary)
- [x] Map field → DB
- [x] CSRF keys dự kiến
- [x] User đọc và **`「xác nhận CV-0」`**

**Commit gợi ý (docs only):** `docs: phase CV-0 wireframe và IA CV structured`
