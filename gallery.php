<?php
require_once __DIR__ . '/app-functions.php';
require_once __DIR__ . '/includes/site-content.php';
session_start();

$pageTitle = 'Foodera Gallery';
$pageMetaDescription = 'Explore the food, dishes, and menu moments from Foodera.';
$activePage = 'gallery';
$bodyClass = 'gallery-page';
$pageStyles = array('css/gallery-page.css');
$pageScripts = array('js/gallery-page.js');
include __DIR__ . '/includes/public-header.php';
$galleryImages = siteGallery();
$galleryCategories = array_values(array_unique(array_filter(array_map(function ($image) { return trim((string) ($image['category_name'] ?? '')); }, $galleryImages))));
sort($galleryCategories, SORT_NATURAL | SORT_FLAG_CASE);
?>
<main class="foodera-gallery-page">
    <header class="foodera-gallery-hero">
        <p class="foodera-gallery-kicker"><i class="bi bi-images" aria-hidden="true"></i> Foodera moments</p>
        <h1><?php echo siteEscape(siteSetting('about.gallery_title')); ?></h1>
        <p>Explore the dishes and flavours available from Foodera.</p>
    </header>

    <?php if ($galleryImages) { ?>
        <section class="foodera-gallery-tools" aria-label="Find gallery images">
            <div class="foodera-gallery-filters" role="group" aria-label="Filter by category">
                <button type="button" class="is-active" data-gallery-filter="all" aria-pressed="true">All</button>
                <?php foreach ($galleryCategories as $galleryCategory) { ?><button type="button" data-gallery-filter="<?php echo siteEscape(strtolower($galleryCategory)); ?>" aria-pressed="false"><?php echo siteEscape($galleryCategory); ?></button><?php } ?>
            </div>
            <label class="foodera-gallery-search"><i class="bi bi-search" aria-hidden="true"></i><span class="visually-hidden">Search gallery</span><input type="search" data-gallery-search placeholder="Search gallery" autocomplete="off"></label>
        </section>
        <section class="foodera-gallery-wall" aria-label="Foodera image gallery">
            <?php foreach ($galleryImages as $index => $image) {
                $category = trim((string) ($image['category_name'] ?? ''));
                $categorySlug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $category), '-'));
                $tile = ($index % 10) + 1;
                $label = trim((string) ($image['caption'] ?? '')) ?: ($category ?: 'Foodera dish');
            ?>
                <?php if ($category !== '') { ?>
                    <a class="foodera-gallery-tile foodera-gallery-tile--<?php echo $tile; ?>" data-gallery-category="<?php echo siteEscape(strtolower($category)); ?>" data-gallery-search-text="<?php echo siteEscape(strtolower($label . ' ' . $category)); ?>" href="menu.php?category=<?php echo rawurlencode($categorySlug); ?>" aria-label="View <?php echo siteEscape($category); ?> dishes">
                <?php } else { ?>
                    <figure class="foodera-gallery-tile foodera-gallery-tile--<?php echo $tile; ?>" data-gallery-category="" data-gallery-search-text="<?php echo siteEscape(strtolower($label)); ?>">
                <?php } ?>
                    <img src="<?php echo siteEscape(siteAsset($image['image_path'])); ?>" alt="<?php echo siteEscape($label); ?>" loading="lazy">
                    <span class="foodera-gallery-label">
                        <strong><?php echo siteEscape($label); ?></strong>
                        <?php if ($category !== '') { ?><small>View menu <i class="bi bi-arrow-up-right" aria-hidden="true"></i></small><?php } ?>
                    </span>
                <?php if ($category !== '') { ?></a><?php } else { ?></figure><?php } ?>
            <?php } ?>
        </section>
        <p class="foodera-gallery-no-results" data-gallery-empty hidden><i class="bi bi-search" aria-hidden="true"></i> No gallery images match your search.</p>
        <div class="foodera-gallery-more"><button type="button" data-gallery-more><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Load more photos</button></div>
    <?php } else { ?>
        <section class="foodera-gallery-empty"><i class="bi bi-images" aria-hidden="true"></i><h2>Gallery coming soon</h2><p>New Foodera moments will appear here soon.</p></section>
    <?php } ?>
</main>
<?php include __DIR__ . '/includes/public-footer.php'; ?>
