# Setup — Import CV từ PDF (Phase CV-E)

Hướng dẫn cài đặt local (XAMPP) cho tính năng **Tạo CV từ PDF**.

---

## 1. Yêu cầu PHP

| Thành phần | Ghi chú |
|------------|---------|
| PHP **8.0+** | Khuyến nghị 8.1+ |
| **curl** | Gọi API Groq / OpenRouter / Gemini |
| **fileinfo** | Validate MIME upload PDF |
| **mbstring** | Xử lý text tiếng Việt |
| **openssl** | HTTPS tới API |

Kiểm tra nhanh:

```powershell
php -m | findstr /i "curl fileinfo mbstring openssl"
```

---

## 2. Composer + pdfparser

Tại thư mục gốc project:

```powershell
cd C:\xampp\htdocs\topcv_lite
php composer.phar install --prefer-source
```

Nếu thiếu extension `zip`, dùng `--prefer-source` như trên.

Thư mục `vendor/` đã gitignore — mỗi máy clone cần chạy lại `composer install`.

---

## 3. Cấu hình AI (bắt buộc cho parse tốt)

```powershell
copy config\ai.example.php config\ai.local.php
```

Sửa `config/ai.local.php`:

- **Groq (khuyến nghị):** `provider` = `groq`, key `gsk_...` từ [console.groq.com](https://console.groq.com/keys)
- **OpenRouter / Gemini:** xem comment trong `ai.example.php`

**Không commit** `config/ai.local.php` (đã gitignore).

Nếu chưa có key, hệ thống vẫn chạy **fallback regex** — ít field hơn.

---

## 4. Thư mục upload

Đảm bảo ghi được:

```
uploads/cv/import/
```

File PDF import lưu dạng: `{userId}_{YmdHis}_{random}.pdf`

---

## 5. XAMPP — tránh timeout khi import

POST import có thể mất **10–30 giây** (extract + AI).

Trong `php.ini` (XAMPP → Config → PHP):

```ini
max_execution_time = 120
```

Khởi động lại Apache sau khi sửa.

---

## 6. Rate limit

Mỗi candidate: **5 lần import / giờ** (session `cv_import_hits`).

Lần thứ 6 trong cùng giờ → thông báo *"Thử lại sau 1 giờ"*.

---

## 7. Script kiểm tra (dev)

```powershell
# Trích text PDF
php docs\migrations\_test-pdf-extract.php "uploads\cv\file.pdf"

# Pipeline đầy đủ (extract + AI + normalize)
php docs\migrations\_test-cv-parse-pipeline.php "uploads\cv\file.pdf"

# Clean text (không gọi AI)
php docs\migrations\_test-text-clean.php "docs\migrations\cv-text-noisy-sample.txt"
```

Script trong `docs/migrations/_test-*.php` chỉ dùng dev — không chạy trên production công khai.

---

## 8. Luồng người dùng

1. **Quản lý CV** → **Tạo CV từ PDF**
2. Upload PDF text-based (max 5MB)
3. Review trên **cv-builder** → **Lưu CV**
4. File gốc lưu `cv_profiles.attachment_path` — icon 📎 trên danh sách CV

---

## 9. Giới hạn đã biết

| Tình huống | Hành vi |
|------------|---------|
| PDF scan ảnh (không copy chữ) | Lỗi hoặc text quá ngắn |
| PDF thiết kế Canva | Text nhiễu — cần kiểm tra form trước khi lưu |
| API lỗi / hết quota | Fallback regex + cảnh báo trên builder |

Nâng cấp OCR / Vision → phase **CV-F** (defer).
