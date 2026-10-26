<?php
require_once __DIR__ . '/includes/bootstrap.php'; adminRequireLogin();
$menuTab = $_GET['tab'] ?? 'items';
if (!in_array($menuTab, array('categories', 'items'), true)) $menuTab = 'items';
$itemStatus = $_GET['item_status'] ?? 'all';
if (!in_array($itemStatus, array('all', 'active', 'inactive'), true)) $itemStatus = 'all';
$categoryStatus = $_GET['status'] ?? 'all';
if (!in_array($categoryStatus, array('all', 'active', 'inactive'), true)) $categoryStatus = 'all';
$categoryError = '';
$conn = adminDb();
adminEnsureFoodStatuses();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!adminVerifyPost()) adminRedirect('menu.php?tab=' . $menuTab, 'Request expired.');
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0 && isset($_POST['delete'])) adminRedirect('menu.php?tab=items', runQuery('DELETE FROM foods WHERE id=?', 'i', $id) ? 'Menu item deleted.' : 'Could not delete item.');
    if (isset($_POST['toggle_food_status'])) {
        $foodId = (int) ($_POST['food_id'] ?? 0);
        $food = $conn && $foodId > 0 ? mysqli_fetch_assoc(mysqli_query($conn, 'SELECT id,name,is_active FROM foods WHERE id=' . $foodId)) : null;
        if (!$food) adminRedirect('menu.php?tab=items', 'Menu item not found.');
        $nextStatus = $food['is_active'] ? 0 : 1;
        $ok = runQuery('UPDATE foods SET is_active=? WHERE id=?', 'ii', $nextStatus, $foodId);
        adminRedirect('menu.php?tab=items&item_status=' . rawurlencode($itemStatus), $ok ? ($nextStatus ? 'Menu item activated.' : 'Menu item paused. It is now hidden from the public menu.') : 'Could not update the menu item status.');
    }
    if (isset($_POST['add_category'])) {
        adminCategories();
        $name = trim($_POST['category_name'] ?? '');
        if ($name === '' || strlen($name) > 50 || preg_match('/[\x00-\x1F]/', $name)) $categoryError = 'Enter a category name under 50 characters.';
        elseif (runQuery('INSERT INTO food_categories (name) VALUES (?)', 's', $name)) adminRedirect('menu.php?tab=categories', 'Category added.');
        else $categoryError = 'That category already exists or could not be saved.';
    }
    if (isset($_POST['update_category'])) {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $name = trim($_POST['category_name'] ?? '');
        $old = $conn && $categoryId > 0 ? mysqli_fetch_assoc(mysqli_query($conn, 'SELECT category_id,name FROM food_categories WHERE category_id=' . $categoryId)) : null;
        if (!$old) $categoryError = 'Category not found.';
        elseif ($name === '' || strlen($name) > 50 || preg_match('/[\x00-\x1F]/', $name)) $categoryError = 'Enter a category name under 50 characters.';
        elseif (!mysqli_begin_transaction($conn)) $categoryError = 'Could not start category update.';
        else {
            $updateCategory = mysqli_prepare($conn, 'UPDATE food_categories SET name=? WHERE category_id=?');
            $updateFoods = mysqli_prepare($conn, 'UPDATE foods SET category=? WHERE category=?');
            $galleryColumn = mysqli_query($conn, "SHOW COLUMNS FROM site_gallery_images LIKE 'category_name'");
            $updateGallery = $galleryColumn && mysqli_num_rows($galleryColumn) ? mysqli_prepare($conn, 'UPDATE site_gallery_images SET category_name=? WHERE category_name=?') : null;
            $oldName = $old['name'];
            $ok = $updateCategory && $updateFoods && mysqli_stmt_bind_param($updateCategory, 'si', $name, $categoryId) && mysqli_stmt_execute($updateCategory) && mysqli_stmt_bind_param($updateFoods, 'ss', $name, $oldName) && mysqli_stmt_execute($updateFoods);
            if ($ok && $updateGallery) $ok = mysqli_stmt_bind_param($updateGallery, 'ss', $name, $oldName) && mysqli_stmt_execute($updateGallery);
            if ($ok && mysqli_commit($conn)) adminRedirect('menu.php?tab=categories', 'Category updated.');
            mysqli_rollback($conn);
            $categoryError = 'Could not update the category. Category names must be unique.';
        }
    }
    if (isset($_POST['toggle_category_status'])) {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $category = $conn && $categoryId > 0 ? mysqli_fetch_assoc(mysqli_query($conn, 'SELECT category_id,name,is_active FROM food_categories WHERE category_id=' . $categoryId)) : null;
        if (!$category) adminRedirect('menu.php?tab=categories&status=' . rawurlencode($categoryStatus), 'Category not found.');
        $nextStatus = $category['is_active'] ? 0 : 1;
        $ok = runQuery('UPDATE food_categories SET is_active=? WHERE category_id=?', 'ii', $nextStatus, $categoryId);
        $galleryColumn = $conn ? mysqli_query($conn, "SHOW COLUMNS FROM site_gallery_images LIKE 'is_auto_hidden'") : false;
        if ($ok && $galleryColumn && mysqli_num_rows($galleryColumn)) $ok = runQuery('UPDATE site_gallery_images SET is_auto_hidden=? WHERE category_name=?', 'is', $nextStatus ? 0 : 1, $category['name']);
        adminRedirect('menu.php?tab=categories&status=' . rawurlencode($categoryStatus), $ok ? ($nextStatus ? 'Category activated.' : 'Category paused. It is now hidden from the public menu.') : 'Could not update category status.');
    }
    if (isset($_POST['delete_category'])) {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $category = $conn && $categoryId > 0 ? mysqli_fetch_assoc(mysqli_query($conn, 'SELECT category_id,name FROM food_categories WHERE category_id=' . $categoryId)) : null;
        if (!$category) adminRedirect('menu.php?tab=categories', 'Category not found.');
        $foodCount = 0;
        $foodCheck = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM foods WHERE category=?');
        if ($foodCheck) { mysqli_stmt_bind_param($foodCheck, 's', $category['name']); mysqli_stmt_execute($foodCheck); $foodCount = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($foodCheck))['total'] ?? 0); }
        $targetCount = 0;
        $targetTable = mysqli_query($conn, "SHOW TABLES LIKE 'offer_food_categories'");
        if ($targetTable && mysqli_num_rows($targetTable)) { $targetCheck = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM offer_food_categories WHERE category_id=?'); if ($targetCheck) { mysqli_stmt_bind_param($targetCheck, 'i', $categoryId); mysqli_stmt_execute($targetCheck); $targetCount = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($targetCheck))['total'] ?? 0); } }
        $galleryCount = 0;
        $galleryColumn = mysqli_query($conn, "SHOW COLUMNS FROM site_gallery_images LIKE 'category_name'");
        if ($galleryColumn && mysqli_num_rows($galleryColumn)) { $galleryCheck = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM site_gallery_images WHERE category_name=?'); if ($galleryCheck) { mysqli_stmt_bind_param($galleryCheck, 's', $category['name']); mysqli_stmt_execute($galleryCheck); $galleryCount = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($galleryCheck))['total'] ?? 0); } }
        if ($foodCount || $targetCount || $galleryCount) adminRedirect('menu.php?tab=categories', 'This category is still used by menu items, offers, or gallery images and cannot be deleted.');
        adminRedirect('menu.php?tab=categories', runQuery('DELETE FROM food_categories WHERE category_id=?', 'i', $categoryId) ? 'Category deleted.' : 'Could not delete category.');
    }
}
$categories = adminCategories();
$categoryCounts = array('all'=>count($categories), 'active'=>0, 'inactive'=>0);
foreach ($categories as $category) $categoryCounts[$category['is_active'] ? 'active' : 'inactive']++;
$visibleCategories = array_values(array_filter($categories, function ($category) use ($categoryStatus) { return $categoryStatus === 'all' || ($categoryStatus === 'active' ? $category['is_active'] : !$category['is_active']); }));
$foods = adminRows('SELECT id, name, description, price, picture, category, is_active FROM foods ORDER BY id DESC');
$foodCounts = array('all'=>count($foods), 'active'=>0, 'inactive'=>0);
foreach ($foods as $food) $foodCounts[$food['is_active'] ? 'active' : 'inactive']++;
$visibleFoods = array_values(array_filter($foods, function ($food) use ($itemStatus) { return $itemStatus === 'all' || ($itemStatus === 'active' ? $food['is_active'] : !$food['is_active']); }));
$editingCategory = null;
$editingCategoryId = (int) ($_GET['edit_category'] ?? ($_POST['category_id'] ?? 0));
foreach ($categories as $category) if ((int) $category['category_id'] === $editingCategoryId) { $editingCategory = $category; break; }
$adminTitle = 'Menu'; $adminPage = 'menu'; include __DIR__ . '/includes/header.php';
?>
<nav class="admin-menu-tabs" aria-label="Menu sections"><a href="menu.php?tab=items"<?php echo $menuTab === 'items' ? ' aria-current="page"' : ''; ?>><i class="bi bi-list-ul" aria-hidden="true"></i><span>Food items</span><b><?php echo count($foods); ?></b></a><a href="menu.php?tab=categories"<?php echo $menuTab === 'categories' ? ' aria-current="page"' : ''; ?>><i class="bi bi-tags" aria-hidden="true"></i><span>Categories</span><b><?php echo count($categories); ?></b></a></nav>
<?php if ($menuTab === 'categories') { include __DIR__ . '/includes/category-manager.php'; } else { include __DIR__ . '/includes/menu-items-manager.php'; } ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
