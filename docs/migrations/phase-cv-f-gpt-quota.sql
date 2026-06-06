-- Phase CV-F — Quota Chuẩn GPT import (lifetime per user)
-- Chạy một lần trên localhost / phpMyAdmin.

ALTER TABLE `users`
  ADD COLUMN `cv_gpt_import_uses` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
  COMMENT 'Số lần Chuẩn GPT import CV (lifetime, user thường max 5)'
  AFTER `created_at`;
