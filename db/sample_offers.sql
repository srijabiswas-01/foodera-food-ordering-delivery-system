-- Foodera sample offers. Safe to run more than once after offers_migration.sql.
-- The date window keeps the samples available for testing through 2027.

INSERT INTO offers (offer_category_id,title,description,terms,code,discount_type,discount_value,maximum_discount,minimum_order,customer_scope,starts_at,ends_at,total_usage_limit,per_customer_limit,daily_usage_limit,is_automatic,is_combinable,is_active,image_path,priority,minimum_eligible_subtotal,days_of_week)
SELECT c.offer_category_id,'Welcome to Foodera','Save 50% on your first eligible order, up to Rs 150.','For a customer\'s first completed order. Minimum cart value Rs 299. One use per customer.','WELCOME50','percentage',50,150,299,'first_order','2026-01-01 00:00:00','2027-12-31 23:59:59',NULL,1,NULL,1,0,1,'uploads/offers/welcome-offer.png',100,299,''
FROM offer_categories c WHERE c.name='First Purchase' AND NOT EXISTS (SELECT 1 FROM offers WHERE code='WELCOME50');

INSERT INTO offers (offer_category_id,title,description,terms,code,discount_type,discount_value,maximum_discount,minimum_order,customer_scope,starts_at,ends_at,total_usage_limit,per_customer_limit,daily_usage_limit,is_automatic,is_combinable,is_active,image_path,priority,minimum_eligible_subtotal,days_of_week)
SELECT c.offer_category_id,'Today: Rs 100 off','Get Rs 100 off when eligible dishes in your cart total Rs 599 or more.','Valid while the offer is active. Eligible dishes only. One use per customer each day.','TODAY100','fixed',100,NULL,599,'all','2026-01-01 00:00:00','2027-12-31 23:59:59',NULL,1,100,1,0,1,'uploads/offers/today-offer.png',80,599,''
FROM offer_categories c WHERE c.name='Today''s Specials' AND NOT EXISTS (SELECT 1 FROM offers WHERE code='TODAY100');

INSERT INTO offers (offer_category_id,title,description,terms,code,discount_type,discount_value,maximum_discount,minimum_order,customer_scope,starts_at,ends_at,total_usage_limit,per_customer_limit,daily_usage_limit,is_automatic,is_combinable,is_active,image_path,priority,minimum_eligible_subtotal,days_of_week)
SELECT c.offer_category_id,'Save Rs 200 on bigger orders','Spend Rs 1,299 or more and save Rs 200 automatically.','Valid on a qualifying cart while the offer is active.','SAVE200','fixed',200,NULL,1299,'all','2026-01-01 00:00:00','2027-12-31 23:59:59',NULL,1,NULL,1,0,1,'uploads/offers/family-offer.png',70,0,''
FROM offer_categories c WHERE c.name='Order Value' AND NOT EXISTS (SELECT 1 FROM offers WHERE code='SAVE200');

INSERT INTO offers (offer_category_id,title,description,terms,code,discount_type,discount_value,maximum_discount,minimum_order,customer_scope,starts_at,ends_at,total_usage_limit,per_customer_limit,daily_usage_limit,is_automatic,is_combinable,is_active,image_path,priority,minimum_eligible_subtotal,days_of_week)
SELECT c.offer_category_id,'Dessert Delight','Enjoy 20% off selected desserts, cakes, ice cream, and waffles.','Eligible dessert items must total at least Rs 249. Maximum discount Rs 80.','DESSERT20','percentage',20,80,249,'all','2026-01-01 00:00:00','2027-12-31 23:59:59',NULL,1,NULL,1,0,1,'uploads/offers/dessert-offer.png',60,249,''
FROM offer_categories c WHERE c.name='Food Categories' AND NOT EXISTS (SELECT 1 FROM offers WHERE code='DESSERT20');

INSERT INTO offers (offer_category_id,title,description,terms,code,discount_type,discount_value,maximum_discount,minimum_order,customer_scope,starts_at,ends_at,total_usage_limit,per_customer_limit,daily_usage_limit,is_automatic,is_combinable,is_active,image_path,priority,minimum_eligible_subtotal,days_of_week)
SELECT c.offer_category_id,'Weekend Feast','Save 15% on a weekend feast of Biryani, Pizza, or Burgers.','Valid Saturday and Sunday only. Minimum eligible items value Rs 599. Maximum discount Rs 175.','WEEKEND15','percentage',15,175,599,'all','2026-01-01 00:00:00','2027-12-31 23:59:59',NULL,1,NULL,1,0,1,'uploads/offers/family-offer.png',75,599,'6,7'
FROM offer_categories c WHERE c.name='Weekend Offers' AND NOT EXISTS (SELECT 1 FROM offers WHERE code='WEEKEND15');

INSERT INTO offers (offer_category_id,title,description,terms,code,discount_type,discount_value,maximum_discount,minimum_order,customer_scope,starts_at,ends_at,total_usage_limit,per_customer_limit,daily_usage_limit,is_automatic,is_combinable,is_active,image_path,priority,minimum_eligible_subtotal,days_of_week)
SELECT c.offer_category_id,'Festival Feast','Use this sample festival coupon to save Rs 250 on a larger celebration order.','Minimum cart value Rs 1,499. One use per customer while the offer is active.','FESTIVE250','fixed',250,NULL,1499,'all','2026-01-01 00:00:00','2027-12-31 23:59:59',500,1,NULL,0,0,1,'uploads/offers/family-offer.png',40,0,''
FROM offer_categories c WHERE c.name='Festival Offers' AND NOT EXISTS (SELECT 1 FROM offers WHERE code='FESTIVE250');

INSERT INTO offers (offer_category_id,title,description,terms,code,discount_type,discount_value,maximum_discount,minimum_order,customer_scope,starts_at,ends_at,total_usage_limit,per_customer_limit,daily_usage_limit,is_automatic,is_combinable,is_active,image_path,priority,minimum_eligible_subtotal,days_of_week)
SELECT c.offer_category_id,'Foodera Treat','Use this sample promotion code for Rs 75 off your order.','Minimum cart value Rs 449. One use per customer while the offer is active.','TREAT75','fixed',75,NULL,449,'all','2026-01-01 00:00:00','2027-12-31 23:59:59',NULL,1,NULL,0,0,1,'uploads/offers/today-offer.png',30,0,''
FROM offer_categories c WHERE c.name='Promotion Offers' AND NOT EXISTS (SELECT 1 FROM offers WHERE code='TREAT75');

-- Category-limited offer targets.
INSERT IGNORE INTO offer_food_categories (offer_id,category_id)
SELECT o.offer_id,fc.category_id FROM offers o JOIN food_categories fc ON fc.name IN ('Biryani','Pizza') WHERE o.code='TODAY100';
INSERT IGNORE INTO offer_food_categories (offer_id,category_id)
SELECT o.offer_id,fc.category_id FROM offers o JOIN food_categories fc ON fc.name IN ('Desserts','Cakes & Pastries','Ice Cream','Indian Sweets','Waffles & Pancakes') WHERE o.code='DESSERT20';
INSERT IGNORE INTO offer_food_categories (offer_id,category_id)
SELECT o.offer_id,fc.category_id FROM offers o JOIN food_categories fc ON fc.name IN ('Biryani','Pizza','Burgers') WHERE o.code='WEEKEND15';

-- Item-specific sample target. It must also belong to a selected category above.
INSERT IGNORE INTO offer_food_items (offer_id,food_id)
SELECT o.offer_id,f.id FROM offers o JOIN foods f ON f.name IN ('Butter Chicken Biryani','Margherita Pizza') WHERE o.code='TODAY100';
