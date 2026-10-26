-- Run this on an existing old database before uploading the updated PHP files.
-- A fresh install can import "foodera.sql" directly instead.

RENAME TABLE
  `admin_td` TO `admins`,
  `customer` TO `customers`,
  `food` TO `foods`,
  `message` TO `messages`,
  `order` TO `orders`,
  `payment` TO `payments`,
  `user_order` TO `order_items`;

ALTER TABLE `foods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `messages`
  CHANGE COLUMN `messages` `message` varchar(50) NOT NULL,
  MODIFY `messageid` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `payments`
  CHANGE COLUMN ` month` `exp_month` varchar(2) NOT NULL,
  CHANGE COLUMN ` year` `exp_year` varchar(4) NOT NULL,
  MODIFY `card` varchar(20) NOT NULL;
