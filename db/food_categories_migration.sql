CREATE TABLE IF NOT EXISTS `food_categories` (
  `category_id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `food_categories_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @food_category_status_sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE food_categories ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER name',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'food_categories' AND COLUMN_NAME = 'is_active'
);
PREPARE food_category_status_stmt FROM @food_category_status_sql;
EXECUTE food_category_status_stmt;
DEALLOCATE PREPARE food_category_status_stmt;

INSERT IGNORE INTO `food_categories` (`name`)
SELECT DISTINCT TRIM(`category`) FROM `foods` WHERE TRIM(`category`) <> '';
