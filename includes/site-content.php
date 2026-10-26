<?php
require_once __DIR__ . '/../app-functions.php';

function siteContentFields()
{
    return array(
        'branding' => array(
            'branding.logo' => array('Logo', 'image', 'assets/branding/foodera-wordmark.png'),
            'branding.favicon' => array('Favicon', 'image', 'assets/branding/foodera-icon.png'),
        ),
        'appearance' => array(
            'theme.light_primary' => array('Light primary', 'color', '#ff3d00'),
            'theme.light_background' => array('Light background', 'color', '#f7f7f5'),
            'theme.light_accent' => array('Light accent', 'color', '#2f86a6'),
            'theme.dark_primary' => array('Dark primary', 'color', '#ff5a2c'),
            'theme.dark_background' => array('Dark background', 'color', '#20242b'),
            'theme.dark_accent' => array('Dark accent', 'color', '#66c7df'),
        ),
        'home' => array(
            'home.kicker' => array('Hero label', 'text', 'Fresh food delivered fast'),
            'home.title' => array('Hero title', 'text', 'FOODERA'),
            'home.hero_title' => array('Hero headline', 'text', 'Delicious food, delivered to your door.'),
            'home.tagline' => array('Hero tagline', 'text', 'Fresh flavours, delivered with care.'),
            'home.hero_cta' => array('Hero secondary button', 'text', 'Explore menu'),
            'home.hero_poster' => array('Hero video poster', 'image', 'image/resturent.jpg'),
            'home.categories_kicker' => array('Categories section label', 'text', 'Explore the menu'),
            'home.categories_title' => array('Categories section heading', 'text', 'What are you craving?'),
            'home.favourites_kicker' => array('Featured dishes section label', 'text', 'Fresh picks'),
            'home.favourites_title' => array('Featured dishes section heading', 'text', 'Featured dishes, ready to order'),
            'home.highlight_1_title' => array('First highlight title', 'text', 'Curated Menu'),
            'home.highlight_1_body' => array('First highlight copy', 'textarea', 'Popular meals, snacks, drinks, and desserts in one clean menu.'),
            'home.highlight_2_title' => array('Second highlight title', 'text', 'Quick Ordering'),
            'home.highlight_2_body' => array('Second highlight copy', 'textarea', 'Browse first, login only when you are ready to add items.'),
            'home.highlight_3_title' => array('Third highlight title', 'text', 'Reliable Checkout'),
            'home.highlight_3_body' => array('Third highlight copy', 'textarea', 'Cart, payment, and bill flow organized for smooth ordering.'),
            'home.about_section_title' => array('About section label', 'text', 'About us'),
            'home.why_kicker' => array('Why choose label', 'text', 'Why Choose Foodera?'),
            'home.why_title' => array('Why choose heading', 'text', 'Delicious Food, Delivered with Care!'),
            'home.why_intro' => array('Why choose introduction', 'textarea', 'At Foodera, we make ordering your favourite meals simple, convenient and enjoyable. From local favourites to exciting new flavours, great food is just a few taps away.'),
            'home.why_1_title' => array('Why choose benefit 1 title', 'text', 'Wide Food Selection'),
            'home.why_1_body' => array('Why choose benefit 1 copy', 'text', 'Explore delicious meals from a variety of restaurants.'),
            'home.why_2_title' => array('Why choose benefit 2 title', 'text', 'Convenient Delivery'),
            'home.why_2_body' => array('Why choose benefit 2 copy', 'text', 'Enjoy your favourite food delivered to your doorstep.'),
            'home.why_3_title' => array('Why choose benefit 3 title', 'text', 'Exciting Offers'),
            'home.why_3_body' => array('Why choose benefit 3 copy', 'text', 'Save more with special discounts and promotions.'),
            'home.why_4_title' => array('Why choose benefit 4 title', 'text', 'Easy Ordering'),
            'home.why_4_body' => array('Why choose benefit 4 copy', 'text', 'Browse, choose and order in just a few taps.'),
            'home.about_image' => array('About image', 'image', 'image/resturent.jpg'),
            'home.services_title' => array('Services heading', 'text', 'Our Services'),
            'home.service_1_title' => array('First service title', 'text', 'Fast delivery'),
            'home.service_1_body' => array('First service copy', 'textarea', 'Order your favourite dishes through a clear menu and move quickly from cart to checkout.'),
            'home.service_2_title' => array('Second service title', 'text', 'Secure payment'),
            'home.service_2_body' => array('Second service copy', 'textarea', 'Payment and bill details are handled inside a dedicated order flow for better tracking.'),
            'home.service_3_title' => array('Third service title', 'text', 'At door service'),
            'home.service_3_body' => array('Third service copy', 'textarea', 'A delivery-focused experience keeps customers, menu items, orders, and billing connected.'),
            'home.services_image' => array('Services background', 'image', 'image/service-background.jpg'),
        ),
        'about' => array(
            'about.kicker' => array('Page label', 'text', 'About Foodera'),
            'about.title' => array('Page heading', 'text', 'Food ordering made simple, fast, and reliable.'),
            'about.intro' => array('Introduction', 'textarea', 'Launched in 2010, Foodera connects customers, restaurant partners, and delivery partners through one organized food ordering experience.'),
            'about.image' => array('Hero image', 'image', 'image/resturent.jpg'),
            'about.cta_label' => array('Hero button label', 'text', 'Explore the menu'),
            'about.stat_1_value' => array('First statistic value', 'text', '2010'),
            'about.stat_1_label' => array('First statistic label', 'text', 'Launched'),
            'about.stat_2_value' => array('Second statistic value', 'text', '3'),
            'about.stat_2_label' => array('Second statistic label', 'text', 'Connected flows'),
            'about.stat_3_value' => array('Third statistic value', 'text', '24/7'),
            'about.stat_3_label' => array('Third statistic label', 'text', 'Order access'),
            'about.services_kicker' => array('Service section label', 'text', 'Made for everyday ordering'),
            'about.services_title' => array('Service section heading', 'text', 'Everything needed for a better food moment.'),
            'about.story_1_title' => array('First story heading', 'text', 'Who are we?'),
            'about.story_1_body' => array('First story copy', 'textarea', 'Customers use Foodera to discover restaurants, browse food, manage carts, order delivery, book tables, and keep billing details connected.'),
            'about.story_2_title' => array('Second story heading', 'text', 'For restaurants'),
            'about.story_2_body' => array('Second story copy', 'textarea', 'We provide restaurant partners with practical tools to reach customers, grow orders, and support a reliable last-mile delivery service.'),
            'about.story_3_title' => array('Third story heading', 'text', 'From our blog'),
            'about.story_3_body' => array('Third story copy', 'textarea', 'Explore articles, food stories, resources, and ideas that inspire the Foodera community and make ordering feel more connected.'),
            'about.why_kicker' => array('Why section label', 'text', 'Why Foodera'),
            'about.why_title' => array('Why section heading', 'text', 'Food that fits your day.'),
            'about.why_intro' => array('Why section introduction', 'textarea', 'From first browse to final delivery update, Foodera keeps the essentials clear and dependable.'),
            'about.why_image' => array('Why section image', 'image', 'image/service-background.jpg'),
            'about.why_1_title' => array('Why benefit 1 heading', 'text', 'Clear choice'),
            'about.why_1_body' => array('Why benefit 1 copy', 'textarea', 'Find dishes by category, compare options, and choose with confidence.'),
            'about.why_2_title' => array('Why benefit 2 heading', 'text', 'Order visibility'),
            'about.why_2_body' => array('Why benefit 2 copy', 'textarea', 'Keep track of your order from confirmation through delivery.'),
            'about.why_3_title' => array('Why benefit 3 heading', 'text', 'Support when needed'),
            'about.why_3_body' => array('Why benefit 3 copy', 'textarea', 'Order history, cancellation policy, and account tools stay within reach.'),
            'about.gallery_title' => array('Gallery heading', 'text', 'Picture Gallery'),
        ),
        'contact' => array(
            'contact.title' => array('Page heading', 'text', 'Need help with food ordering or delivery?'),
            'contact.intro' => array('Introduction', 'textarea', 'Send us a message and the Foodera team will keep your ordering experience connected.'),
            'contact.form_title' => array('Form heading', 'text', 'Send a Message'),
            'contact.form_intro' => array('Form introduction', 'text', 'Email us and keep up to date with our latest updates.'),
            'contact.map' => array('Contact image', 'image', 'image/map.jpg'),
            'contact.address' => array('Contact address', 'text', '658 Salt Lake City Block Sector 1, Kolkata-700064'),
            'contact.phone' => array('Contact phone', 'text', '+91 03324567890'),
            'contact.email' => array('Contact email', 'text', 'foodera@gmail.com'),
        ),
        'blog' => array(
            'blog.title' => array('Blog heading', 'text', 'Stories from our kitchen'),
            'blog.intro' => array('Blog introduction', 'textarea', 'Food, ordering tips, and news from the Foodera community.'),
        ),
        'shared' => array(
            'menu.heading' => array('Menu heading', 'text', 'Our special'),
            'footer.tagline' => array('Footer tagline', 'textarea', 'Fresh meals, quick ordering, and organized delivery service from one clean Foodera experience.'),
            'footer.cta' => array('Footer button', 'text', 'Order now'),
            'footer.contact_1' => array('Footer contact line 1', 'text', 'Quick food delivery'),
            'footer.contact_2' => array('Footer contact line 2', 'text', 'Orders and billing in one flow'),
            'footer.contact_3' => array('Footer contact line 3', 'text', 'Secure checkout support'),
        ),
    );
}

function siteContentField($key)
{
    foreach (siteContentFields() as $fields) if (isset($fields[$key])) return $fields[$key];
    return null;
}

function siteSetting($key)
{
    static $values = null;
    $field = siteContentField($key);
    if (!$field) return '';
    if ($values === null) {
        $values = array();
        $conn = dbConnect();
        $result = $conn ? @mysqli_query($conn, 'SELECT setting_key,setting_value FROM site_settings') : false;
        if ($result) while ($row = mysqli_fetch_assoc($result)) $values[$row['setting_key']] = $row['setting_value'];
    }
    return isset($values[$key]) && $values[$key] !== '' ? $values[$key] : $field[2];
}

function siteEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function siteAsset($path)
{
    $path = (string) $path;
    if (preg_match('~^uploads/site/[a-f0-9]{32}\.(jpg|png|webp)$~', $path)) {
        $file = __DIR__ . '/../' . $path;
        return is_file($file) ? $path . '?v=' . filemtime($file) : '';
    }
    return preg_match('~^(assets/branding/|image/)[a-zA-Z0-9 _./-]+\.(jpg|jpeg|png|webp)$~', $path) && strpos($path, '..') === false ? $path : '';
}

function siteImage($key)
{
    $field = siteContentField($key);
    if (!$field || $field[1] !== 'image') return '';
    return siteAsset(siteSetting($key)) ?: siteAsset($field[2]);
}

function siteColor($key)
{
    $value = siteSetting($key);
    $field = siteContentField($key);
    return $field && $field[1] === 'color' && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : ($field[2] ?? '#20242a');
}

function siteGallery()
{
    $defaults = array('image/resturent1.jpg','image/cafe.jpg','image/dinning.jpg','image/moderndinning.jpg','image/res-food.jpg','image/GarlicChickenCheeseBall.jpg','image/Green Tea Creame Frappuccino.jpg','image/18.jpg','image/15.jpeg','image/9.jpg','image/23.png');
    $conn = dbConnect();
    $categoryColumn = $conn ? @mysqli_query($conn, "SHOW COLUMNS FROM site_gallery_images LIKE 'category_name'") : false;
    $autoHiddenColumn = $conn ? @mysqli_query($conn, "SHOW COLUMNS FROM site_gallery_images LIKE 'is_auto_hidden'") : false;
    $hasCategory = $categoryColumn && mysqli_num_rows($categoryColumn) > 0;
    $hasAutoHidden = $autoHiddenColumn && mysqli_num_rows($autoHiddenColumn) > 0;
    $result = $conn ? @mysqli_query($conn, 'SELECT image_id,image_path,caption,sort_order,' . ($hasCategory ? 'category_name' : "'' AS category_name") . ' FROM site_gallery_images WHERE is_visible=1' . ($hasAutoHidden ? ' AND is_auto_hidden=0' : '') . ' ORDER BY sort_order,image_id') : false;
    if (!$result) return array_map(function ($path) { return array('image_path'=>$path,'caption'=>'Foodera gallery','category_name'=>''); }, $defaults);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}
