<?php
include('app-functions.php');
require_once __DIR__ . '/includes/site-content.php';
session_start();
$isLoggedIn = isset($_SESSION['user']);
$count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$pageTitle = 'About Us';
$activePage = 'about';
$bodyClass = 'about-page';
$pageStyles = array('css/menu.css');
include('includes/public-header.php');
?>
    <main class="about-main">
        <section class="about-hero-panel">
            <div class="about-copy">
                <p class="about-kicker"><?php echo siteEscape(siteSetting('about.kicker')); ?></p>
                <h1><?php echo siteEscape(siteSetting('about.title')); ?></h1>
                <p><?php echo siteEscape(siteSetting('about.intro')); ?></p>
                <a class="about-hero-cta" href="menu.php"><?php echo siteEscape(siteSetting('about.cta_label')); ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                <div class="about-stats">
                    <div><strong><?php echo siteEscape(siteSetting('about.stat_1_value')); ?></strong><span><?php echo siteEscape(siteSetting('about.stat_1_label')); ?></span></div>
                    <div><strong><?php echo siteEscape(siteSetting('about.stat_2_value')); ?></strong><span><?php echo siteEscape(siteSetting('about.stat_2_label')); ?></span></div>
                    <div><strong><?php echo siteEscape(siteSetting('about.stat_3_value')); ?></strong><span><?php echo siteEscape(siteSetting('about.stat_3_label')); ?></span></div>
                </div>
            </div>
            <div class="about-image-card">
                <img src="<?php echo siteEscape(siteImage('about.image')); ?>" alt="Foodera restaurant interior">
            </div>
        </section>

        <section class="about-services" aria-labelledby="about-services-title">
            <div class="about-section-heading"><p class="about-kicker"><?php echo siteEscape(siteSetting('about.services_kicker')); ?></p><h2 id="about-services-title"><?php echo siteEscape(siteSetting('about.services_title')); ?></h2></div>
            <div class="about-story-grid">
            <article>
                <i class="bi bi-grid-1x2" aria-hidden="true"></i>
                <h2><?php echo siteEscape(siteSetting('about.story_1_title')); ?></h2>
                <p><?php echo siteEscape(siteSetting('about.story_1_body')); ?></p>
            </article>
            <article>
                <i class="bi bi-bag-check" aria-hidden="true"></i>
                <h2><?php echo siteEscape(siteSetting('about.story_2_title')); ?></h2>
                <p><?php echo siteEscape(siteSetting('about.story_2_body')); ?></p>
            </article>
            <article>
                <i class="bi bi-truck" aria-hidden="true"></i>
                <h2><?php echo siteEscape(siteSetting('about.story_3_title')); ?></h2>
                <p><?php echo siteEscape(siteSetting('about.story_3_body')); ?></p>
            </article>
            </div>
        </section>

        <section class="about-why" aria-labelledby="about-why-title">
            <div class="about-why-image"><img src="<?php echo siteEscape(siteImage('about.why_image')); ?>" alt="Foodera food and delivery experience"></div>
            <div class="about-why-copy"><p class="about-kicker"><?php echo siteEscape(siteSetting('about.why_kicker')); ?></p><h2 id="about-why-title"><?php echo siteEscape(siteSetting('about.why_title')); ?></h2><p class="about-why-intro"><?php echo siteEscape(siteSetting('about.why_intro')); ?></p><div class="about-benefits"><article><i class="bi bi-check2-circle" aria-hidden="true"></i><div><h3><?php echo siteEscape(siteSetting('about.why_1_title')); ?></h3><p><?php echo siteEscape(siteSetting('about.why_1_body')); ?></p></div></article><article><i class="bi bi-clock-history" aria-hidden="true"></i><div><h3><?php echo siteEscape(siteSetting('about.why_2_title')); ?></h3><p><?php echo siteEscape(siteSetting('about.why_2_body')); ?></p></div></article><article><i class="bi bi-shield-check" aria-hidden="true"></i><div><h3><?php echo siteEscape(siteSetting('about.why_3_title')); ?></h3><p><?php echo siteEscape(siteSetting('about.why_3_body')); ?></p></div></article></div></div>
        </section>
    </main>
<?php include('includes/public-footer.php'); ?>
