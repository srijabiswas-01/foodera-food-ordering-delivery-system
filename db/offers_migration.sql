-- Foodera offers: run once for an existing database before using Offers.
CREATE TABLE IF NOT EXISTS food_categories (
  category_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS offer_categories (
  offer_category_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  description VARCHAR(255) NOT NULL DEFAULT '',
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY offer_categories_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS offers (
  offer_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  offer_category_id INT NOT NULL,
  title VARCHAR(140) NOT NULL,
  description VARCHAR(600) NOT NULL,
  terms TEXT NOT NULL,
  code VARCHAR(40) DEFAULT NULL,
  discount_type ENUM('percentage','fixed','free_delivery') NOT NULL DEFAULT 'percentage',
  discount_value DECIMAL(10,2) NOT NULL DEFAULT 0,
  maximum_discount DECIMAL(10,2) DEFAULT NULL,
  minimum_order DECIMAL(10,2) NOT NULL DEFAULT 0,
  applicable_food_category VARCHAR(80) DEFAULT NULL,
  customer_scope ENUM('all','first_order') NOT NULL DEFAULT 'all',
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  total_usage_limit INT DEFAULT NULL,
  per_customer_limit INT NOT NULL DEFAULT 1,
  daily_usage_limit INT DEFAULT NULL,
  is_automatic TINYINT(1) NOT NULL DEFAULT 0,
  is_combinable TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  image_path VARCHAR(255) NOT NULL DEFAULT '',
  priority INT NOT NULL DEFAULT 0,
  minimum_eligible_subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  days_of_week VARCHAR(32) NOT NULL DEFAULT '',
  daily_start_time TIME DEFAULT NULL,
  daily_end_time TIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY offers_code_unique (code),
  KEY offers_active_dates_idx (is_active, starts_at, ends_at),
  CONSTRAINT offers_category_fk FOREIGN KEY (offer_category_id) REFERENCES offer_categories(offer_category_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Specific menu items selected for an offer. An empty set means every item is eligible.
CREATE TABLE IF NOT EXISTS offer_food_items (
  offer_id INT NOT NULL,
  food_id INT NOT NULL,
  PRIMARY KEY (offer_id, food_id),
  CONSTRAINT offer_food_items_offer_fk FOREIGN KEY (offer_id) REFERENCES offers(offer_id) ON DELETE CASCADE,
  CONSTRAINT offer_food_items_food_fk FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS offer_redemptions (
  redemption_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  offer_id INT NOT NULL,
  order_id INT DEFAULT NULL,
  customer_email VARCHAR(100) NOT NULL,
  offer_code VARCHAR(40) DEFAULT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  discount_amount DECIMAL(10,2) NOT NULL,
  redeemed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY offer_redemptions_offer_idx (offer_id, redeemed_at),
  KEY offer_redemptions_customer_idx (customer_email, offer_id, redeemed_at),
  KEY offer_redemptions_order_idx (order_id),
  CONSTRAINT offer_redemptions_offer_fk FOREIGN KEY (offer_id) REFERENCES offers(offer_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Financial snapshot used for the final checkout amount and admin order history.
CREATE TABLE IF NOT EXISTS order_financials (
  orderid INT NOT NULL PRIMARY KEY,
  subtotal DECIMAL(10,2) NOT NULL,
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(10,2) NOT NULL,
  offer_id INT DEFAULT NULL,
  offer_code VARCHAR(40) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY order_financials_offer_idx (offer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories selected for an offer. An empty set means the whole menu is eligible.
CREATE TABLE IF NOT EXISTS offer_food_categories (
  offer_id INT NOT NULL,
  category_id INT NOT NULL,
  PRIMARY KEY (offer_id, category_id),
  CONSTRAINT offer_food_categories_offer_fk FOREIGN KEY (offer_id) REFERENCES offers(offer_id) ON DELETE CASCADE,
  CONSTRAINT offer_food_categories_category_fk FOREIGN KEY (category_id) REFERENCES food_categories(category_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
