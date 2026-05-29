# Đánh giá hiện trạng AI

Hiện tại codebase **chưa có AI subsystem thực tế** cho CV parsing, JD parsing, matching, scoring, ranking hay recommendation.  
Do đó mọi quyết định tuyển dụng đang manual-first.

# Phân tích theo hạng mục yêu cầu

## CV parsing
- Chưa triển khai.
- Rủi ro: không thể chuẩn hóa dữ liệu skill/experience từ CV.

## JD parsing
- Chưa triển khai.
- Rủi ro: không thể trích requirement trọng số để matching.

## Matching algorithm / scoring / ranking
- Chưa triển khai.
- Rủi ro: không có năng lực ưu tiên ứng viên theo mức phù hợp.

## Recommendation logic
- Chưa triển khai.
- Rủi ro: retention thấp, trải nghiệm candidate không cá nhân hóa.

# Reality check theo case thực tế

- CV đẹp nhưng ít keyword: hệ thống hiện không nhận diện được năng lực thật.
- CV keyword spam: chưa có cơ chế phát hiện.
- Fresher/senior/career switch: chưa có logic weighting theo context.
- Remote/hybrid/multi-role: chưa có semantic matching và preference modeling.
- CV đa ngôn ngữ/typo: chưa có NLP normalization.

# Bias, explainability, chống tối ưu giả

Hiện chưa có AI nên chưa có bias trực tiếp từ model, nhưng khi triển khai sau này cần:
- Explainable score breakdown (skills/experience/industry fit).
- Anti-keyword stuffing signal.
- Fairness check theo giới tính/độ tuổi (không dùng thuộc tính nhạy cảm để chấm).

# Kết luận

Khoảng cách giữa "job board MVP" và "AI recruitment platform" còn rất lớn.  
Cần xây data foundation trước, sau đó mới triển khai AI theo lộ trình tăng dần độ phức tạp.
