# AI Improvement Roadmap

## Phase A — Data Foundation
- Chuẩn hóa candidate/job schema (skills, years, seniority, domain).
- Triển khai CV/JD parsing async và lưu output structured.
- Tạo feature store tối thiểu cho matching.

## Phase B — Matching V1 (Rule-Based)
- Score theo hard constraints: location, experience tối thiểu, job type.
- Score theo kỹ năng overlap có trọng số.
- Trả explainability JSON để recruiter hiểu lý do.

## Phase C — Matching V2 (Hybrid Semantic)
- Embedding CV/JD cho semantic similarity.
- Kết hợp score rule-based + semantic score.
- Chặn keyword stuffing bằng độ phủ ngữ cảnh.

## Phase D — Recommendation & Ranking
- Candidate-side: recommend jobs theo lịch sử view/save/apply.
- Recruiter-side: rank ứng viên theo fit score và quality signals.
- Ghi lịch sử recommendation/scoring để audit.

## Phase E — Governance & Quality
- Bias/fairness monitoring định kỳ.
- Offline/online evaluation (precision@k, apply conversion uplift).
- Human-in-the-loop feedback để calibrate mô hình.
