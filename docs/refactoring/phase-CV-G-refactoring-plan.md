# Phase CV-G — Refactoring Plan

> **Trạng thái:** ✅ **Đã refactor** — chờ test lại / commit  
> **Cập nhật:** 2026-06-09

---

## Đã thực hiện (2026-06-09)

- [x] **R1** — Xóa SVG + `cv_template_thumbnail_url()` + field `thumbnail`
- [x] **R2** — `cv_template_picker_url()` / `cv_template_page_url()` trong `cv_rules.php`; wire 4 entry points + import redirect
- [x] **R3** — Partial `includes/partials/cv_template_card.php`
- [x] **R4** — CSS cleanup + section comments
- [x] **R5** — `cv_builder_resolve_initial_template_key()` trong `cv_rules.php`

**Chưa làm:** commit (chờ user)

---

## Nguyên tắc

- **Không đổi behavior** đã test pass
- **Giữ API/URL** hiện tại (`cv-templates.php`, `?template=`, `?from_import=1`)
- Chỉ dọn code, giảm trùng lặp, chuẩn hóa naming

---

## Phạm vi đã ship (CV-G)

| Thành phần | File |
|------------|------|
| Gallery chọn mẫu | `candidate/cv-templates.php` |
| Catalog + preview mẫu | `includes/cv_template_catalog.php` |
| CSS gallery | `assets/css/cv-template-picker.css` |
| Wire entry points | `cv-manage.php`, `profile.php`, `cv-import.php` |
| Builder `?template=` | `candidate/cv-builder.php` |
| Import redirect | `includes/cv_import_vip.php` |

**Không dùng nữa (dead code):** `assets/images/cv-templates/*.svg`, `cv_template_thumbnail_url()` — preview chuyển sang HTML thu nhỏ.

---

## R1 — Dọn dead code thumbnail SVG (ưu tiên cao, rủi ro thấp)

| Việc | Chi tiết |
|------|----------|
| Xóa | `assets/images/cv-templates/classic.svg`, `modern.svg` |
| Xóa | `cv_template_thumbnail_url()` nếu không còn caller |
| Xóa field | `thumbnail` trong `cv_template_catalog()` (hoặc giữ comment TODO nếu sau dùng PNG) |
| Cập nhật | `docs/phase-cv-g-plan.md` §6 — ghi preview = HTML scaled, không còn asset SVG |

**Behavior:** Không đổi UI.

---

## R2 — Gom link «Tạo CV mới» (ưu tiên cao, rủi ro thấp)

Hiện 4 file hardcode `href="cv-templates.php"`:

- `candidate/cv-manage.php` (2 chỗ)
- `candidate/profile.php` (2 chỗ)
- `candidate/cv-import.php` (1 chỗ)

**Đề xuất:** thêm helper trong `cv_template_catalog.php`:

```php
function cv_template_picker_url(array $query = []): string
```

Dùng cho mọi nút «Tạo CV mới» / «Tạo CV thủ công». Import redirect trong `cv_import_vip.php` gọi helper với `['from_import' => '1']`.

**Behavior:** URL giữ nguyên.

---

## R3 — Tách partial card template (ưu tiên trung bình)

`cv-templates.php` ~95 dòng — chấp nhận được, nhưng vòng lặp card có thể tách:

| Tạo | `includes/partials/cv_template_card.php` |
|-----|------------------------------------------|
| Input | `$tpl`, `$builderQuery` |
| Output | HTML 1 card gallery |

Giữ logic auth/schema trong `cv-templates.php`.

**Behavior:** Không đổi HTML output (chỉ structure PHP).

---

## R4 — Gom CSS preview compact (ưu tiên trung bình)

`cv-template-picker.css` có nhiều rule lặp `.cv-template-thumb-inner .small, li, p`.

**Đề xuất:**

- Nhóm selector chung cho typography compact
- Comment block: `/* Classic header */`, `/* Modern sidebar */`
- Không merge vào `cv-preview.css` (tránh ảnh hưởng trang preview full)

**Behavior:** Visual giữ nguyên (verify 2 card sau refactor).

---

## R5 — Builder template query (ưu tiên thấp)

`cv-builder.php` có logic `$requestedTemplate` rải 2 nhánh (`from_import`, tạo mới).

**Đề xuất:** helper nhỏ trong `cv_rules.php` hoặc catalog:

```php
function cv_builder_resolve_initial_template_key(
    bool $isEdit,
    ?string $getTemplate,
    ?string $profileTemplate
): string
```

Gọi 1 lần sau load profile/draft.

**Behavior:** Fallback `classic` + không override khi `?id=` — giữ nguyên.

---

## R6 — Catalog i18n / naming (ưu tiên thấp, có thể hoãn)

Label «Classic» / «Modern» tiếng Anh; tags tiếng Việt. Có thể thống nhất sau (VD: «Cổ điển» / «Hiện đại») — **không làm trong refactor CV-G** trừ khi user yêu cầu.

---

## Không làm trong refactor CV-G

| Việc | Lý do |
|------|--------|
| Đổi `cv-manage.php` → `my-cvs.php` | Phase riêng |
| Thêm mẫu thứ 3 | Ngoài scope |
| Iframe / PNG export preview | MVP đã chốt HTML |
| DB migration | Không cần |

---

## Checklist verify sau refactor (nếu thực hiện)

- [ ] `cv-manage.php` → Tạo CV mới → `cv-templates.php`
- [ ] Preview card fill khung, tags đúng (không ATS/Online)
- [ ] Classic / Modern → builder dropdown đúng → lưu → preview
- [ ] Import PDF → `cv-templates.php?from_import=1` → builder có draft
- [ ] `cv-builder.php` direct / invalid template → classic
- [ ] Sửa CV `?id=` → template DB không đổi

---

## Thứ tự đề xuất

1. **R1** — dead code SVG  
2. **R2** — helper URL picker  
3. **R4** — CSS cleanup  
4. **R3** — partial card (tuỳ chọn)  
5. **R5** — builder helper (tuỳ chọn)

Ước lượng: **R1+R2** ~15 phút · full R1–R5 ~45 phút.

---

## Git (sau refactor + user đồng ý commit)

- Nhánh: `feature/cv-g-template-picker`
- Commit gợi ý: `feat(cv): template picker with live preview before builder (CV-G)`
