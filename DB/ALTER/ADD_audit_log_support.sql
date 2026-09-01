-- 既存テーブルには、updated_at / updated_by と同じ意味を持つ
-- update_datetime / update_user_id が既に存在し、各更新処理でも設定されています。
-- そのため ALTER TABLE は不要です。重複カラムを追加せず、監査画面では
-- update_datetime AS updated_at / update_user_id AS updated_by として利用します。

-- 本番DBで事前確認する場合:
SELECT TABLE_NAME, COLUMN_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('users', 'practices', 'games', 'events')
  AND COLUMN_NAME IN ('update_datetime', 'update_user_id')
ORDER BY TABLE_NAME, COLUMN_NAME;
