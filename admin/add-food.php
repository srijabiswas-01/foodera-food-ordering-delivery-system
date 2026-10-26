<?php
require_once __DIR__ . '/includes/bootstrap.php';
adminRequireLogin();
$conn = adminDb();
$id = max(0, (int) ($_GET['edit'] ?? $_POST['id'] ?? 0));
$item = null;
if ($conn && $id) {
    $stmt = mysqli_prepare($conn, 'SELECT * FROM foods WHERE id=?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}
$categories = adminCategories();
$categoryNames = array_column($categories, 'name');
$errors = array();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!adminVerifyPost()) $errors['form'] = 'Request expired. Refresh and try again.';
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
    if ($name === '' || strlen($name) > 1000) $errors['name'] = 'Enter an item name.';
    if ($description === '') $errors['description'] = 'Enter a description.';
    if (!in_array($category, $categoryNames, true)) $errors['category'] = 'Choose a category from the list.';
    if ($price === false || $price < 1) $errors['price'] = 'Enter a price of at least Rs 1.';
    if (!$conn) $errors['form'] = 'Database unavailable.';
    if ($id && !$item) $errors['form'] = 'Item not found.';
    $file = $_FILES['picture'] ?? null;
    $hasNewImage = $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if (!$item && !$hasNewImage) $errors['picture'] = 'Choose an image for this menu item.';
    if ($hasNewImage) {
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 4000000 || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) $errors['picture'] = 'Choose a JPG, PNG, or WebP image under 4 MB.';
        else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $extensions = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp');
            if (!isset($extensions[$mime]) || !@getimagesize($file['tmp_name'])) $errors['picture'] = 'Use a valid JPG, PNG, or WebP image.';
        }
    }
    if (!$errors) {
        $picture = $item['picture'] ?? '';
        $uploadedPath = '';
        if ($hasNewImage) {
            $directory = __DIR__ . '/../uploads/foods';
            if (!is_dir($directory) && !mkdir($directory, 0775, true)) $errors['picture'] = 'Could not prepare image storage.';
            else {
                $uploadedPath = 'uploads/foods/' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../' . $uploadedPath)) $errors['picture'] = 'Image upload failed.';
                else $picture = $uploadedPath;
            }
        }
        if (!$errors) {
            if ($item) $saved = runQuery('UPDATE foods SET name=?,description=?,price=?,picture=?,category=? WHERE id=?', 'ssdssi', $name, $description, $price, $picture, $category, $id);
            else $saved = runQuery('INSERT INTO foods (name,description,price,picture,category) VALUES (?,?,?,?,?)', 'ssdss', $name, $description, $price, $picture, $category);
            if ($saved) {
                if ($uploadedPath !== '' && $item && str_starts_with((string) $item['picture'], 'uploads/foods/')) @unlink(__DIR__ . '/../' . $item['picture']);
                adminRedirect('menu.php', 'Menu item saved.');
            }
            if ($uploadedPath !== '') @unlink(__DIR__ . '/../' . $uploadedPath);
            $errors['form'] = 'Could not save the item. Try again.';
        }
    }
}
$adminTitle = $item ? 'Edit menu item' : 'Add menu item';
$adminPage = 'menu';
include __DIR__ . '/includes/header.php';
?>
<?php if (isset($errors['form'])) { ?><p class="admin-notice" role="alert"><?php echo adminEscape($errors['form']); ?></p><?php } ?>
<section class="admin-card">
    <?php if (!$categories) { ?><p class="admin-notice">Add a category on the <a href="menu.php">Menu page</a> before adding an item.</p><?php } ?>
    <form method="post" enctype="multipart/form-data" class="admin-form admin-menu-editor" data-inline-validate>
        <input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>">
        <input type="hidden" name="id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
        <aside class="admin-menu-preview" aria-label="Customer menu card preview"><p>Customer preview</p><article><div class="admin-menu-preview-image"><i class="bi bi-image" data-preview-placeholder aria-hidden="true"<?php echo $item && !empty($item['picture']) ? ' hidden' : ''; ?>></i><?php if ($item && !empty($item['picture'])) { ?><img id="food-image-preview" src="../<?php echo adminEscape($item['picture']); ?>" alt="Current image for <?php echo adminEscape($item['name']); ?>"><?php } else { ?><img id="food-image-preview" alt="Selected menu image preview" hidden><?php } ?></div><div class="admin-menu-preview-copy"><span id="menu-preview-category"><?php echo adminEscape($_POST['category'] ?? $item['category'] ?? 'Category'); ?></span><h2 id="menu-preview-name"><?php echo adminEscape($_POST['name'] ?? $item['name'] ?? 'Menu item name'); ?></h2><p id="menu-preview-description"><?php echo adminEscape($_POST['description'] ?? $item['description'] ?? 'A short description of the dish will appear here.'); ?></p><strong id="menu-preview-price">Rs <?php echo adminEscape($_POST['price'] ?? $item['price'] ?? '0'); ?></strong><button type="button" disabled>Add to cart</button></div></article></aside>
        <div class="admin-menu-fields"><label for="food-name">Name<input id="food-name" name="name" maxlength="1000" value="<?php echo adminEscape($_POST['name'] ?? $item['name'] ?? ''); ?>" data-preview-text="menu-preview-name" required><small class="field-error" aria-live="polite"><?php echo adminEscape($errors['name'] ?? ''); ?></small></label><label for="food-description">Description<textarea id="food-description" name="description" rows="5" data-preview-text="menu-preview-description" required><?php echo adminEscape($_POST['description'] ?? $item['description'] ?? ''); ?></textarea><small class="field-error" aria-live="polite"><?php echo adminEscape($errors['description'] ?? ''); ?></small></label><?php $selectedCategory = $_POST['category'] ?? $item['category'] ?? ''; ?><label for="food-category-search">Category<div class="admin-search-select" data-category-combobox><div class="admin-search-select-input"><i class="bi bi-search" aria-hidden="true"></i><input id="food-category-search" type="text" value="<?php echo adminEscape($selectedCategory); ?>" placeholder="Search and select a category" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="food-category-options" required><button type="button" class="admin-search-select-clear" aria-label="Clear selected category" title="Clear selected category"<?php echo $selectedCategory === '' ? ' hidden' : ''; ?>><i class="bi bi-x-lg" aria-hidden="true"></i></button><i class="bi bi-chevron-down" aria-hidden="true"></i></div><input id="food-category" type="hidden" name="category" value="<?php echo adminEscape($selectedCategory); ?>"><div id="food-category-options" class="admin-search-select-options" role="listbox" hidden><?php foreach ($categories as $option) { ?><button type="button" role="option" aria-selected="<?php echo $selectedCategory === $option['name'] ? 'true' : 'false'; ?>" data-category-option data-value="<?php echo adminEscape($option['name']); ?>"><?php echo adminEscape($option['name']); ?></button><?php } ?><p class="admin-search-select-empty" hidden>No matching categories.</p></div></div><small class="field-error" aria-live="polite"><?php echo adminEscape($errors['category'] ?? ''); ?></small></label><label for="food-price">Regular price (Rs)<input id="food-price" type="number" min="1" step="1" name="price" value="<?php echo adminEscape($_POST['price'] ?? $item['price'] ?? ''); ?>" data-preview-price="menu-preview-price" required><small class="field-error" aria-live="polite"><?php echo adminEscape($errors['price'] ?? ''); ?></small></label><label for="food-picture">Menu image<input id="food-picture" type="file" name="picture" accept="image/jpeg,image/png,image/webp" data-preview-target="food-image-preview" data-preview-placeholder="true" <?php echo $item ? '' : 'required'; ?>><small class="field-error" aria-live="polite"><?php echo adminEscape($errors['picture'] ?? ''); ?></small></label><p class="admin-muted">Discounts are created and managed from <a href="offers.php">Offers</a>. The cart applies eligible offers automatically.</p><div class="admin-actions"><button class="admin-btn" type="submit" <?php echo $categories ? '' : 'disabled'; ?>><i class="bi bi-floppy" aria-hidden="true"></i> Save item</button><a class="admin-btn secondary" href="menu.php">Cancel</a></div></div>
    </form>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
