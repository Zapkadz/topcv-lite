# Phase CV-G — Chọn mẫu CV trước khi vào builder

> **Trạng thái:** ✅ **Test pass** — chờ refactor / commit  
> **Cập nhật:** 2026-06-09

---

## Quyết định đã chốt

| # | Quyết định | Ghi chú triển khai |
|---|------------|-------------------|
| **1** | **Giữ URL hub `cv-manage.php`** | Không đổi sang `my-cvs.php` trong phase này. Tránh ảnh hưởng link, breadcrumb, redirect. Đổi tên URL (nếu cần) → phase refactor riêng. |
| **2** | **Trang chọn mẫu bắt buộc có thumbnail/card preview** | Ảnh preview **tĩnh** + tên + mô tả ngắn + tag + nút «Dùng mẫu này». **Không** iframe preview ở MVP. |
| **3** | **Mọi luồng tạo CV mới đi qua `cv-templates.php`** | Sau chọn mẫu → `cv-builder.php?template=<code>`. Builder vẫn **fallback `classic`** khi truy cập trực tiếp hoặc template không hợp lệ. |

---

## 1. Phạm vi task

**Nhỏ–vừa → 1 phase (CV-G).**

| Trong scope | Ngoài scope |
|-------------|-------------|
| Trang chọn mẫu + thumbnail tĩnh | Thêm mẫu thứ 3+ |
| Đổi link «Tạo CV mới» / «Tạo CV thủ công» → templates | Đổi tên `cv-manage.php` → `my-cvs.php` |
| Builder nhận `?template=` | Iframe preview trên trang mẫu |
| Fallback `classic` (direct URL / invalid) | Rewrite builder/preview |
| Giữ dropdown mẫu trong builder | DB migration |
| Asset ảnh preview tĩnh (classic, modern) | |

---

## 2. Mục tiêu

- Ứng viên **nhìn thumbnail** và **chọn mẫu CV** (Classic / Modern) **trước** khi nhập thông tin.
- **Mọi** điểm vào «tạo CV mới» đều qua `cv-templates.php` — không mở thẳng builder.
- Giữ logic tạo/lưu CV hiện tại; **không phá CV đã tạo**.
- Hub quản lý CV vẫn là **`candidate/cv-manage.php`**.

---

## 3. Flow

### Hiện tại

```text
cv-manage.php / profile.php / cv-import.php
  → [Tạo CV mới / Tạo CV thủ công] → cv-builder.php
  → template_key mặc định classic
  → chọn mẫu bằng dropdown trong form
```

### Mới

```text
Mọi luồng tạo CV mới:
  cv-manage.php | profile.php | cv-import.php (Tạo CV thủ công)
    → cv-templates.php
    → chọn mẫu (card + thumbnail tĩnh)
    → cv-builder.php?template=classic|modern
    → nhập thông tin → lưu (template_key vào DB)

Ngoại lệ (không đổi hành vi sửa):
  cv-builder.php?id={cv_id}     — sửa CV đã có; template lấy từ DB

Fallback builder (vẫn cho phép, không redirect):
  cv-builder.php                — không có ?template= → classic
  cv-builder.php?template=xyz   — invalid → classic (cv_normalize_template_key)
```

**Query param:** `template=` → `cv_normalize_template_key()` → `template_key` nội bộ.

**Import PDF:** Luồng upload/parse PDF giữ logic hiện tại; khi cần **tạo CV mới** (kể cả «Tạo CV thủ công» từ trang import), entry point đi qua `cv-templates.php` trước. Builder có thể nhận thêm param nghiệp vụ cũ (VD `from_import=1`) **sau** khi đã chọn mẫu — không bỏ qua bước chọn mẫu.

---

## 4. UI trang `cv-templates.php`

Mỗi mẫu (Classic, Modern) hiển thị **card** gồm:

| Thành phần | Bắt buộc |
|------------|----------|
| **Thumbnail tĩnh** | Có — ảnh preview (PNG/WebP), không iframe |
| **Tên mẫu** | Có — VD: «Classic», «Modern» |
| **Mô tả ngắn** | Có — 1–2 câu |
| **Tag** | Có — VD: «Truyền thống», «Hiện đại» (hoặc tương đương) |
| **Nút «Dùng mẫu này»** | Có — link `cv-builder.php?template={key}` |

**Không làm ở MVP:** iframe render preview thật, zoom live, so sánh side-by-side.

**Asset dự kiến:** `assets/images/cv-templates/classic.{png|webp}`, `modern.{png|webp}` (hoặc path thống nhất trong `cv_template_catalog.php`).

---

## 5. File cần đọc (đã khảo sát)

| File | Vai trò |
|------|---------|
| `candidate/cv-manage.php` | Hub, nút Tạo CV mới |
| `candidate/cv-builder.php` | Form tạo/sửa, dropdown mẫu ~688 |
| `candidate/profile.php` | Link Tạo CV mới |
| `candidate/cv-import.php` | «Tạo CV thủ công», luồng import |
| `includes/cv_rules.php` | `cv_allowed_template_keys()`, `cv_normalize_template_key()` |
| `includes/cv_preview_render.php` | Render classic/modern (tham khảo style, không iframe) |

---

## 6. File dự kiến sửa / tạo

### Tạo mới

| File | Mục đích |
|------|----------|
| `candidate/cv-templates.php` | Gallery 2 card + thumbnail + nút «Dùng mẫu này» |
| `includes/cv_template_catalog.php` | Metadata: key, label, mô tả, tag, path thumbnail |
| `assets/images/cv-templates/classic.*` | Thumbnail tĩnh Classic |
| `assets/images/cv-templates/modern.*` | Thumbnail tĩnh Modern |

### Sửa

| File | Thay đổi |
|------|----------|
| `candidate/cv-manage.php` | Link Tạo CV mới → `cv-templates.php` |
| `candidate/profile.php` | Link tạo CV → `cv-templates.php` |
| `candidate/cv-import.php` | «Tạo CV thủ công» → `cv-templates.php` |
| `candidate/cv-builder.php` | GET `?template=` khi tạo mới; fallback classic; không override template khi `?id=` |

---

## 7. Database

**Không thay đổi.**

- Cột `cv_profiles.template_key` đã có (`classic` | `modern`).
- Chỉ set sớm hơn qua query → form → POST như hiện tại.

---

## 8. Rủi ro

| Rủi ro | Mức | Giảm |
|--------|-----|------|
| CV cũ đổi template | Thấp | Chỉ áp `?template` khi tạo mới (không `?id=`) |
| Apply/snapshot | Rất thấp | Không đụng ApplicationService |
| Sót link cũ → thẳng builder | Trung bình | Grep toàn repo + fallback classic |
| Import PDF thiếu bước chọn mẫu | Trung bình | Wire «Tạo CV thủ công» + kiểm tra redirect sau parse |
| Template invalid / direct URL | Thấp | `cv_normalize_template_key()` → classic |
| Thumbnail thiếu / broken | Thấp | Catalog central + alt text trên `<img>` |

**Xung đột tổng thể: thấp.**

---

## 9. Thiết kế triển khai (sau khi duyệt plan)

| Khối | Nội dung |
|------|----------|
| **G1** | `cv_template_catalog.php` + asset thumbnail tĩnh + `cv-templates.php` (card UI) |
| **G2** | Wire links: `cv-manage.php`, `profile.php`, `cv-import.php` → templates |
| **G3** | Builder: `$_GET['template']` nhánh tạo mới; fallback classic |
| **G4** | Giữ dropdown builder; có thể hint «đã chọn mẫu từ bước trước, vẫn đổi được» |

---

## 10. Checklist test thủ công

- [ ] `cv-manage.php` → Tạo CV mới → `cv-templates.php` (không thẳng builder)
- [ ] Trang templates: mỗi mẫu có thumbnail, tên, mô tả, tag, nút «Dùng mẫu này»
- [ ] Thumbnail load đúng (không broken image); **không** có iframe preview
- [ ] Chọn Classic → `cv-builder.php?template=classic` → dropdown = Classic → lưu → preview classic
- [ ] Chọn Modern → builder dropdown = Modern → lưu → preview modern
- [ ] `?template=invalid` → fallback classic
- [ ] `cv-builder.php` truy cập trực tiếp (không query) → classic
- [ ] Sửa CV (`?id=`) → template DB không đổi vì URL
- [ ] `profile.php` → Tạo CV mới → qua templates
- [ ] `cv-import.php` → «Tạo CV thủ công» → qua templates → builder có `?template=`
- [ ] Import PDF: sau khi cần tạo CV mới, vẫn có bước chọn mẫu (không skip templates)
- [ ] Breadcrumb / link quay lại hub vẫn trỏ `cv-manage.php`
- [ ] Apply + snapshot smoke test với CV mới tạo

---

## 11. Git (dự kiến, sau pass)

- Nhánh: `feature/cv-g-template-picker`
- Commit gợi ý: `feat(cv): template picker with static thumbnails before builder (CV-G)`

---

## 12. Đã pass

- User: **`「CV-G pass」`** — 2026-06-09
- Refactor plan: `docs/refactoring/phase-CV-G-refactoring-plan.md`
- **Chưa làm:** refactor (tuỳ chọn) · commit
