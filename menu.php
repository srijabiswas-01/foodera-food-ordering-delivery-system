<?php
include('app-functions.php');
require_once __DIR__ . '/includes/site-content.php';
session_start();
if (empty($_SESSION['cart_csrf'])) $_SESSION['cart_csrf'] = bin2hex(random_bytes(32));
$connect = dbConnect();
$categories = array();
if ($connect) {
    $statusColumn = @mysqli_query($connect, "SHOW COLUMNS FROM food_categories LIKE 'is_active'");
    $hasCategoryStatus = $statusColumn && mysqli_num_rows($statusColumn) > 0;
    $foodStatusColumn = @mysqli_query($connect, "SHOW COLUMNS FROM foods LIKE 'is_active'");
    $hasFoodStatus = $foodStatusColumn && mysqli_num_rows($foodStatusColumn) > 0;
    $galleryAutoHiddenColumn = @mysqli_query($connect, "SHOW COLUMNS FROM site_gallery_images LIKE 'is_auto_hidden'");
    $hasGalleryAutoHidden = $galleryAutoHiddenColumn && mysqli_num_rows($galleryAutoHiddenColumn) > 0;
    $galleryFallback = "COALESCE(NULLIF(foods.picture, ''), (SELECT gallery.image_path FROM site_gallery_images AS gallery WHERE gallery.category_name=foods.category AND gallery.is_visible=1" . ($hasGalleryAutoHidden ? ' AND gallery.is_auto_hidden=0' : '') . " ORDER BY gallery.sort_order,gallery.image_id LIMIT 1)) AS display_picture";
    $foodsResult = $hasCategoryStatus
        ? @mysqli_query($connect, 'SELECT foods.*,' . $galleryFallback . ' FROM foods INNER JOIN food_categories ON food_categories.name=foods.category WHERE food_categories.is_active=1' . ($hasFoodStatus ? ' AND foods.is_active=1' : '') . ' ORDER BY foods.id')
        : ($hasFoodStatus ? @mysqli_query($connect, 'SELECT foods.*,' . $galleryFallback . ' FROM foods WHERE is_active=1 ORDER BY id') : getAllfood());
    $categoriesResult = @mysqli_query($connect, $hasCategoryStatus ? 'SELECT name FROM food_categories WHERE is_active=1 ORDER BY name' : 'SELECT name FROM food_categories ORDER BY name');
    if ($categoriesResult) {
        while ($categoryRow = mysqli_fetch_assoc($categoriesResult)) $categories[] = $categoryRow['name'];
    }
} else $foodsResult = false;
$foods = $foodsResult ? mysqli_fetch_all($foodsResult, MYSQLI_ASSOC) : array();
foreach ($foods as $food) {
    if (!in_array($food['category'], $categories, true)) $categories[] = $food['category'];
}
sort($categories, SORT_NATURAL | SORT_FLAG_CASE);
$isLoggedIn = isset($_SESSION['user']);
$count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$menuSearchQuery = substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
$pageTitle = 'Food Menu';
$activePage = 'menu';
$pageStyles = array('css/mainmenu.css', 'css/menu.css');
include('includes/public-header.php');
?>
    <section id="food-menu">
        <h2 class="food-menu-heading"><?php echo siteEscape(siteSetting('menu.heading')); ?></h2>
        <?php if ($foods) { ?><div class="menu-toolbar">
            <label class="menu-search"><i class="bi bi-search" aria-hidden="true"></i><input type="search" id="menu-search" value="<?php echo htmlspecialchars($menuSearchQuery, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search food and categories" autocomplete="off"></label>
            <label class="menu-category-select"><i class="bi bi-funnel" aria-hidden="true"></i><select id="menu-category-filter" aria-label="Filter menu by category"><option value="all">All categories</option><?php foreach ($categories as $categoryName) { ?><option value="<?php echo htmlspecialchars(strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $categoryName), '-')), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?></option><?php } ?></select></label>
            <button type="button" class="menu-reset" id="menu-reset" hidden><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset</button>
        </div><?php } ?>
        <div class="menu">
        
        <?php
            if(!$foodsResult) { ?>
                <p class="database-error">Menu is unavailable because the database connection is not configured correctly.</p>
            <?php } else {
            if (!$foods) { ?><div class="menu-empty-state"><i class="bi bi-cup-hot" aria-hidden="true"></i><h3>No menu items are available yet</h3><p>Our kitchen is preparing the menu. Please check back shortly for fresh dishes and daily specials.</p><a href="offers.php">View current offers <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div><?php }
            foreach ($foods as $row) { ?>
			
 
                           <div class="food-items" data-category="<?php echo htmlspecialchars(strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $row['category']), '-')), ENT_QUOTES, 'UTF-8'); ?>">
                           <form action="manage-cart.php" method="POST">
                           <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['cart_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                           <?php $foodImage = $row['display_picture'] ?? $row['picture']; if (!empty($foodImage)) { ?><img src="<?php echo htmlspecialchars($foodImage, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?>"><?php } else { ?><div class="food-image-placeholder" aria-hidden="true"><i class="bi bi-cup-hot"></i><span><?php echo htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8'); ?></span></div><?php } ?>
                           <div class="details">
                               <div class="details-sub">
                                   <h5><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></h5>
                                   <h5 class="price">Rs<?php echo number_format((float) $row['price']); ?></h5>
                               </div>
                               <span class="food-card-category"><?php echo htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8'); ?></span>
                               <p><?php echo htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <button type="submit" name="add"><?php echo $isLoggedIn ? 'Add to cart' : 'Login to order'; ?></button>
                            <input type="hidden" name="pid" value="<?php echo (int) $row['id']; ?>">

                           </div>
                           </form>
                       </div>
                       <?php  }
                    }
                   
                
          ?>
							
                          


        </div>
        <?php if ($foods) { ?><p class="menu-filter-empty" id="menu-filter-empty" hidden><i class="bi bi-search" aria-hidden="true"></i> No menu items match your search or selected category. Try another filter or reset it.</p><nav class="content-pagination" id="menu-pagination" aria-label="Menu pagination"></nav><?php } ?>
    </section>
    <div class="space"></div>
    <script>
        (function () {
            const items = Array.from(document.querySelectorAll('.food-items'));
            const categoryFilter = document.getElementById('menu-category-filter');
            const search = document.getElementById('menu-search');
            const reset = document.getElementById('menu-reset');
            const pager = document.getElementById('menu-pagination');
            const empty = document.getElementById('menu-filter-empty');
            if (!items.length || !search || !pager || !categoryFilter) return;
            const pageSize = 20;
            const requestedCategory = new URLSearchParams(window.location.search).get('category') || 'all';
            let category = Array.from(categoryFilter.options).some(function (option) { return option.value === requestedCategory; }) ? requestedCategory : 'all'; let query = search.value.trim().toLowerCase(); let page = 1;
            function render() {
                const visible = items.filter(function (item) { return (category === 'all' || item.dataset.category === category) && item.textContent.toLowerCase().includes(query); });
                const pages = Math.max(1, Math.ceil(visible.length / pageSize)); if (page > pages) page = pages;
                items.forEach(function (item) { item.hidden = true; });
                visible.slice((page - 1) * pageSize, page * pageSize).forEach(function (item) { item.hidden = false; });
                empty.hidden = visible.length > 0;
                categoryFilter.value = category;
                reset.hidden = category === 'all' && query === '';
                const hasPagination = pages > 1;
                pager.hidden = !hasPagination;
                const pageNumbers = Array.from({length: pages}, function (_, index) { return index + 1; }).filter(function (number) { return number === 1 || number === pages || Math.abs(number - page) <= 1; });
                const previousButton = '<button type="button" class="menu-page-arrow" data-page="' + (page - 1) + '" aria-label="Previous page" title="Previous page"' + (page === 1 ? ' disabled' : '') + '><i class="bi bi-chevron-left" aria-hidden="true"></i></button>';
                const nextButton = '<button type="button" class="menu-page-arrow" data-page="' + (page + 1) + '" aria-label="Next page" title="Next page"' + (page === pages ? ' disabled' : '') + '><i class="bi bi-chevron-right" aria-hidden="true"></i></button>';
                pager.innerHTML = hasPagination ? previousButton + pageNumbers.map(function (number, index) { const previous = pageNumbers[index - 1]; const gap = previous && number - previous > 1 ? '<span aria-hidden="true">...</span>' : ''; return gap + '<button type="button" class="' + (number === page ? 'active' : '') + '" data-page="' + number + '"' + (number === page ? ' aria-current="page"' : '') + '>' + number + '</button>'; }).join('') + nextButton : '';
            }
            categoryFilter.addEventListener('change', function () { category = categoryFilter.value; page = 1; render(); });
            search.addEventListener('input', function () { query = search.value.trim().toLowerCase(); page = 1; render(); });
            reset.addEventListener('click', function () { category = 'all'; query = ''; search.value = ''; page = 1; render(); search.focus(); });
            pager.addEventListener('click', function (event) { const button = event.target.closest('[data-page]'); if (button && !button.disabled) { page = Number(button.dataset.page); render(); } });
            render();
        }());
    </script>
<?php include('includes/public-footer.php'); ?>
