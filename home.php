
<?php
session_start();
require_once __DIR__ . '/includes/site-content.php';
require_once __DIR__ . '/includes/offers.php';
if (empty($_SESSION['cart_csrf'])) $_SESSION['cart_csrf'] = bin2hex(random_bytes(32));
$isLoggedIn = isset($_SESSION['user']);
$count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$homeCategories = array();
$homeFavourites = array();
$homeCategoryImages = array();
foreach (siteGallery() as $galleryImage) {
    $galleryCategory = trim((string) ($galleryImage['category_name'] ?? ''));
    if ($galleryCategory !== '' && empty($homeCategoryImages[$galleryCategory])) {
        $homeCategoryImages[$galleryCategory] = siteAsset($galleryImage['image_path']);
    }
}
$homeConnection = dbConnect();
if ($homeConnection) {
    $categoryStatus = @mysqli_query($homeConnection, "SHOW COLUMNS FROM food_categories LIKE 'is_active'");
    $foodStatus = @mysqli_query($homeConnection, "SHOW COLUMNS FROM foods LIKE 'is_active'");
    $galleryStatus = @mysqli_query($homeConnection, "SHOW COLUMNS FROM site_gallery_images LIKE 'is_auto_hidden'");
    $hasCategoryStatus = $categoryStatus && mysqli_num_rows($categoryStatus) > 0;
    $hasFoodStatus = $foodStatus && mysqli_num_rows($foodStatus) > 0;
    $hasGalleryStatus = $galleryStatus && mysqli_num_rows($galleryStatus) > 0;
    $galleryImage = "(SELECT gallery.image_path FROM site_gallery_images AS gallery WHERE gallery.category_name=foods.category AND gallery.is_visible=1" . ($hasGalleryStatus ? ' AND gallery.is_auto_hidden=0' : '') . " ORDER BY gallery.sort_order,gallery.image_id LIMIT 1)";
    $categorySql = $hasCategoryStatus ? 'SELECT name FROM food_categories WHERE is_active=1 ORDER BY name LIMIT 6' : 'SELECT DISTINCT category AS name FROM foods ORDER BY category LIMIT 6';
    $categoryResult = @mysqli_query($homeConnection, $categorySql);
    if ($categoryResult) $homeCategories = mysqli_fetch_all($categoryResult, MYSQLI_ASSOC);
    $favouriteSql = 'SELECT foods.*,COALESCE(NULLIF(foods.picture, \'\'), ' . $galleryImage . ') AS display_picture FROM foods' . ($hasCategoryStatus ? ' INNER JOIN food_categories ON food_categories.name=foods.category WHERE food_categories.is_active=1' : ' WHERE 1=1') . ($hasFoodStatus ? ' AND foods.is_active=1' : '') . ' ORDER BY foods.id DESC LIMIT 8';
    $favouritesResult = @mysqli_query($homeConnection, $favouriteSql);
    if ($favouritesResult) $homeFavourites = mysqli_fetch_all($favouritesResult, MYSQLI_ASSOC);
}
$pageTitle = 'Online Food Delivery';
$activePage = 'home';
$bodyClass = 'home-page';
$pageStyles = array('css/home.css');
$pageScripts = array('js/home-offer-carousel.js');
$showHeroVideo = true;
$heroVideoPoster = siteImage('home.hero_poster');
$heroContent = '<div class="content">
            <p class="hero-kicker">' . siteEscape(siteSetting('home.kicker')) . '</p>
            <h1>' . siteEscape(siteSetting('home.hero_title')) . '</h1>
            <h2>' . siteEscape(siteSetting('home.tagline')) . '</h2>
            <form class="hero-food-search" action="menu.php" method="get" role="search">
                <label><i class="bi bi-search" aria-hidden="true"></i><span class="sr-only">Search the menu</span><input type="search" name="q" maxlength="100" placeholder="Search biryani, pizza, burgers..." autocomplete="off"></label>
                <button type="submit">Find food <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
            </form>
            <div class="hero-actions">
                <a class="btn btn-primary btn-lg" href="menu.php" role="button">' . siteEscape(siteSetting('home.hero_cta')) . ' <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
        </div>';
include('includes/public-header.php');
?>

    <?php if ($homeCategories) { ?><section class="home-discovery home-categories" aria-labelledby="home-categories-heading"><div class="home-section-heading"><p><?php echo siteEscape(siteSetting('home.categories_kicker')); ?></p><h2 id="home-categories-heading"><?php echo siteEscape(siteSetting('home.categories_title')); ?></h2></div><div class="home-category-grid"><?php foreach ($homeCategories as $homeCategory) { $categoryName = $homeCategory['name']; $categorySlug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $categoryName), '-')); $categoryImage = $homeCategoryImages[$categoryName] ?? ''; if ($categoryImage === '') { foreach ($homeFavourites as $favourite) { if ($favourite['category'] === $categoryName && !empty($favourite['display_picture'])) { $categoryImage = $favourite['display_picture']; break; } } } ?><a href="menu.php?category=<?php echo rawurlencode($categorySlug); ?>" class="home-category-card"><?php if ($categoryImage) { ?><img src="<?php echo siteEscape($categoryImage); ?>" alt="<?php echo siteEscape($categoryName); ?> dishes"><?php } else { ?><span class="home-category-fallback"><i class="bi bi-cup-hot" aria-hidden="true"></i></span><?php } ?><span><?php echo siteEscape($categoryName); ?></span><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a><?php } ?></div></section><?php } ?>

    <?php if ($homeFavourites) { ?><section class="home-discovery home-favourites" aria-labelledby="home-favourites-heading"><div class="home-section-heading home-section-heading-row"><div><p><?php echo siteEscape(siteSetting('home.favourites_kicker')); ?></p><h2 id="home-favourites-heading"><?php echo siteEscape(siteSetting('home.favourites_title')); ?></h2></div><a href="menu.php">View full menu <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div><div class="home-favourites-grid"><?php foreach ($homeFavourites as $favourite) { ?><article class="home-favourite-card"><?php if (!empty($favourite['display_picture'])) { ?><img src="<?php echo siteEscape($favourite['display_picture']); ?>" alt="<?php echo siteEscape($favourite['name']); ?>" loading="lazy"><?php } else { ?><div class="home-favourite-fallback"><i class="bi bi-cup-hot" aria-hidden="true"></i></div><?php } ?><div><span><?php echo siteEscape($favourite['category']); ?></span><h3><?php echo siteEscape($favourite['name']); ?></h3><div class="home-favourite-footer"><strong>Rs <?php echo number_format((float) $favourite['price']); ?></strong><form action="manage-cart.php" method="post"><input type="hidden" name="csrf" value="<?php echo siteEscape($_SESSION['cart_csrf']); ?>"><input type="hidden" name="pid" value="<?php echo (int) $favourite['id']; ?>"><button type="submit" name="add" aria-label="Add <?php echo siteEscape($favourite['name']); ?> to cart"><i class="bi bi-plus-lg" aria-hidden="true"></i><span><?php echo $isLoggedIn ? 'Add' : 'Order'; ?></span></button></form></div></div></article><?php } ?></div></section><?php } ?>

    <section class="home-highlights" aria-label="Foodera benefits">
        <div class="highlight-card">
            <i class="bi bi-cup-hot" aria-hidden="true"></i>
            <strong><?php echo siteEscape(siteSetting('home.highlight_1_title')); ?></strong>
            <span><?php echo siteEscape(siteSetting('home.highlight_1_body')); ?></span>
        </div>
        <div class="highlight-card">
            <i class="bi bi-lightning-charge" aria-hidden="true"></i>
            <strong><?php echo siteEscape(siteSetting('home.highlight_2_title')); ?></strong>
            <span><?php echo siteEscape(siteSetting('home.highlight_2_body')); ?></span>
        </div>
        <div class="highlight-card">
            <i class="bi bi-shield-check" aria-hidden="true"></i>
            <strong><?php echo siteEscape(siteSetting('home.highlight_3_title')); ?></strong>
            <span><?php echo siteEscape(siteSetting('home.highlight_3_body')); ?></span>
        </div>
    </section>

    <?php $homeOffers = offerPublicList(); if ($homeOffers) { ?>
    <section class="home-offers" aria-labelledby="home-offers-heading">
        <div class="home-offers-heading"><p>Current offers</p><h2 id="home-offers-heading">Save on your next order</h2><a href="offers.php">View all offers <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
        <div id="homeOfferCarousel" class="carousel slide home-offer-carousel" data-bs-ride="carousel">
            <?php if (count($homeOffers) > 1) { ?><div class="carousel-indicators"><?php foreach ($homeOffers as $index => $offer) { ?><button type="button" data-bs-target="#homeOfferCarousel" data-bs-slide-to="<?php echo $index; ?>"<?php echo $index === 0 ? ' class="active" aria-current="true"' : ''; ?> aria-label="Offer <?php echo $index + 1; ?>"></button><?php } ?></div><?php } ?>
            <div class="carousel-inner">
                <?php foreach ($homeOffers as $index => $offer) { ?><article class="carousel-item<?php echo $index === 0 ? ' active' : ''; ?>">
                    <?php if ($offer['image_path']) { ?><img src="<?php echo siteEscape($offer['image_path']); ?>" alt="<?php echo siteEscape($offer['title']); ?>"><?php } ?>
                    <div class="container">
                        <p class="home-offer-category"><?php echo siteEscape($offer['category_name']); ?></p>
                        <h3><?php echo siteEscape($offer['title']); ?></h3>
                        <strong><?php echo siteEscape(offerFormatValue($offer)); ?></strong>
                        <p><?php echo siteEscape($offer['description']); ?></p>
                        <?php if ($offer['code']) { ?><code><?php echo siteEscape($offer['code']); ?></code><?php } else { ?><small><i class="bi bi-lightning-charge-fill" aria-hidden="true"></i> Applied automatically when eligible</small><?php } ?>
                        <a href="offers.php">View offer <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                </article><?php } ?>
            </div>
            <?php if (count($homeOffers) > 1) { ?><button class="carousel-control-prev" type="button" data-bs-target="#homeOfferCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous offer</span></button><button class="carousel-control-next" type="button" data-bs-target="#homeOfferCarousel" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next offer</span></button><?php } ?>
        </div>
    </section>
    <?php } ?>
    <h1 class="heading"><?php echo siteEscape(siteSetting('home.about_section_title')); ?></h1>
    <section class="about" id="about">
        <div class="content-left">
            <img src="<?php echo siteEscape(siteImage('home.about_image')); ?>" alt="Foodera restaurant" class="image">
        </div>
        <div class="content-right">
            <section class="why-choose" aria-label="Why choose Foodera">
                <p class="why-choose-kicker"><?php echo siteEscape(siteSetting('home.why_kicker')); ?></p>
                <h4><?php echo siteEscape(siteSetting('home.why_title')); ?></h4>
                <p class="why-choose-intro"><?php echo siteEscape(siteSetting('home.why_intro')); ?></p>
                <div class="why-choose-grid">
                    <div><i class="bi bi-egg-fried" aria-hidden="true"></i><strong><?php echo siteEscape(siteSetting('home.why_1_title')); ?></strong><span><?php echo siteEscape(siteSetting('home.why_1_body')); ?></span></div>
                    <div><i class="bi bi-bicycle" aria-hidden="true"></i><strong><?php echo siteEscape(siteSetting('home.why_2_title')); ?></strong><span><?php echo siteEscape(siteSetting('home.why_2_body')); ?></span></div>
                    <div><i class="bi bi-tags" aria-hidden="true"></i><strong><?php echo siteEscape(siteSetting('home.why_3_title')); ?></strong><span><?php echo siteEscape(siteSetting('home.why_3_body')); ?></span></div>
                    <div><i class="bi bi-phone" aria-hidden="true"></i><strong><?php echo siteEscape(siteSetting('home.why_4_title')); ?></strong><span><?php echo siteEscape(siteSetting('home.why_4_body')); ?></span></div>
                </div>
            </section>
            <a class="btn btn-primary" href="about-us.php" role=" button ">Learn more</a>
        </div>
    </section>
    <div class="space"></div>
    <div class="service-section" style="--home-services-image: url('<?php echo siteEscape(siteImage('home.services_image')); ?>')">
        <div class="inner-width">
            <h1 class="section-title"><?php echo siteEscape(siteSetting('home.services_title')); ?></h1>
            <div class="border"></div>
            <div class="service-container">
                <div class="service-box">
                    <div class="service-icon"><i class="bi bi-truck" aria-hidden="true"></i></div>
                    <div class="service-title"><?php echo siteEscape(siteSetting('home.service_1_title')); ?></div>
                    <div class="service-desc"><?php echo siteEscape(siteSetting('home.service_1_body')); ?></div>
                </div>
                <div class="service-box">
                    <div class="service-icon"><i class="bi bi-credit-card" aria-hidden="true"></i></div>
                    <div class="service-title"><?php echo siteEscape(siteSetting('home.service_2_title')); ?></div>
                    <div class="service-desc"><?php echo siteEscape(siteSetting('home.service_2_body')); ?></div>
                </div>
                <div class="service-box">
                    <div class="service-icon"><i class="bi bi-house-check" aria-hidden="true"></i></div>
                    <div class="service-title"><?php echo siteEscape(siteSetting('home.service_3_title')); ?></div>
                    <div class="service-desc"><?php echo siteEscape(siteSetting('home.service_3_body')); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="space"></div>
<?php include('includes/public-footer.php'); ?>
