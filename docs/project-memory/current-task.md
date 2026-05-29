# Current Task — TopCV Lite

> Cập nhật: **2026-05-29**  
> Đọc file này để biết **đang làm gì**, **dừng ở đâu**, **bước tiếp theo chính xác**.

---

## Trạng thái hiện tại

**Phase:** Phase 1 — Critical Fixes  
**Nhóm vừa hoàn tất:** **2B** — CSRF auth + employer + admin — **✅ Code + Test PASSED** (2026-05-29)  
**Nhóm tiếp theo:** **3** — Upload hardening — **chờ mini-plan + user confirm**

---

## BLOCKER

**Không còn blocker active.**

---

## Tiến độ Phase 1 Critical Fixes

| Nhóm | Nội dung | Code | Test user |
|------|----------|------|-----------|
| **1** | UNIQUE apply + duplicate handling | ✅ | ✅ Passed |
| **4** | Fix `$profile` runtime | ✅ | ✅ Passed |
| **2A** | CSRF apply + profile | ✅ | ✅ Passed |
| **2B** | CSRF auth + employer + admin | ✅ | ✅ Passed (2026-05-29) |
| **3** | Upload hardening | ❌ Chưa | ❌ |

---

## Bước tiếp theo

1. **User duyệt mini-plan Nhóm 3** (upload CV + logo) — chưa gửi / chưa confirm.
2. Implement Nhóm 3 trên branch `feature/phase-1-3-upload`.
3. Test → log → project-memory → commit + PR.

---

## Ghi chú cho AI chat mới

- Nhóm 2B pass 2026-05-29; CSRF đã phủ hầu hết POST (trừ xóa bằng GET).
- Không bắt đầu Nhóm 3 cho đến khi user confirm mini-plan.
