-- Adds public visibility control for food items on existing Foodera databases.
SET @food_item_status_sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE foods ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER category',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'foods' AND COLUMN_NAME = 'is_active'
);
PREPARE food_item_status_stmt FROM @food_item_status_sql;
EXECUTE food_item_status_stmt;
DEALLOCATE PREPARE food_item_status_stmt;
