<?php
require_once __DIR__ . '/site-content.php';
$pageTitle = $pageTitle ?? 'Foodera';
$activePage = $activePage ?? '';
$pageStyles = $pageStyles ?? array();
$bodyClass = $bodyClass ?? '';
$headerClass = $headerClass ?? 'header';
$heroContent = $heroContent ?? '';
$showHeroVideo = $showHeroVideo ?? false;
$heroVideoPoster = $heroVideoPoster ?? '';
$useBootstrap = $useBootstrap ?? true;
$pageMetaDescription = $pageMetaDescription ?? '';
$pageMetaImage = $pageMetaImage ?? '';
$pageCanonical = $pageCanonical ?? '';
$isLoggedIn = isset($_SESSION['user']);
$count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$notificationCount = $isLoggedIn ? notificationUnreadCount('customer', $_SESSION['email'] ?? '') : 0;

function fooderaNavClass($page, $activePage)
{
    return $page === $activePage ? ' class="active"' : '';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="<?php echo siteColor('theme.light_primary'); ?>">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <?php if ($pageMetaDescription !== '') { ?><meta name="description" content="<?php echo htmlspecialchars($pageMetaDescription, ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
    <?php if ($pageCanonical !== '') { ?><link rel="canonical" href="<?php echo htmlspecialchars($pageCanonical, ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
    <?php if ($pageMetaDescription !== '') { ?><meta property="og:type" content="article"><meta property="og:title" content="<?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?>"><meta property="og:description" content="<?php echo htmlspecialchars($pageMetaDescription, ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
    <?php if ($pageCanonical !== '') { ?><meta property="og:url" content="<?php echo htmlspecialchars($pageCanonical, ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
    <?php if ($pageMetaImage !== '') { ?><meta property="og:image" content="<?php echo htmlspecialchars($pageMetaImage, ENT_QUOTES, 'UTF-8'); ?>"><meta name="twitter:card" content="summary_large_image"><?php } ?>
    <link rel="icon" href="<?php echo siteEscape(siteImage('branding.favicon')); ?>">
    <link rel="apple-touch-icon" href="<?php echo siteEscape(siteImage('branding.favicon')); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
    <script>
        (function () {
            const savedTheme = localStorage.getItem('foodera-theme');
            const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = savedTheme || (systemDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
    <?php if ($useBootstrap) { ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <?php } ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <?php foreach ($pageStyles as $stylePath) { ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($stylePath); ?>?v=<?php echo (int) @filemtime(__DIR__ . '/../' . ltrim($stylePath, '/')); ?>">
    <?php } ?>
    <link rel="stylesheet" href="css/public-theme.css?v=<?php echo (int) @filemtime(__DIR__ . '/../css/public-theme.css'); ?>">
    <link rel="stylesheet" href="css/ui-system.css">
    <link rel="stylesheet" href="css/public-animations.css">
    <style id="site-theme-colors">
        :root { --primary: <?php echo siteColor('theme.light_primary'); ?>; --primary-hover: color-mix(in srgb, var(--primary), #000 13%); --bg: <?php echo siteColor('theme.light_background'); ?>; --accent: <?php echo siteColor('theme.light_accent'); ?>; --surface: color-mix(in srgb, var(--bg), #fff 70%); --section-bg: var(--surface); --section-alt: color-mix(in srgb, var(--bg), var(--primary) 7%); --soft-panel: color-mix(in srgb, var(--surface), transparent 8%); --border: color-mix(in srgb, var(--accent), transparent 79%); }
        :root[data-theme="dark"] { --primary: <?php echo siteColor('theme.dark_primary'); ?>; --primary-hover: color-mix(in srgb, var(--primary), #fff 14%); --bg: <?php echo siteColor('theme.dark_background'); ?>; --accent: <?php echo siteColor('theme.dark_accent'); ?>; --surface: color-mix(in srgb, var(--bg), #fff 7%); --section-bg: var(--surface); --section-alt: color-mix(in srgb, var(--bg), var(--accent) 9%); --soft-panel: color-mix(in srgb, var(--surface), transparent 10%); --border: color-mix(in srgb, var(--accent), transparent 72%); }
        :root[data-theme="dark"] body { background: var(--bg); }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css">
</head>

<body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <div class="<?php echo htmlspecialchars($headerClass); ?>">
        <?php if ($showHeroVideo) { ?>
            <video autoplay loop class="back-vedio" muted playsinline<?php echo $heroVideoPoster !== '' ? ' poster="' . htmlspecialchars($heroVideoPoster, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
                <source src="media/bg2video.mp4" type="video/mp4">
            </video>
        <?php } ?>
        <nav class="site-header-nav">
            <a class="site-logo-link" href="home.php" aria-label="Foodera home">
                <img src="<?php echo siteEscape(siteImage('branding.logo')); ?>" class="logo site-logo" alt="Foodera logo">
            </a>
            <button class="nav-toggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="public-nav-links">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            <ul class="nav-links site-nav-links" id="public-nav-links">
                <li<?php echo fooderaNavClass('home', $activePage); ?>><a href="home.php"><i class="bi bi-house-fill" aria-hidden="true"></i>Home</a></li>
                <li<?php echo fooderaNavClass('menu', $activePage); ?>><a href="menu.php"><i class="bi bi-cup-hot-fill" aria-hidden="true"></i>Menu</a></li>
                <li<?php echo fooderaNavClass('offers', $activePage); ?>><a href="offers.php"><i class="bi bi-tags-fill" aria-hidden="true"></i>Offers</a></li>
                <li class="site-nav-more<?php echo in_array($activePage, array('about', 'gallery', 'contact', 'blog'), true) ? ' active' : ''; ?>"><details><summary><i class="bi bi-grid-3x3-gap-fill" aria-hidden="true"></i>More <i class="bi bi-chevron-down" aria-hidden="true"></i></summary><div class="site-nav-more-menu"><a<?php echo fooderaNavClass('about', $activePage); ?> href="about-us.php"><i class="bi bi-info-circle-fill" aria-hidden="true"></i>About</a><a<?php echo fooderaNavClass('gallery', $activePage); ?> href="gallery.php"><i class="bi bi-images" aria-hidden="true"></i>Gallery</a><a<?php echo fooderaNavClass('contact', $activePage); ?> href="contact-us.php"><i class="bi bi-telephone-fill" aria-hidden="true"></i>Contact</a><a<?php echo fooderaNavClass('blog', $activePage); ?> href="blog.php"><i class="bi bi-journal-richtext" aria-hidden="true"></i>Blogs</a></div></details></li>
                <?php if ($isLoggedIn) { ?>
                    <li<?php echo fooderaNavClass('orders', $activePage); ?>><a href="order-history.php"><i class="bi bi-receipt-cutoff" aria-hidden="true"></i>My Orders</a></li>
                    <li<?php echo fooderaNavClass('profile', $activePage); ?>><a href="profile.php"><i class="bi bi-person-circle" aria-hidden="true"></i>Profile</a></li>
                <?php } ?>
                <li class="site-nav-action-item">
                    <button class="theme-toggle site-nav-action" type="button" id="theme-toggle" aria-label="Toggle dark mode" title="Toggle dark mode">
                        <i class="bi bi-moon" aria-hidden="true"></i>
                    </button>
                </li>
                <?php if ($isLoggedIn) { ?>
                    <li class="site-nav-action-item"><a href="notifications.php" class="site-nav-action site-cart-link" aria-label="Notifications" title="Notifications"><i class="bi bi-bell" aria-hidden="true"></i><?php if ($notificationCount > 0) { ?><span class="site-cart-count"><?php echo $notificationCount > 99 ? '99+' : $notificationCount; ?></span><?php } ?></a></li>
                    <li class="site-nav-action-item">
                        <a href="cart.php" class="site-nav-action site-cart-link" aria-label="Cart, <?php echo $count; ?> items" title="Cart">
                            <i class="bi bi-cart3" aria-hidden="true"></i>
                            <?php if ($count > 0) { ?><span class="site-cart-count"><?php echo $count; ?></span><?php } ?>
                        </a>
                    </li>
                    <li class="site-nav-action-item"><a href="logout.php" class="site-nav-action" aria-label="Sign out" title="Sign out"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></a></li>
                <?php } else { ?>
                    <li class="site-nav-action-item">
                        <a href="login.php" class="site-nav-action" aria-label="Sign in" title="Sign in"><i class="bi bi-person" aria-hidden="true"></i></a>
                    </li>
                <?php } ?>
            </ul>
        </nav>
        <?php echo $heroContent; ?>
    </div>
