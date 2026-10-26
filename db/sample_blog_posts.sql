-- Two published starter articles. Existing gallery uploads are reused as images.
INSERT INTO blog_posts (title, slug, excerpt, content, category, image_path, author, is_featured, is_published)
SELECT
  'Five easy ways to choose your next meal',
  'five-easy-ways-to-choose-your-next-meal',
  'A simple guide to exploring categories, comparing dishes, and ordering food that fits the moment.',
  'Great food ordering begins with a clear choice. Start with the category you are craving, compare descriptions and prices, then use the menu filters to narrow the list. Whether you are choosing a quick lunch, a family dinner, or something sweet, Foodera keeps every step simple from menu browsing to checkout.',
  'Food guide',
  COALESCE((SELECT image_path FROM site_gallery_images WHERE is_visible=1 ORDER BY sort_order, image_id LIMIT 1), ''),
  'Foodera Team',
  1,
  1
WHERE NOT EXISTS (SELECT 1 FROM blog_posts WHERE slug='five-easy-ways-to-choose-your-next-meal');

INSERT INTO blog_posts (title, slug, excerpt, content, category, image_path, author, is_featured, is_published)
SELECT
  'How Foodera makes ordering feel organized',
  'how-foodera-makes-ordering-feel-organized',
  'From menu discovery to billing, every part of the ordering journey stays in one connected flow.',
  'Foodera is designed to make everyday food ordering feel calm and clear. Visitors can browse freely, save their choices in a cart, review offers that apply to their order, and move through checkout with confidence. Restaurants benefit from a consistent menu presence while customers get a familiar ordering experience each time.',
  'Foodera updates',
  COALESCE((SELECT image_path FROM site_gallery_images WHERE is_visible=1 ORDER BY sort_order, image_id LIMIT 1 OFFSET 1), ''),
  'Foodera Team',
  0,
  1
WHERE NOT EXISTS (SELECT 1 FROM blog_posts WHERE slug='how-foodera-makes-ordering-feel-organized');
