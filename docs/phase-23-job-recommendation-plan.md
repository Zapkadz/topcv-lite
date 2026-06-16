# Phase 23 — AI gợi ý việc làm cho ứng viên (Candidate-side)

> **Trạng thái:** ✅ implemented — chờ test manual / commit  
> **Prompt:** `docs/cursor-prompt-topcv-lite-job-recommendation-phase23.md`  
> **Phụ thuộc:** CV-A…CV-G merged · EMP-B API screening · `ai_screening_job_text.php` · `cv_snapshot_text.php`  
> **Python API:** `POST http://127.0.0.1:8000/recommend-jobs` (cùng uvicorn với `/screening`)

---

## 1. Mục tiêu nghiệp vụ

Ứng viên có CV online → bấm **「AI gợi ý việc làm phù hợp」** → hệ thống:

1. Lấy **plain text CV** sạch từ CV đang chọn (mặc định = CV primary)
2. Lấy **tất cả tin tuyển dụng đang mở** (approved, chưa hết hạn, chưa xóa mềm)
3. Gọi **`POST /recommend-jobs`**
4. Hiển thị **Top 10** kèm:
   - `fit_label`, `fit_score`
   - lý do phù hợp (`why_fit`, `fit_summary`)
   - skill-gap (`skill_gap_summary`, `skill_gaps`)
   - gợi ý cải thiện CV (`next_best_actions`, `cv_improvement_suggestions`)

**Không thay thế** employer-side `/screening`.  
**Không gọi Python CLI** cho feature này.

---

## 2. Phân tích codebase hiện có (đã inspect)

### 2.1 Có sẵn — tái sử dụng trực tiếp

| Thành phần | File | Vai trò Phase 23 |
|------------|------|------------------|
| Clean JD HTML → plain text | `includes/ai_screening_job_text.php` | `ai_screening_split_text_lines()`, `ai_screening_html_to_plain_text()` |
| Build job payload structured | `includes/ai_screening_payload.php` | `ai_screening_build_job_payload($job)` → `requirements`, `nice_to_have`, `responsibilities` |
| Build CV plain text | `includes/cv_snapshot_text.php` | `cv_snapshot_text_from_array()` — heading Skills/Experience/Education… |
| Load CV đầy đủ | `CvService::getFullForUser()` + `buildSnapshotJson()` | Snapshot structured giống lúc apply |
| Filter job active | `job_is_open_for_apply()` + query `jobs.php` | `approved` + `deadline >= CURDATE()` + `deleted_at IS NULL` |
| AI config / health / debug | `includes/ai_screening_config.php`, `ai_screening_api.php` | Pattern cURL, log, ghi JSON debug local |
| Candidate ID | `CvService::ensureCandidateId()` | Map `user_id` → `candidates.id` |

### 2.2 Chưa có — cần tạo mới

| Thành phần | Ghi chú |
|------------|---------|
| API client `/recommend-jobs` | Endpoint khác `/screening`, body khác |
| Payload builder candidate-side | `candidate` + `jobs[]` + `options` |
| Service orchestration | Gom CV + jobs + gọi API + parse response |
| UI candidate | Trang kết quả + modal chi tiết |
| CTA entry points | `cv-manage.php`, `profile.php` |
| Smoke test CLI | `docs/migrations/_test-job-recommendation.php` |

### 2.3 Rủi ro đã biết (từ prompt + kinh nghiệm EMP-B)

| Rủi ro | Cách tránh |
|--------|------------|
| Gửi HTML thô trong JD | Luôn qua `ai_screening_split_text_lines` / `ai_screening_build_job_payload` |
| `cv_text` quá ngắn/rỗng | Validate min length (~150–200 ký tự) trước khi gọi API |
| Map sai `candidate_id` | Lấy từ session `user_id` → `candidates.id`, không tin input client |
| UI đọc nhầm `recommendation` | Candidate UI dùng **`fit_label`**, không dùng `recommendation` (employer-side) |
| Response `job_id` không khớp DB | Join lại `jobs` + `companies` khi render link「Xem tin」 |
| API phase cũ | Health check `GET /health`, log phase/embedding khi debug |

---

## 3. Kiến trúc đề xuất

```text
[candidate/cv-manage.php | profile.php]
        │ CTA "AI gợi ý việc làm"
        ▼
[candidate/job-recommendations.php]
        │ POST (CSRF) chọn cv_profile_id (optional)
        ▼
[JobRecommendationService::runForCandidate()]
        ├─ CvService::getFullForUser → snapshot array
        ├─ cv_snapshot_text_from_array → candidate.cv_text
        ├─ build structured candidate fields (skills, experiences…)
        ├─ JobRepository::listOpenForRecommendation → jobs[]
        ├─ ai_recommendation_build_payload()
        └─ ai_recommendation_call_api() → top_jobs[]
        ▼
[Render UI] table + summary + modal detail
        └─ session cache kết quả lần chạy gần nhất (MVP)
```

---

## 4. Contract API — mapping cụ thể

### 4.1 Request `POST /recommend-jobs`

```json
{
  "candidate": {
    "candidate_id": 456,
    "candidate_name": "...",
    "email": "...",
    "phone": "...",
    "headline": "target_position",
    "summary": "career_objective",
    "skills": ["Java", "Spring Boot"],
    "work_experience": [{ "company_name": "...", "position": "...", "description": "..." }],
    "projects": [...],
    "education": [...],
    "certifications": [...],
    "cv_text": "plain text đầy đủ",
    "cv_file_path": ""
  },
  "jobs": [
    {
      "job_id": 10,
      "job_title": "...",
      "requirements": ["..."],
      "nice_to_have": ["..."],
      "responsibilities": ["..."]
    }
  ],
  "options": {
    "top_k": 10,
    "retrieval_top_n": 50
  }
}
```

**Nguồn dữ liệu PHP:**

| Field API | Nguồn DB / code |
|-----------|-----------------|
| `candidate_id` | `candidates.id` |
| `candidate_name` | `cv_profiles.full_name` (fallback `users.fullname`) |
| `email`, `phone` | `cv_profiles` (fallback `users`) |
| `headline` | `cv_profiles.target_position` |
| `summary` | `cv_profiles.career_objective` |
| `skills[]` | `cv_skills.skill_name` |
| `work_experience[]` | `cv_experiences` rows |
| `projects[]` | `cv_projects` (nếu có schema) |
| `education[]` | `cv_educations` |
| `certifications[]` | `cv_certificates` |
| `cv_text` | `cv_snapshot_text_from_array(packFullProfile)` |
| `jobs[]` | Mỗi row → `ai_screening_build_job_payload($job)` |

**`taxonomy_path`:** chỉ thêm nếu Python Phase 23 yêu cầu (giố EMP-B). Mặc định thử không gửi; nếu API lỗi thiếu taxonomy thì bổ sung `ai_taxonomy_effective_screening_path()`.

### 4.2 Response — field render UI

| UI section | Field API (ưu tiên) |
|------------|---------------------|
| Badge fit | `fit_label` |
| Điểm | `fit_score` |
| Tóm tắt card | `fit_summary` |
| Lý do phù hợp | `why_fit[]` |
| Count nhanh | `skill_gap_summary.*` |
| Chi tiết gap | `skill_gaps.missing_must_have`, `weak_evidence`, `optional_growth` |
| Gợi ý ngắn (list) | `next_best_actions[]` (2–4 dòng) |
| Gợi ý đầy đủ (modal) | `cv_improvement_suggestions[]` |
| Evidence | `matched_must_have_skills[]`, `review_card.evidence_highlights` |
| **Không dùng làm label chính** | `recommendation` (employer-side) |

### 4.3 Summary strip (sau khi có kết quả)

Tính từ `top_jobs[]`:

- `Top matches returned` = `count(top_jobs)`
- `Strong/Good Fit` = đếm `fit_label` ∈ {Strong Fit, Good Fit}
- `Missing must-have` = đếm job có `skill_gap_summary.missing_must_have_count > 0`
- `Optional growth only` = missing=0 && optional_growth>0

---

## 5. Query jobs active

```sql
SELECT j.*, c.name AS company_name, c.logo, l.name AS city
FROM jobs j
JOIN companies c ON j.company_id = c.id
JOIN locations l ON j.location_id = l.id
WHERE j.status = 'approved'
  AND j.deadline >= CURDATE()
  AND j.deleted_at IS NULL
ORDER BY j.created_at DESC
```

Đặt trong `JobRepository::listOpenForRecommendation(PDO $conn): array`.

**Giới hạn MVP:** gửi toàn bộ job active (prompt chấp nhận; AI có retrieval nội bộ `retrieval_top_n=50`).  
Nếu >200 jobs → log cảnh báo, vẫn gửi (hoặc cap 200 ở phase sau).

---

## 6. UX / trang web (đã review — chỉnh theo thực tế)

> **Kết luận review:** Hướng tổng thể **phù hợp** và làm được. Đã chỉnh plan để khớp pattern TOPCV Lite (employer `job_candidates.php`), tránh bảng quá rộng/mobile vỡ, và xử lý thời gian chờ API 1–3 phút.

### 6.1 Nguyên tắc UX (bám codebase hiện có)

| Nguyên tắc | Lý do |
|------------|-------|
| Mirror employer AI panel | NTD đã quen card + nút「Chạy AI」+ bảng kết quả — UV dùng cùng ngôn ngữ UI |
| Trang riêng, không inline profile | Kết quả nhiều (≤10 job + modal); API chậm — cần không gian + loading |
| POST → handler → redirect | Giống `employer/run_ai_screening.php`; tránh F5 submit lại |
| Bảng gọn desktop / card mobile | `cv-manage.php` dùng table — OK desktop; mobile cần stack card |
| Tiếng Việt end-user | Không hiện label debug kiểu "Top matches returned" |
| Không hiện trạng thái API kỹ thuật | User chỉ thấy lỗi thân thiện khi chạy fail |

### 6.2 Entry points (CTA) — đã tinh gọn

| Vị trí | Ưu tiên | Hành vi |
|--------|---------|---------|
| `candidate/cv-manage.php` | **Chính** | Banner card phía trên bảng CV (icon robot + mô tả 1 dòng + nút xanh) |
| `candidate/profile.php` | Phụ | Nút outline trong khối CV online **chỉ khi đã có primary CV** |
| Dropdown user (header) | Phụ | Thêm mục「AI gợi ý việc làm」— **không** thêm vào navbar chính (tránh lộn menu) |

**Không** đặt CTA trên `jobs.php` / trang chủ ở MVP — tránh gọi AI ngoài ngữ cảnh CV.

### 6.3 Luồng trang (2 trạng thái rõ ràng)

```text
GET  job-recommendations.php
  → Trạng thái A: Thiết lập (chọn CV, xem số tin, bấm chạy)
  → (nếu session có kết quả cũ) Trạng thái B: Hiển thị kết quả lần trước

POST run-job-recommendation.php (CSRF)
  → Gọi AI (blocking, timeout 180s)
  → Lưu response vào $_SESSION['job_recommendation_last']
  → Redirect job-recommendations.php?ran=1

GET job-recommendations.php?ran=1
  → Trạng thái B: Summary + danh sách + modal
```

**Loading (bắt buộc):** Form submit bật overlay + disable nút:
>「Đang phân tích CV với {N} tin tuyển dụng… Có thể mất 1–3 phút, vui lòng không đóng trang.」

### 6.4 Trang chính: `candidate/job-recommendations.php`

#### A. Header + breadcrumb

```
Trang chủ / Quản lý CV / AI gợi ý việc làm
```

- Tiêu đề: **AI gợi ý việc làm phù hợp**
- Mô tả: *Phân tích CV hiện tại và gợi ý top công việc phù hợp cùng điểm cần cải thiện.*
- Link「Quay lại Quản lý CV」

#### B. Panel thiết lập (card — giống employer AI panel)

| Thành phần | Chi tiết |
|------------|----------|
| Chọn CV | Dropdown (default = primary); hiện % hoàn thiện + link「Xem trước CV」 |
| Số tin sẽ xét | Badge động: `{N} tin đang tuyển` (đếm từ DB, không gọi AI) |
| Lần chạy gần nhất | `{dd/mm/YYYY HH:MM}` nếu session có cache |
| Nút chính | **Chạy AI gợi ý** (disabled nếu chưa đủ điều kiện) |
| Hint panel | Giống employer `$aiPanelHint`: chưa CV / CV ngắn / không có tin / API chưa sẵn sàng |

#### C. Summary strip (sau khi có kết quả) — tiếng Việt

| Chỉ số | Cách tính |
|--------|-----------|
| Top gợi ý | `count(top_jobs)` |
| Phù hợp cao | `fit_label` ∈ {Strong Fit, Good Fit} |
| Còn thiếu kỹ năng bắt buộc | `missing_must_have_count > 0` |
| Chỉ cần phát triển thêm | missing = 0 và `optional_growth_count > 0` |

Hiển thị 4 ô nhỏ trong 1 row (Bootstrap col), không dùng thuật ngữ English.

#### D. Danh sách kết quả — **gọn, không 9 cột**

**Desktop — bảng 5 cột:**

| # | Việc làm | Độ phù hợp | Thiếu hụt | Thao tác |
|---|----------|-------------|-----------|----------|
| 1 | Title + company + city | Badge `fit_label` + điểm | 1 dòng tóm tắt gap | Chi tiết · Xem tin |

- **Thiếu hụt** (1 dòng): vd. `Thiếu 2 bắt buộc · 1 bằng chứng yếu` — lấy từ `skill_gap_summary`
- **next_best_actions**: không hiện trên bảng — chỉ trong modal (tránh clutter)

**Mobile — stacked card** mỗi job (cùng nội dung, nút full-width).

#### E. Modal chi tiết — accordion 4 mục

1. **Vì sao phù hợp** — `why_fit[]`, `fit_summary`
2. **Điểm còn thiếu / yếu** — `skill_gaps.*`, `skill_gap_summary`
3. **Cách cải thiện CV** — `next_best_actions[]` (2–4 dòng) + `cv_improvement_suggestions[]` (đầy đủ)
4. **Bằng chứng kỹ năng** — `matched_must_have_skills[]`, `review_card.evidence_highlights` (tối đa 5–7 dòng)

Pattern: giống modal review card employer (`openAiReviewModal`), không dump JSON.

#### F. Badge màu `fit_label`

| Label | Màu Bootstrap |
|-------|----------------|
| Strong Fit | `success` |
| Good Fit | `primary` |
| Potential Fit | `info` |
| Stretch | `warning` |
| Low Fit | `secondary` |

#### G. Actions trên mỗi job — đã chỉnh logic

| Nút | Hành vi |
|-----|---------|
| **Chi tiết AI** | Mở modal accordion |
| **Xem tin** | `job-detail.php?id={job_id}` |
| ~~Ứng tuyển riêng~~ | **Bỏ** — trùng「Xem tin」; apply nằm trên job detail |
| Badge **Đã ứng tuyển** | Nếu `applications` đã có `(job_id, candidate_id)` — disable nhấn mạnh đã nộp |

#### H. Disclaimer (alert info, luôn hiện khi có kết quả)

> Kết quả xếp hạng dựa trên mức độ phù hợp giữa CV và mô tả công việc. Chưa tính ưu tiên cá nhân như mức lương, địa điểm, hình thức làm việc.

### 6.5 Empty / error states

| Tình huống | UI |
|------------|-----|
| Chưa có CV | Empty card + nút「Tạo CV mới」/「Import PDF」 |
| CV text quá ngắn | Hint vàng + link「Sửa CV」kèm checklist (Kỹ năng, Kinh nghiệm, Mục tiêu) |
| Không có JD active | Hint「Chưa có tin tuyển dụng đang mở」 |
| API down | SweetAlert error sau redirect (giống employer) |
| `top_jobs` rỗng | Alert「Chưa tìm thấy công việc phù hợp đủ điều kiện」+ gợi ý bổ sung CV |
| Đang chạy | Overlay loading (không để user bấm lại) |

### 6.6 Những gì **không** làm (tránh over-UX)

- Không landing page / hero marketing
- Không card trong card
- Không hiện `recommendation` (employer field) làm label chính
- Không auto chạy AI khi mở trang
- Không pagination kết quả (top_k ≤ 10, hiện hết một trang)

---

## 7. File sẽ tạo / sửa

### 7.1 Tạo mới

| File | Vai trò |
|------|---------|
| `includes/ai_job_recommendation_config.php` | URL `/recommend-jobs`, options `top_k`, `retrieval_top_n` (hoặc mở rộng `ai_screening_config.php`) |
| `includes/ai_job_recommendation_payload.php` | `ai_recommendation_build_candidate_payload()`, `ai_recommendation_build_jobs_payload()`, `ai_recommendation_build_request()` |
| `includes/ai_job_recommendation_api.php` | `ai_recommendation_call_api()`, log metadata, debug file write |
| `includes/services/JobRecommendationService.php` | Orchestration + validation + session cache |
| `candidate/job-recommendations.php` | UI chính (setup + results) |
| `candidate/run-job-recommendation.php` | POST handler (giống `employer/run_ai_screening.php`) |
| `includes/partials/job_recommendation_results.php` | Table + summary (tách cho gọn) |
| `includes/partials/job_recommendation_detail_modal.php` | Modal JS + template |
| `assets/css/job-recommendations.css` | Style dense list (nếu cần, tối thiểu) |
| `docs/migrations/_test-job-recommendation.php` | CLI smoke: build payload + gọi API + in top 3 |

### 7.2 Sửa

| File | Thay đổi |
|------|----------|
| `includes/repositories/JobRepository.php` | `listOpenForRecommendation()` |
| `config/ai_screening.example.php` | Thêm `recommend_jobs_api_url` |
| `includes/ai_screening_config.php` | Default + merge local config |
| `candidate/cv-manage.php` | CTA link |
| `candidate/profile.php` | CTA link |
| `includes/header.php` | Dropdown UV — mục「AI gợi ý việc làm」(không navbar chính) |

### 7.3 Không làm (MVP)

- Bảng DB `ai_job_recommendation_runs` (defer — dùng session)
- Preference-aware ranking (lương, địa điểm)
- Rate limit / quota UV
- Hiển thị trên `index.php` / `jobs.php`
- Gọi CLI Python

---

## 8. Config

Mở rộng `config/ai_screening.local.php`:

```php
'recommend_jobs_api_url' => 'http://127.0.0.1:8000/recommend-jobs',
'recommend_top_k' => 10,
'recommend_retrieval_top_n' => 50,
'recommend_min_cv_text_length' => 150,
// debug_api_payload => true  (dùng chung folder api-debug)
```

Debug file naming:
- `{timestamp}-candidate-{id}-recommend-request.json`
- `{timestamp}-candidate-{id}-recommend-response.json`

Log tối thiểu (`storage/logs/ai_screening.log` hoặc log riêng):
- `candidate_id`, `cv_id`, `jobs_count`, `top_k`, HTTP status, `top_jobs` count
- top 3: `job_id`, `fit_score`, `fit_label`

---

## 9. Validation trước khi gọi API

| Check | Rule |
|-------|------|
| Role | Chỉ `candidate` đăng nhập |
| CV ownership | `cv_profile_id` thuộc `user_id` session |
| CV text | `strlen(trim(cv_text)) >= 150` (configurable) |
| Jobs | `count(jobs) >= 1` |
| API | `ai_screening_check_api_health()` OK |
| CSRF | POST form |

---

## 10. Tiến độ implement (sau khi user xác nhận)

| Step | Nội dung | Ước lượng |
|------|----------|-----------|
| **R1** | Config + `JobRepository::listOpenForRecommendation` + payload builders | 1 session |
| **R2** | API client + `JobRecommendationService` + logging/debug | 1 session |
| **R3** | `job-recommendations.php` UI (header, form, states) | 1 session |
| **R4** | Results table + summary strip + detail modal | 1 session |
| **R5** | Wire CTA (`cv-manage`, `profile`, header) + smoke test | 0.5 session |

---

## 11. Test checklist

### 11.1 Chuẩn bị

```powershell
cd C:\SEMANTIC_SKILLS_RESUME
.\.venv\Scripts\Activate.ps1
$env:SEMANTIC_EMBEDDING_ENABLED='1'
$env:SEMANTIC_EMBEDDING_MODEL='BAAI/bge-m3'
uvicorn api:app --host 127.0.0.1 --port 8000
```

```powershell
curl http://127.0.0.1:8000/health
php docs/migrations/_test-job-recommendation.php
```

### 11.2 Manual web

- [ ] UV chưa có CV → empty state + link tạo CV
- [ ] UV có CV đủ data → chạy AI → Top ≤10 jobs
- [ ] Mỗi job hiện `fit_label`, `fit_score`, gap counts
- [ ] Modal chi tiết: why_fit, skill_gaps, next_best_actions
- [ ]「Xem tin」mở đúng `job-detail.php?id=`
- [ ] Payload debug không chứa HTML (`<p>`, `<li>`)
- [ ] API tắt → message thân thiện
- [ ] Disclaimer Phase 23 hiển thị
- [ ] Không hiển thị `recommendation` làm label chính

### 11.3 Regression

- [ ] Employer `/screening` vẫn hoạt động (không đụng payload screening)
- [ ] Apply job flow không đổi

---

## 12. Acceptance criteria (từ prompt)

1. Candidate bấm được nút chạy AI gợi ý  
2. PHP build `candidate.cv_text` plain text sạch  
3. PHP lấy jobs active và build `jobs[]`  
4. POST thành công tới `/recommend-jobs`  
5. Hiển thị Top 10 + fit/gap/actions  
6. Modal detail đầy đủ 4 nhóm thông tin  
7. Error states thân thiện  
8. Không gửi HTML thô  

---

## 13. Git (khi pass)

Nhánh đề xuất: `feature/phase-23-job-recommendation`  
Commit gợi ý: `feat(candidate): AI job recommendations via /recommend-jobs API (Phase 23)`

---

## 14. Câu hỏi đã tự chốt (không cần hỏi user trước code)

| Câu hỏi | Quyết định |
|---------|------------|
| Lưu DB lịch sử? | **Không** — session cache MVP |
| Trang kết quả riêng hay inline? | **Trang riêng** `job-recommendations.php` |
| CV nào dùng mặc định? | **Primary CV**, cho đổi dropdown |
| `nice_to_have` lấy từ đâu? | **`benefits`** (giống EMP-B screening) |
| Config chung hay riêng? | **Mở rộng** `ai_screening_config` (cùng health URL) |
