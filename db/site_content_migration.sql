CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key` VARCHAR(80) NOT NULL PRIMARY KEY,
  `setting_value` TEXT NOT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `site_gallery_images` (
  `image_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `image_path` VARCHAR(255) NOT NULL,
  `caption` VARCHAR(120) NOT NULL DEFAULT '',
  `category_name` VARCHAR(50) NOT NULL DEFAULT '',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_visible` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @gallery_category_column_sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE site_gallery_images ADD COLUMN category_name VARCHAR(50) NOT NULL DEFAULT '''' AFTER caption',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'site_gallery_images' AND COLUMN_NAME = 'category_name'
);
PREPARE gallery_category_column_stmt FROM @gallery_category_column_sql;
EXECUTE gallery_category_column_stmt;
DEALLOCATE PREPARE gallery_category_column_stmt;

SET @gallery_auto_hidden_column_sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE site_gallery_images ADD COLUMN is_auto_hidden TINYINT(1) NOT NULL DEFAULT 0 AFTER is_visible',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'site_gallery_images' AND COLUMN_NAME = 'is_auto_hidden'
);
PREPARE gallery_auto_hidden_column_stmt FROM @gallery_auto_hidden_column_sql;
EXECUTE gallery_auto_hidden_column_stmt;
DEALLOCATE PREPARE gallery_auto_hidden_column_stmt;

INSERT INTO `site_gallery_images` (`image_path`,`caption`,`sort_order`)
SELECT d.image_path, d.caption, d.sort_order FROM (
  SELECT 'image/resturent1.jpg' AS image_path, 'Restaurant interior' AS caption, 1 AS sort_order UNION ALL
  SELECT 'image/cafe.jpg', 'Cafe', 2 UNION ALL
  SELECT 'image/dinning.jpg', 'Dining', 3 UNION ALL
  SELECT 'image/moderndinning.jpg', 'Modern dining', 4 UNION ALL
  SELECT 'image/res-food.jpg', 'Fresh food', 5 UNION ALL
  SELECT 'image/GarlicChickenCheeseBall.jpg', 'Garlic chicken cheese balls', 6 UNION ALL
  SELECT 'image/Green Tea Creame Frappuccino.jpg', 'Green tea frappe', 7 UNION ALL
  SELECT 'image/18.jpg', 'Dessert', 8 UNION ALL
  SELECT 'image/15.jpeg', 'Foodera dish', 9 UNION ALL
  SELECT 'image/9.jpg', 'Chocolate mousse', 10 UNION ALL
  SELECT 'image/23.png', 'Foodera dish', 11
) AS d WHERE NOT EXISTS (SELECT 1 FROM `site_gallery_images` LIMIT 1);
