-- One starter menu item for every Foodera menu category.
-- Safe to run again: a sample is only inserted when that category has no menu item.
INSERT INTO foods (name, description, price, picture, category)
SELECT samples.name, samples.description, samples.price, '', samples.category
FROM (
    SELECT 'Butter Chicken Biryani' AS name, 'Fragrant basmati rice, tender chicken, and slow-cooked spices.' AS description, 299 AS price, 'Biryani' AS category UNION ALL
    SELECT 'Margherita Pizza', 'Stone-baked pizza with tomato, mozzarella, and fresh basil.', 249, 'Pizza' UNION ALL
    SELECT 'Crispy Chicken Burger', 'Crunchy chicken fillet, lettuce, cheese, and house sauce.', 219, 'Burgers' UNION ALL
    SELECT 'Schezwan Fried Rice', 'Wok-tossed rice with vegetables and a warm Schezwan kick.', 189, 'Chinese' UNION ALL
    SELECT 'Paneer Butter Masala', 'Creamy tomato gravy with soft paneer and aromatic spices.', 239, 'North Indian' UNION ALL
    SELECT 'Masala Dosa', 'Crisp rice crepe filled with spiced potato and served with chutneys.', 149, 'South Indian' UNION ALL
    SELECT 'Kosha Mangsho', 'Slow-cooked Bengali-style mutton curry with rich spices.', 349, 'Bengali' UNION ALL
    SELECT 'Chicken Reshmi Kebab', 'Tender grilled chicken kebabs with a delicate creamy marinade.', 279, 'Mughlai' UNION ALL
    SELECT 'Chicken Kathi Roll', 'Spiced chicken, onion, and sauces wrapped in a warm paratha.', 159, 'Rolls & Wraps' UNION ALL
    SELECT 'Steamed Veg Momos', 'Soft dumplings filled with seasoned vegetables and served with dip.', 129, 'Momos' UNION ALL
    SELECT 'Grilled Cheese Sandwich', 'Golden grilled bread with a generous cheese filling.', 139, 'Sandwiches' UNION ALL
    SELECT 'Creamy Alfredo Pasta', 'Penne pasta in a smooth garlic cream sauce.', 229, 'Pasta' UNION ALL
    SELECT 'Veg Hakka Noodles', 'Wok-tossed noodles with crisp vegetables and savoury seasoning.', 179, 'Noodles' UNION ALL
    SELECT 'Hot Chicken Wings', 'Crispy fried chicken wings finished with a bold hot glaze.', 259, 'Fried Chicken' UNION ALL
    SELECT 'Tandoori Chicken Platter', 'Charred chicken pieces marinated in yoghurt and Indian spices.', 329, 'Kebabs & Tandoori' UNION ALL
    SELECT 'Crispy Fish Fry', 'Lightly spiced fish fillets fried until golden and crisp.', 289, 'Seafood' UNION ALL
    SELECT 'Classic Veg Thali', 'A complete meal with curry, dal, rice, bread, and sides.', 249, 'Thali & Combos' UNION ALL
    SELECT 'Jeera Rice', 'Steamed basmati rice tempered with cumin and ghee.', 119, 'Rice & Pulao' UNION ALL
    SELECT 'Butter Naan', 'Soft tandoor-baked flatbread brushed with butter.', 49, 'Roti & Breads' UNION ALL
    SELECT 'Mumbai Pav Bhaji', 'Spiced vegetable mash served with buttered pav.', 129, 'Street Food' UNION ALL
    SELECT 'Papdi Chaat', 'Crisp papdi layered with yoghurt, chutneys, and spices.', 119, 'Chaat' UNION ALL
    SELECT 'Crispy Veg Spring Rolls', 'Crunchy rolls filled with seasoned garden vegetables.', 149, 'Snacks' UNION ALL
    SELECT 'Masala Omelette', 'Fluffy eggs with onions, tomatoes, herbs, and green chilli.', 109, 'Breakfast' UNION ALL
    SELECT 'Protein Power Bowl', 'Wholesome grains, roasted vegetables, and a fresh dressing.', 259, 'Healthy Food' UNION ALL
    SELECT 'Greek Salad', 'Cucumber, tomato, olives, feta, and a bright herb dressing.', 199, 'Salads' UNION ALL
    SELECT 'Sweet Corn Soup', 'Comforting sweet corn soup with vegetables and pepper.', 109, 'Soups' UNION ALL
    SELECT 'Chocolate Brownie', 'Warm chocolate brownie with a soft, fudgy centre.', 129, 'Desserts' UNION ALL
    SELECT 'Red Velvet Pastry', 'Moist red velvet sponge with smooth cream cheese frosting.', 149, 'Cakes & Pastries' UNION ALL
    SELECT 'Belgian Chocolate Sundae', 'Chocolate ice cream, sauce, nuts, and whipped cream.', 169, 'Ice Cream' UNION ALL
    SELECT 'Gulab Jamun', 'Soft milk dumplings soaked in fragrant cardamom syrup.', 99, 'Indian Sweets' UNION ALL
    SELECT 'Chocolate Waffle', 'Fresh waffle with chocolate sauce and a crisp golden edge.', 189, 'Waffles & Pancakes' UNION ALL
    SELECT 'Mango Smoothie', 'Chilled mango smoothie blended until creamy and bright.', 139, 'Shakes & Smoothies' UNION ALL
    SELECT 'Fresh Orange Juice', 'Freshly pressed orange juice served chilled.', 119, 'Juices' UNION ALL
    SELECT 'Cappuccino', 'Espresso with steamed milk and a silky foam finish.', 129, 'Tea & Coffee' UNION ALL
    SELECT 'Virgin Mojito', 'Lime, mint, and sparkling soda over crushed ice.', 109, 'Beverages' UNION ALL
    SELECT 'Chocolate Chip Cookie', 'Fresh-baked cookie with a soft centre and chocolate chips.', 79, 'Bakery' UNION ALL
    SELECT 'Herb Grilled Chicken', 'Juicy grilled chicken with herbs, vegetables, and light jus.', 329, 'Continental' UNION ALL
    SELECT 'Vegetable Lasagna', 'Layered pasta, roasted vegetables, tomato sauce, and cheese.', 269, 'Italian' UNION ALL
    SELECT 'Chicken Burrito Bowl', 'Mexican rice, beans, chicken, salsa, and fresh toppings.', 279, 'Mexican' UNION ALL
    SELECT 'Loaded Cheese Fries', 'Crisp fries topped with cheese sauce and savoury seasoning.', 149, 'Fast Food'
) AS samples
INNER JOIN food_categories AS categories ON categories.name = samples.category
LEFT JOIN foods AS existing ON existing.category = samples.category
WHERE existing.id IS NULL;
