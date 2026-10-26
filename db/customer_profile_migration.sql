ALTER TABLE `customers`
  MODIFY `address` VARCHAR(255) NOT NULL,
  MODIFY `password` VARCHAR(255) NOT NULL;

ALTER TABLE `orders` MODIFY `address` VARCHAR(255) NOT NULL;

CREATE TABLE IF NOT EXISTS `customer_profiles` (
  `customer_email` VARCHAR(50) NOT NULL,
  `photo_path` VARCHAR(255) DEFAULT NULL,
  `dietary_preference` VARCHAR(30) NOT NULL DEFAULT 'none',
  `favorite_cuisines` VARCHAR(255) NOT NULL DEFAULT '',
  `allergies` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`customer_email`),
  CONSTRAINT `customer_profiles_customer_fk` FOREIGN KEY (`customer_email`) REFERENCES `customers` (`email`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `customer_addresses` (
  `address_id` INT NOT NULL AUTO_INCREMENT,
  `customer_email` VARCHAR(50) NOT NULL,
  `label` VARCHAR(50) NOT NULL,
  `address_line` VARCHAR(255) NOT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`address_id`),
  KEY `customer_addresses_email_idx` (`customer_email`),
  CONSTRAINT `customer_addresses_customer_fk` FOREIGN KEY (`customer_email`) REFERENCES `customers` (`email`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `customer_addresses` (`customer_email`, `label`, `address_line`, `is_default`)
SELECT `email`, 'Home', `address`, 1 FROM `customers` c
WHERE TRIM(c.`address`) <> '' AND NOT EXISTS (
  SELECT 1 FROM `customer_addresses` a WHERE a.`customer_email` = c.`email`
);
