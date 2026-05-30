# Current Task — TopCV Lite

> Cập nhật: **2026-05-29**

---

## Trạng thái

| Phase | Status |
|-------|--------|
| 1 + 1.1 | ✅ |
| Docs chuẩn hóa | ✅ |
| **2A Status model** | ✅ Code xong — **chờ user test + migration** |

---

## Bước bắt buộc trước test 2A

```bash
php docs/migrations/run-phase-2a-user-status.php
```

Checklist: `docs/phase-2a-plan.md`

---

## Bước tiếp theo

1. Chạy migration trên DB XAMPP  
2. Test checklist → **`「2A pass」`**  
3. Git branch `feature/phase-2-2a-user-status` + commit  
4. Sau pass → **`「xác nhận 2B」`** (soft delete job)

**Git:** User tự commit/push.
