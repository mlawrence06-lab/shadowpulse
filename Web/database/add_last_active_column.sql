-- Add last_active column to member_stats
SET @dbname = DATABASE();
SET @tablename = "member_stats";
SET @columnname = "last_active";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE member_stats ADD COLUMN last_active datetime DEFAULT CURRENT_TIMESTAMP"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
