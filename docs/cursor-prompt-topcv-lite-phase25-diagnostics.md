# Cursor Prompt - Integrate TOPCV Lite Web with Phase 25 Diagnostics

Copy nguyen prompt duoi day vao Cursor trong project PHP `C:\xampp\htdocs\topcv_lite`.

```text
Ban dang code trong project PHP TOPCV Lite tai:

C:\xampp\htdocs\topcv_lite

Ben ngoai project PHP da co AI Python project tai:

C:\SEMANTIC_SKILLS_RESUME

AI Python da duoc nang cap len:

Phase 25 - Web Payload Quality Hardening and Runtime Diagnostics

Task nay CHI sua web PHP de tich hop dung response moi cua AI.
Khong sua code Python AI trong task nay.

==================================================
1. Boi canh quan trong
==================================================

AI Python hien tai co 2 endpoint:

1. POST /screening
   - employer-side
   - 1 JD -> rank nhieu CV

2. POST /recommend-jobs
   - candidate-side
   - 1 CV -> Top matching jobs

Task nay can cap nhat web cho CA HAI endpoint,
vi Phase 25 bo sung `trace_id` va `diagnostics` cho ca employer-side va candidate-side.

API local:

- Health: http://127.0.0.1:8000/health
- Screening: http://127.0.0.1:8000/screening
- Recommendation: http://127.0.0.1:8000/recommend-jobs

==================================================
2. Dieu gi da thay doi o Phase 25
==================================================

Phase 24 da them:

- `excluded_jobs`
- `job_quality_stats`
- `warnings`
- `job_quality`

Phase 25 them tiep:

- top-level `trace_id`
- top-level `diagnostics`
- payload diagnostics cho CV/JD
- runtime diagnostics de phan biet:
  - loi payload web
  - payload qua ngan / placeholder
  - van de parsing
  - van de matching that

Muc tieu:

Khong chi hien ket qua AI,
ma con phai giup web/dev/admin biet:

- AI da nhan du lieu gi
- du lieu co yeu khong
- request nay co canh bao nao
- trace_id nao de doi chieu log

==================================================
3. Nhung loi cu phai tranh lap lai
==================================================

Da tung gap cac loi sau trong web:

1. Gui JD dang HTML tho tu DB:
   - `<p>`, `<strong>`, `&nbsp;`, bullet HTML, editor content

2. Gui `cv_text` qua ngan hoac mat section

3. Candidate-side dung nham field employer-side:
   - lay `recommendation`
   - thay vi `fit_label`

4. Tron `excluded_jobs` vao list goi y chinh

5. Session cu / response cu khien UI hien sai contract phase moi

Task nay phai sua cho dung:

- van clean plain text truoc khi gui AI
- candidate-side van dung `fit_label`
- khong dua `excluded_jobs` vao danh sach fit jobs
- log `trace_id`
- neu dang cache session, phai bump session schema version

==================================================
4. Muc tieu task web nay
==================================================

Hay inspect codebase va cap nhat web de:

1. goi dung API Phase 25
2. parse them `trace_id`
3. parse them `diagnostics`
4. luu / log `trace_id` cho moi lan goi AI
5. hien warning than thien neu payload CV/JD yeu
6. co khu debug/admin de xem diagnostics
7. van giu backward-compatible UI chinh

Can uu tien:

- employer-side screening page / modal
- candidate-side recommendation page
- service layer parse API response
- logging/debug layer

==================================================
5. Quy tac health-check truoc khi goi API
==================================================

Neu web da co health check truoc khi goi AI,
hay cap nhat de xac nhan:

`phase == "Phase 25 - Web Payload Quality Hardening and Runtime Diagnostics"`

Neu phase khac, log warning local.

Khong block cứng UI chi vi phase khac,
nhung phai log ro de dev debug.

==================================================
6. Request body van gui nhu cu, nhung phai sach
==================================================

### 6.1 Employer-side `/screening`

Web van gui payload kieu:

{
  "job": {
    "job_id": 10,
    "job_title": "...",
    "requirements": [...],
    "nice_to_have": [...],
    "responsibilities": [...],
    "description": "plain text",
    "raw_text": "plain text neu co"
  },
  "candidates": [
    {
      "application_id": 123,
      "candidate_id": 456,
      "candidate_name": "...",
      "cv_text": "plain text CV sach",
      "headline": "...",
      "summary": "...",
      "skills": [...],
      "work_experience": [...],
      "projects": [...],
      "education": [...],
      "certifications": [...]
    }
  ]
}

### 6.2 Candidate-side `/recommend-jobs`

Web van gui:

{
  "candidate": {
    "candidate_id": 456,
    "candidate_name": "...",
    "cv_text": "plain text CV sach",
    "headline": "...",
    "summary": "...",
    "skills": [...],
    "work_experience": [...],
    "projects": [...],
    "education": [...],
    "certifications": [...]
  },
  "jobs": [...],
  "options": {
    "top_k": 10,
    "retrieval_top_n": 50
  }
}

### 6.3 Bat buoc clean plain text truoc khi gui

Phai dam bao:

- KHONG gui rich HTML tho tu DB/editor
- `cv_text` la plain text
- `description`, `requirements`, `responsibilities` la plain text
- giu newline / bullet hop ly de AI con parser duoc

Neu web chua co helper clean text tot,
hay tao / cap nhat helper dung cho ca employer-side va candidate-side.

Khuyen nghi:

- `html_entity_decode(...)`
- doi `<br>`, `</p>`, `</li>` thanh newline
- `strip_tags(...)`
- thay `&nbsp;` bang space
- chuan hoa whitespace
- giu bullet/list line

==================================================
7. Response moi cua `/screening`
==================================================

Screening response hien tai co them:

{
  "trace_id": "screening-xxxxxxxxxxxx",
  "job": {...},
  "candidates": [...],
  "diagnostics": {
    "endpoint": "screening",
    "trace_id": "screening-xxxxxxxxxxxx",
    "payload": {
      "job": {
        "flags": [...],
        "warnings": [...],
        "quality_label": "ok|info|warning",
        "source": {
          "used_raw_text": true,
          "used_job_description_text": false,
          "used_structured_sections": true
        },
        "metrics": {
          "jd_word_count": 120,
          "requirements_count": 6,
          "responsibilities_count": 4,
          "html_tag_count": 0
        }
      },
      "candidates": {
        "received_count": 4,
        "flagged_count": 1,
        "flag_counts": {
          "cv_text_too_short": 1
        },
        "flagged_candidates": [
          {
            "application_id": 15,
            "candidate_id": 4,
            "candidate_name": "..."
          }
        ],
        "warnings": [...],
        "metrics": {
          "min_cv_word_count": 12,
          "max_cv_word_count": 430,
          "avg_cv_word_count": 188.5
        }
      }
    },
    "runtime": {
      "embedding_enabled": false,
      "candidate_count": 4,
      "ranked_candidate_count": 4,
      "job_quality": {
        "quality_score": 82,
        "quality_label": "eligible",
        "recommendation_eligible": true,
        "flags": [...],
        "reasons": [...],
        "metrics": {...}
      },
      "screening_confidence": {...},
      "taxonomy_coverage": {...},
      "open_set_requirement_count": 0
    }
  }
}

==================================================
8. Response moi cua `/recommend-jobs`
==================================================

Recommendation response hien tai co them:

{
  "trace_id": "recommend-jobs-xxxxxxxxxxxx",
  "candidate": {...},
  "top_jobs": [...],
  "excluded_jobs": [
    {
      "job_id": 99,
      "job_title": "Test",
      "job_quality": {...},
      "payload_diagnostics": {
        "flags": [...],
        "warnings": [...],
        "quality_label": "warning",
        "metrics": {...}
      }
    }
  ],
  "retrieval_stats": {...},
  "job_quality_stats": {...},
  "warnings": [...],
  "diagnostics": {
    "endpoint": "recommend-jobs",
    "trace_id": "recommend-jobs-xxxxxxxxxxxx",
    "payload": {
      "candidate": {
        "flags": [...],
        "warnings": [...],
        "quality_label": "ok|info|warning",
        "source": {
          "source_mode": "cv_text|resume_text|structured_cv",
          "used_structured_profile": false
        },
        "metrics": {
          "cv_word_count": 210,
          "skill_count": 8,
          "work_experience_count": 2,
          "project_count": 1
        }
      },
      "jobs": {
        "received_count": 20,
        "flagged_count": 6,
        "flag_counts": {...},
        "flagged_jobs": [...],
        "warnings": [...],
        "metrics": {
          "min_jd_word_count": 2,
          "max_jd_word_count": 220,
          "avg_jd_word_count": 88.3
        },
        "eligible_jobs": 14,
        "excluded_jobs": 6,
        "excluded_job_ids": [4, 10, 15]
      }
    },
    "runtime": {
      "embedding_enabled": false,
      "jobs_received": 20,
      "jobs_indexed": 14,
      "jobs_retrieved": 10,
      "jobs_reranked": 10,
      "top_k": 10,
      "retrieval_top_n": 20,
      "retrieval_applied": true,
      "eligible_jobs": 14,
      "excluded_jobs": 6,
      "top_job_ids": [18, 20, 12],
      "excluded_job_ids": [4, 10, 15]
    }
  }
}

==================================================
9. Employer-side web can sua gi
==================================================

Hay tim va cap nhat cac file employer-side dang:

- build payload cho `/screening`
- parse response `/screening`
- luu ket qua AI vao DB hoac session
- render table/list AI rank
- render modal/chi tiet AI review

### 9.1 Bat buoc luu / propagate `trace_id`

Moi lan goi `/screening`, phai lay:

- `trace_id`

va:

- log vao `storage/logs/...`
- neu dang luu DB ket qua AI, them cot/field neu can:
  - `ai_trace_id`
  - `ai_diagnostics_json` (optional)

Neu khong muon sua DB ngay,
it nhat phai log `trace_id` trong PHP log.

### 9.2 Hien warning strip neu payload co van de

Neu `diagnostics.payload.job.flags` khong rong:

- hien mot warning strip nho o khu employer screening
- vi du:
  - `JD nay dang co dau hieu du lieu yeu cho AI`

Neu `diagnostics.payload.candidates.flagged_count > 0`:

- hien thong diep:
  - `Mot so CV dau vao qua ngan hoac qua sparse, ket qua AI can recruiter review ky hon.`

### 9.3 Detail/debug panel trong modal AI review

Trong modal `AI review` hoac trang chi tiet,
them mot section nho co the collapse:

`AI diagnostics`

Noi dung:

- Trace ID
- Job payload flags
- Candidate flagged count
- Job quality label
- Job quality reasons
- Screening confidence

Khong can lam lo UI chinh.
Nen la collapse hoac admin-only debug section.

==================================================
10. Candidate-side web can sua gi
==================================================

Hay tim va cap nhat cac file candidate-side dang:

- build payload cho `/recommend-jobs`
- parse response
- luu session result / cache result
- render list job goi y
- render modal chi tiet AI

### 10.1 Bump session schema version neu dang dung session

Neu candidate-side recommendation dang luu session,
phai bump schema version them 1 don vi
de bo session cu khong co `trace_id` / `diagnostics`.

Vi du:

- Phase 24 da la version 2
- Phase 25 nang len version 3

Muc tieu:

tranh UI doc response cu va render sai.

### 10.2 Luu / log `trace_id`

Moi lan goi `/recommend-jobs`, phai:

- lay `trace_id`
- luu vao session result
- hien nho tren UI debug/local neu can
- log vao file log PHP

### 10.3 Render warnings than thien tu diagnostics

Neu `diagnostics.payload.candidate.flags` co:

- `cv_text_too_short`
- `candidate_profile_sparse`
- `html_cleaning_changed_text_heavily`

thi hien warning strip nho:

`CV hien tai co the chua du thong tin de AI danh gia toi uu.`

Neu `diagnostics.payload.jobs.warnings` khong rong:

- hien warning strip nho o tren list recommendation

### 10.4 Giu nguyen quy tac candidate-side dung

Van phai:

- render `top_jobs` trong list chinh
- render `excluded_jobs` trong khu rieng
- dung `fit_label`, `fit_score`, `fit_summary`
- KHONG dung `recommendation` employer-side lam nhan chinh
- KHONG tron `excluded_jobs` vao `top_jobs`

### 10.5 Diagnostics panel cho candidate-side

Them mot panel/accordion debug nho:

`AI diagnostics`

Noi dung co the gom:

- Trace ID
- Candidate payload flags
- Job payload flagged count
- Eligible jobs / excluded jobs
- Top job IDs / excluded job IDs

Neu khong muon hien cho user thuong,
co the:

- chi hien o local/dev
- hoac chi hien cho admin/debug role

==================================================
11. Logging phai bo sung
==================================================

Neu codebase web da co log file cho AI,
hay bo sung log sau:

### 11.1 Employer-side

- `trace_id`
- `endpoint=screening`
- `health_phase`
- `job_payload_flags`
- `candidate_flagged_count`
- `ranked_candidate_count`

### 11.2 Candidate-side

- `trace_id`
- `endpoint=recommend-jobs`
- `health_phase`
- `candidate_payload_flags`
- `flagged_jobs_count`
- `top_jobs_count`
- `excluded_jobs_count`

Neu response khong co `trace_id` hoac `diagnostics`
trong khi `/health.phase` la Phase 25,
hay log warning ro rang.

==================================================
12. UI de xuat theo phong cach TOPCV Lite
==================================================

Can giu phong cach:

- sach
- de scan nhanh
- khong marketing style
- khong card trong card

### 12.1 Employer-side

O screening page / modal:

- warning strip gon
- trace id nho, mau xam, o khu debug
- diagnostics section collapse

### 12.2 Candidate-side

O recommendation page:

- warning strip neu CV/JD payload yeu
- summary strip:
  - Jobs analyzed
  - Eligible jobs
  - Excluded jobs
  - Flagged jobs
- diagnostics section collapse phia duoi

==================================================
13. File/luong can inspect trong web
==================================================

Hay tu inspect codebase va tim cac file tuong tu:

- config AI API URL
- health-check helper
- screening API service
- recommendation API service
- screening payload builder
- recommendation payload builder
- session storage helper
- DB save helper (neu employer-side luu ket qua AI)
- UI partial / page cho employer screening
- UI partial / page cho candidate recommendation

Neu da co file debug/audit cu,
hay tai su dung no thay vi tao mot style moi.

==================================================
14. Acceptance criteria
==================================================

Sau khi sua xong:

1. Web van goi duoc `/screening` va `/recommend-jobs`.
2. Web lay duoc `trace_id` tu ca hai endpoint.
3. Web parse duoc `diagnostics` tu ca hai endpoint.
4. Candidate-side van dung `fit_label` lam nhan chinh.
5. Candidate-side van tach rieng `excluded_jobs`.
6. Employer-side co warning neu payload job/candidate co van de.
7. Web log duoc `trace_id` + diagnostics summary.
8. Session cu (neu co) khong gay render sai contract Phase 25.
9. Payload gui sang AI van la plain text sach, khong phai HTML tho.

==================================================
15. Cach lam mong muon
==================================================

Hay:

1. Inspect codebase web hien tai
2. Tim cac file dang xu ly `/screening` va `/recommend-jobs`
3. Cap nhat parser/service layer theo contract Phase 25
4. Cap nhat logging/debug
5. Cap nhat UI employer-side va candidate-side o muc vua du
6. Bao cao lai:
   - file nao da sua
   - field nao dang doc tu response
   - co bump session schema version hay khong
   - cach test local

Neu can chon giua:

- sua it file nhung kho maintain
- hay sua dung layer service + UI ro rang

thi uu tien cach de maintain va debug ve sau.
```
