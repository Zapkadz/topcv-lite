# Current Task — TopCV Lite

> Cập nhật: **2026-05-29**

---

## Trạng thái

| Phase | Status |
|-------|--------|
| 1 + 1.1 | ✅ |
| Docs chuẩn hóa | ✅ |
| **2A Status model** | ✅ **PASS** |
| **2B Soft delete job** | ⏳ Chờ **`「xác nhận 2B」`** |

---

## Git gợi ý (2A)

```powershell
cd c:\xampp\htdocs\topcv_lite
git checkout main
git pull
git checkout -b feature/phase-2-2a-user-status
git add .
git commit -m "phase 2: tách trạng thái tài khoản employer (nhóm 2A)"
git push -u origin feature/phase-2-2a-user-status
```

→ PR merge `main`

---

## Bước tiếp theo

Reply **`「xác nhận 2B」`** — soft delete + lifecycle job (`docs/phase-2-mini-plan.md`)

**Git:** User tự commit/push.
