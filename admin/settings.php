<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/../includes/site-content.php';
adminRequireLogin();
$conn = adminDb();
$fields = siteContentFields();
$settingsTable = $conn ? mysqli_query($conn, "SHOW TABLES LIKE 'site_settings'") : false;
$galleryTable = $conn ? mysqli_query($conn, "SHOW TABLES LIKE 'site_gallery_images'") : false;
$ready = $settingsTable && mysqli_num_rows($settingsTable) && $galleryTable && mysqli_num_rows($galleryTable);
if ($ready && $conn) {
    $galleryCategoryColumn = mysqli_query($conn, "SHOW COLUMNS FROM site_gallery_images LIKE 'category_name'");
    if (!$galleryCategoryColumn || mysqli_num_rows($galleryCategoryColumn) === 0) @mysqli_query($conn, 'ALTER TABLE site_gallery_images ADD COLUMN category_name VARCHAR(50) NOT NULL DEFAULT \'\' AFTER caption');
    $galleryAutoHiddenColumn = mysqli_query($conn, "SHOW COLUMNS FROM site_gallery_images LIKE 'is_auto_hidden'");
    if (!$galleryAutoHiddenColumn || mysqli_num_rows($galleryAutoHiddenColumn) === 0) @mysqli_query($conn, 'ALTER TABLE site_gallery_images ADD COLUMN is_auto_hidden TINYINT(1) NOT NULL DEFAULT 0 AFTER is_visible');
}
$menuCategories = adminCategories();
$menuCategoryNames = array_column($menuCategories, 'name');
$menuCategoryStatuses = array_column($menuCategories, 'is_active', 'name');
$settingsTabs = array('branding'=>'Branding','appearance'=>'Appearance','home'=>'Home','about'=>'About','contact'=>'Contact','blog'=>'Blog','shared'=>'Shared','gallery'=>'Gallery');
$activeTab = $_GET['tab'] ?? 'branding';
if (!isset($settingsTabs[$activeTab])) $activeTab = 'branding';
$themePresets = array(
    'foodera' => array('Foodera Classic', 'The signature Foodera orange with clean cream surfaces and charcoal contrast.', '#ff3d00', '#f7f7f5', '#2f86a6', '#ff5a2c', '#20242b', '#66c7df'),
    'spice' => array('Citrus Terrace', 'Foodera orange stays primary, balanced with citrus cream, olive detail, and a forest dark mode.', '#ff3d00', '#fffbea', '#647b3a', '#ff3d00', '#1f2a22', '#b7cf74'),
    'copper' => array('Harbor Slate', 'Foodera orange over crisp harbour-blue surfaces with cobalt details and a deep navy dark mode.', '#ff3d00', '#eaf2fb', '#245a9b', '#ff3d00', '#101c32', '#79b8e8'),
    'night' => array('Ember Rose', 'Foodera orange with soft rose surfaces, berry contrast, and a deep plum dark mode.', '#ff3d00', '#fff4f4', '#9d3d55', '#ff3d00', '#2a1d25', '#e69aaa'),
);

function adminSettingsUrl($tab)
{
    return 'settings.php?tab=' . rawurlencode($tab) . '#' . rawurlencode($tab);
}

function adminSiteUpload($file)
{
    if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) return array(null, 'Choose a JPG, PNG, or WebP image under 4 MB.');
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE || ($file['size'] ?? 0) > 8000000) return array(null, 'The image must be smaller than 8 MB.');
    if ($file['error'] !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return array(null, 'The image upload was interrupted. Please choose the file again.');
    if ($file['size'] <= 0) return array(null, 'The selected image is empty.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = array('image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp');
    if (!isset($extensions[$mime]) || !@getimagesize($file['tmp_name'])) return array(null, 'Use a valid JPG, PNG, or WebP image.');
    $dir = __DIR__ . '/../uploads/site';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) return array(null, 'Image storage could not be created. Check uploads/site permissions.');
    if (!is_writable($dir)) return array(null, 'Image storage is not writable. Check uploads/site permissions.');
    $path = 'uploads/site/' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../' . $path)) return array(null, 'Image could not be stored. Check uploads/site permissions.');
    return array($path, '');
}

function adminSiteDeleteUnused($conn, $path)
{
    if (!preg_match('~^uploads/site/[a-f0-9]{32}\.(jpg|png|webp)$~', (string) $path)) return;
    $stmt = mysqli_prepare($conn, 'SELECT (SELECT COUNT(*) FROM site_settings WHERE setting_value=?) + (SELECT COUNT(*) FROM site_gallery_images WHERE image_path=?) AS uses');
    if (!$stmt) return;
    mysqli_stmt_bind_param($stmt, 'ss', $path, $path);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($row && (int) $row['uses'] === 0) @unlink(__DIR__ . '/../' . $path);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedTab = $_POST['tab'] ?? 'branding';
    if (!isset($settingsTabs[$postedTab])) $postedTab = 'branding';
    if (!adminVerifyPost()) adminRedirect('settings.php', 'Request expired. Refresh and try again.');
    if (!$ready) adminRedirect('settings.php', 'Run db/site_content_migration.sql before editing site content.');
    $action = $_POST['action'] ?? '';
    if ($action === 'apply_theme_preset') {
        $preset = $_POST['preset'] ?? '';
        if (!isset($themePresets[$preset])) adminRedirect(adminSettingsUrl('appearance'), 'Choose a valid theme preset.');
        $colors = $themePresets[$preset];
        $updates = array('theme.light_primary'=>$colors[2], 'theme.light_background'=>$colors[3], 'theme.light_accent'=>$colors[4], 'theme.dark_primary'=>$colors[5], 'theme.dark_background'=>$colors[6], 'theme.dark_accent'=>$colors[7], 'theme.active_preset'=>$preset);
        mysqli_begin_transaction($conn);
        $save = mysqli_prepare($conn, 'INSERT INTO site_settings(setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
        $ok = (bool) $save;
        foreach ($updates as $key => $value) { if (!$ok) break; mysqli_stmt_bind_param($save, 'ss', $key, $value); $ok = mysqli_stmt_execute($save); }
        if ($ok && mysqli_commit($conn)) adminRedirect(adminSettingsUrl('appearance'), $colors[0] . ' preset activated.');
        mysqli_rollback($conn);
        adminRedirect(adminSettingsUrl('appearance'), 'Could not activate the preset.');
    }
    if ($action === 'save_text') {
        $group = $_POST['group'] ?? '';
        if (!isset($fields[$group])) adminRedirect('settings.php', 'Unknown content group.');
        $submitted = $_POST['values'] ?? array();
        $updates = array();
        foreach ($fields[$group] as $key => $field) {
            if ($field[1] === 'image') continue;
            $value = trim((string) ($submitted[$key] ?? ''));
            $limit = $field[1] === 'textarea' ? 1200 : 180;
            if ($field[1] === 'color' && !preg_match('/^#[0-9a-fA-F]{6}$/', $value)) adminRedirect(adminSettingsUrl($group), 'Choose a valid color for ' . $field[0] . '.');
            if (strlen($value) > $limit || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value)) adminRedirect(adminSettingsUrl($group), $field[0] . ' is too long or contains invalid characters.');
            $updates[$key] = $value;
        }
        if ($group === 'appearance') $updates['theme.active_preset'] = 'custom';
        mysqli_begin_transaction($conn);
        $save = mysqli_prepare($conn, 'INSERT INTO site_settings(setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
        $delete = mysqli_prepare($conn, 'DELETE FROM site_settings WHERE setting_key=?');
        $ok = $save && $delete;
        if ($ok) foreach ($updates as $key => $value) {
            if ($value === '') { mysqli_stmt_bind_param($delete, 's', $key); $ok = mysqli_stmt_execute($delete); }
            else { mysqli_stmt_bind_param($save, 'ss', $key, $value); $ok = mysqli_stmt_execute($save); }
            if (!$ok) break;
        }
        if ($ok && mysqli_commit($conn)) adminRedirect(adminSettingsUrl($group), 'Content saved.');
        mysqli_rollback($conn);
        adminRedirect(adminSettingsUrl($group), 'Could not save content.');
    }
    if ($action === 'replace_image' || $action === 'reset_image') {
        $key = $_POST['key'] ?? '';
        $field = siteContentField($key);
        if (!$field || $field[1] !== 'image') adminRedirect(adminSettingsUrl($postedTab), 'Unknown image slot.');
        $old = siteSetting($key);
        $group = explode('.', $key, 2)[0];
        if ($action === 'reset_image') {
            $ok = runQuery('DELETE FROM site_settings WHERE setting_key=?', 's', $key);
            if ($ok) adminSiteDeleteUnused($conn, $old);
            adminRedirect(adminSettingsUrl($group), $ok ? 'Image reset to the original.' : 'Could not reset image.');
        }
        list($path, $error) = adminSiteUpload($_FILES['image_file'] ?? null);
        if ($error) adminRedirect(adminSettingsUrl($group), $error);
        $ok = runQuery('INSERT INTO site_settings(setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)', 'ss', $key, $path);
        if (!$ok) @unlink(__DIR__ . '/../' . $path);
        else adminSiteDeleteUnused($conn, $old);
        adminRedirect(adminSettingsUrl($group), $ok ? 'Image updated on the public site.' : 'Could not save image.');
    }
    if ($action === 'add_gallery') {
        $caption = trim($_POST['caption'] ?? '');
        $categoryName = trim($_POST['category_name'] ?? '');
        $sort = max(0, min(999, (int) ($_POST['sort_order'] ?? 0)));
        if (strlen($caption) > 120) adminRedirect(adminSettingsUrl('gallery'), 'Caption must be under 120 characters.');
        if ($categoryName !== '' && !in_array($categoryName, $menuCategoryNames, true)) adminRedirect(adminSettingsUrl('gallery'), 'Choose a valid menu category.');
        list($path, $error) = adminSiteUpload($_FILES['image_file'] ?? null);
        if ($error) adminRedirect(adminSettingsUrl('gallery'), $error);
        $autoHidden = $categoryName !== '' && empty($menuCategoryStatuses[$categoryName]) ? 1 : 0;
        $ok = runQuery('INSERT INTO site_gallery_images(image_path,caption,category_name,sort_order,is_visible,is_auto_hidden) VALUES (?,?,?,?,1,?)', 'sssii', $path, $caption, $categoryName, $sort, $autoHidden);
        if (!$ok) @unlink(__DIR__ . '/../' . $path);
        adminRedirect(adminSettingsUrl('gallery'), $ok ? 'Gallery image added.' : 'Could not add image.');
    }
    if ($action === 'toggle_gallery_visibility') {
        $id = max(0, (int) ($_POST['image_id'] ?? 0));
        $existing = $id ? adminRows('SELECT image_id,is_visible FROM site_gallery_images WHERE image_id=' . $id . ' LIMIT 1') : array();
        if (!$existing) adminRedirect(adminSettingsUrl('gallery'), 'Image not found.');
        $nextVisible = $existing[0]['is_visible'] ? 0 : 1;
        $ok = runQuery('UPDATE site_gallery_images SET is_visible=? WHERE image_id=?', 'ii', $nextVisible, $id);
        adminRedirect(adminSettingsUrl('gallery'), $ok ? ($nextVisible ? 'Gallery image made visible.' : 'Gallery image hidden.') : 'Could not update gallery visibility.');
    }
    if ($action === 'save_gallery' || $action === 'delete_gallery') {
        $id = max(0, (int) ($_POST['image_id'] ?? 0));
        $stmt = mysqli_prepare($conn, 'SELECT image_path FROM site_gallery_images WHERE image_id=?');
        if (!$stmt) adminRedirect(adminSettingsUrl('gallery'), 'Gallery is unavailable.');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$existing) adminRedirect(adminSettingsUrl('gallery'), 'Image not found.');
        if ($action === 'delete_gallery') {
            $ok = runQuery('DELETE FROM site_gallery_images WHERE image_id=?', 'i', $id);
            if ($ok) adminSiteDeleteUnused($conn, $existing['image_path']);
            adminRedirect(adminSettingsUrl('gallery'), $ok ? 'Gallery image deleted.' : 'Could not delete image.');
        }
        $caption = trim($_POST['caption'] ?? '');
        $categoryName = trim($_POST['category_name'] ?? '');
        $sort = max(0, min(999, (int) ($_POST['sort_order'] ?? 0)));
        $visible = isset($_POST['is_visible']) ? 1 : 0;
        if (strlen($caption) > 120) adminRedirect(adminSettingsUrl('gallery'), 'Caption must be under 120 characters.');
        if ($categoryName !== '' && !in_array($categoryName, $menuCategoryNames, true)) adminRedirect(adminSettingsUrl('gallery'), 'Choose a valid menu category.');
        $path = $existing['image_path'];
        $uploaded = false;
        if (!empty($_FILES['image_file']['name'])) {
            list($path, $error) = adminSiteUpload($_FILES['image_file']);
            if ($error) adminRedirect(adminSettingsUrl('gallery'), $error);
            $uploaded = true;
        }
        $autoHidden = $categoryName !== '' && empty($menuCategoryStatuses[$categoryName]) ? 1 : 0;
        $ok = runQuery('UPDATE site_gallery_images SET image_path=?,caption=?,category_name=?,sort_order=?,is_visible=?,is_auto_hidden=? WHERE image_id=?', 'sssiiii', $path, $caption, $categoryName, $sort, $visible, $autoHidden, $id);
        if ($uploaded) {
            if ($ok) adminSiteDeleteUnused($conn, $existing['image_path']);
            else @unlink(__DIR__ . '/../' . $path);
        }
        adminRedirect(adminSettingsUrl('gallery'), $ok ? 'Gallery image updated.' : 'Could not save gallery image.');
    }
    adminRedirect('settings.php', 'Unknown action.');
}

$gallery = $ready ? adminRows('SELECT * FROM site_gallery_images ORDER BY sort_order,image_id') : array();
$galleryCounts = array('all'=>count($gallery), 'visible'=>0, 'hidden'=>0, 'automatic'=>0);
foreach ($gallery as $image) {
    $publiclyVisible = (int) $image['is_visible'] === 1 && (int) ($image['is_auto_hidden'] ?? 0) === 0;
    $galleryCounts[$publiclyVisible ? 'visible' : 'hidden']++;
    if (!empty($image['is_auto_hidden'])) $galleryCounts['automatic']++;
}
$activePreset = '';
if ($ready && $conn) {
    $presetSetting = mysqli_prepare($conn, "SELECT setting_value FROM site_settings WHERE setting_key='theme.active_preset' LIMIT 1");
    if ($presetSetting && mysqli_stmt_execute($presetSetting)) $activePreset = (string) (mysqli_fetch_assoc(mysqli_stmt_get_result($presetSetting))['setting_value'] ?? '');
}
$adminTitle = 'Site settings'; $adminPage = 'settings';
include __DIR__ . '/includes/header.php';
?>
<?php if (!$ready) { ?><p class="admin-notice" role="alert">Run <code>db/site_content_migration.sql</code> on this database to enable site settings.</p><?php } else { ?>
<nav class="admin-settings-tabs" aria-label="Settings sections">
    <?php foreach ($settingsTabs as $section => $label) { ?><a href="<?php echo adminEscape(adminSettingsUrl($section)); ?>"<?php echo $section === $activeTab ? ' aria-current="page"' : ''; ?>><?php echo $label; ?></a><?php } ?>
</nav>
<?php foreach ($fields as $group => $entries) { if ($group !== $activeTab) continue; $publicPage = array('branding'=>'home.php','appearance'=>'home.php','home'=>'home.php','about'=>'about-us.php','contact'=>'contact-us.php','blog'=>'blog.php','shared'=>'home.php')[$group]; ?>
<section class="admin-settings-section" id="<?php echo $group; ?>">
    <div class="admin-toolbar"><div><h2><?php echo ucfirst($group); ?></h2><p class="admin-muted">Updates here appear on the public site after saving.</p></div><a href="../<?php echo $publicPage; ?>" target="_blank" rel="noopener">View page <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a></div>
    <?php if ($group === 'appearance') { ?><div class="theme-preset-heading"><div><h3>Theme presets</h3><p>Each preset changes the public site’s light and dark palette. The selected preset is marked below.</p></div></div><div class="theme-preset-grid"><?php foreach ($themePresets as $presetKey => $preset) { $isActivePreset = $presetKey === $activePreset; ?><article class="theme-preset-card<?php echo $isActivePreset ? ' is-active' : ''; ?>"><div class="theme-preset-preview" style="--light-bg:<?php echo adminEscape($preset[3]); ?>;--light-primary:<?php echo adminEscape($preset[2]); ?>;--light-accent:<?php echo adminEscape($preset[4]); ?>;--dark-bg:<?php echo adminEscape($preset[6]); ?>;--dark-primary:<?php echo adminEscape($preset[5]); ?>;--dark-accent:<?php echo adminEscape($preset[7]); ?>"><div class="theme-preview-light"><span>Foodera</span><b></b><i></i></div><div class="theme-preview-dark"><span>Foodera</span><b></b><i></i></div></div><h3><?php echo adminEscape($preset[0]); ?></h3><p><?php echo adminEscape($preset[1]); ?></p><form method="post"><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="tab" value="appearance"><input type="hidden" name="action" value="apply_theme_preset"><input type="hidden" name="preset" value="<?php echo adminEscape($presetKey); ?>"><button class="admin-btn<?php echo $isActivePreset ? ' secondary' : ''; ?>" type="submit"<?php echo $isActivePreset ? ' disabled' : ''; ?>><i class="bi <?php echo $isActivePreset ? 'bi-check2-circle' : 'bi-palette'; ?>" aria-hidden="true"></i> <?php echo $isActivePreset ? 'Active preset' : 'Use this preset'; ?></button></form></article><?php } ?></div><?php } ?>
    <?php $textEntries = array_filter($entries, function ($field) { return $field[1] !== 'image'; }); ?>
    <?php if ($textEntries) { ?><form method="post" class="admin-form admin-settings-form"><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="tab" value="<?php echo $group; ?>"><input type="hidden" name="action" value="save_text"><input type="hidden" name="group" value="<?php echo $group; ?>">
        <?php foreach ($textEntries as $key => $field) { ?><label><?php echo adminEscape($field[0]); ?><?php if ($field[1] === 'textarea') { ?><textarea name="values[<?php echo adminEscape($key); ?>]" maxlength="1200" rows="3"><?php echo adminEscape(siteSetting($key)); ?></textarea><?php } else { ?><input type="<?php echo $field[1] === 'color' ? 'color' : 'text'; ?>" name="values[<?php echo adminEscape($key); ?>]" maxlength="180" value="<?php echo adminEscape(siteSetting($key)); ?>"><?php } ?></label><?php } ?>
        <button class="admin-btn" type="submit"><i class="bi bi-floppy" aria-hidden="true"></i> <?php echo $group === 'appearance' ? 'Save custom theme' : 'Save ' . $group . ' changes'; ?></button>
    </form><?php } ?>
    <?php foreach ($entries as $key => $field) { if ($field[1] !== 'image') continue; ?>
        <div class="admin-media-row"><?php $imageSource = siteImage($key); ?><?php if ($imageSource && (str_starts_with($imageSource, 'uploads/site/') || is_file(__DIR__ . '/../' . $imageSource))) { ?><img src="../<?php echo adminEscape($imageSource); ?>" alt="<?php echo adminEscape($field[0]); ?>"><?php } else { ?><div class="admin-media-placeholder"><i class="bi bi-image" aria-hidden="true"></i><span>No image uploaded</span></div><?php } ?><div><h3><?php echo adminEscape($field[0]); ?></h3><form method="post" enctype="multipart/form-data" class="admin-form admin-media-form"><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="tab" value="<?php echo $group; ?>"><input type="hidden" name="action" value="replace_image"><input type="hidden" name="key" value="<?php echo adminEscape($key); ?>"><input type="hidden" name="MAX_FILE_SIZE" value="8000000"><label>Replace image<input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" required></label><button class="admin-btn" type="submit"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Replace</button></form><form method="post" class="admin-inline" onsubmit="return confirm('Restore the original image?')"><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="tab" value="<?php echo $group; ?>"><input type="hidden" name="action" value="reset_image"><input type="hidden" name="key" value="<?php echo adminEscape($key); ?>"><button class="admin-btn secondary" type="submit"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Restore original</button></form></div></div>
    <?php } ?>
</section>
<?php } ?>
<?php if ($activeTab === 'gallery') { ?><section class="admin-settings-section" id="gallery">
    <div class="admin-toolbar"><div><h2>Gallery</h2><p class="admin-muted">Linked images open the matching category on the public menu and can supply its menu-card image.</p></div><a href="../gallery.php" target="_blank" rel="noopener">View gallery <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a></div>
    <form method="post" enctype="multipart/form-data" class="admin-form admin-gallery-add"><h3>Add image</h3><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="tab" value="gallery"><input type="hidden" name="action" value="add_gallery"><label>Image<input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" required></label><label>Caption<input name="caption" maxlength="120" placeholder="Describe the image"></label><label>Menu category<select name="category_name"><option value="">No menu link</option><?php foreach ($menuCategories as $category) { ?><option value="<?php echo adminEscape($category['name']); ?>"><?php echo adminEscape($category['name']); ?><?php echo $category['is_active'] ? '' : ' (paused)'; ?></option><?php } ?></select></label><label>Order<input type="number" name="sort_order" min="0" max="999" value="<?php echo count($gallery) + 1; ?>"></label><button class="admin-btn" type="submit"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add image</button></form>
    <div class="admin-gallery-tools"><label><i class="bi bi-search" aria-hidden="true"></i><input id="gallery-search" type="search" placeholder="Search caption or category" autocomplete="off"></label><button type="button" class="admin-btn secondary" id="gallery-reset" hidden><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset</button></div>
    <nav class="admin-category-status-tabs" id="gallery-status-tabs" aria-label="Gallery visibility"><button type="button" data-gallery-filter="all" aria-current="page">All <span><?php echo $galleryCounts['all']; ?></span></button><button type="button" data-gallery-filter="visible">Visible <span><?php echo $galleryCounts['visible']; ?></span></button><button type="button" data-gallery-filter="hidden">Hidden <span><?php echo $galleryCounts['hidden']; ?></span></button><button type="button" data-gallery-filter="automatic">Auto hidden <span><?php echo $galleryCounts['automatic']; ?></span></button></nav>
    <div class="admin-gallery-list" id="admin-gallery-list"><?php foreach ($gallery as $image) { $isPublic = (int) $image['is_visible'] === 1 && empty($image['is_auto_hidden']); ?><article class="admin-gallery-item" data-gallery-state="<?php echo $isPublic ? 'visible' : 'hidden'; ?>" data-auto-hidden="<?php echo !empty($image['is_auto_hidden']) ? 'true' : 'false'; ?>"><img src="../<?php echo adminEscape(siteAsset($image['image_path'])); ?>" alt="<?php echo adminEscape($image['caption']); ?>"><div class="admin-gallery-summary"><div><strong><?php echo adminEscape($image['caption'] ?: 'Untitled image'); ?></strong><p><?php echo adminEscape($image['category_name'] ?: 'No menu category'); ?></p></div><div class="admin-gallery-badges"><span class="admin-category-badge <?php echo $isPublic ? 'is-active' : 'is-inactive'; ?>"><?php echo $isPublic ? 'Visible' : 'Hidden'; ?></span><?php if (!empty($image['is_auto_hidden'])) { ?><span class="admin-category-badge is-inactive">Category paused</span><?php } ?></div><div class="admin-gallery-actions"><form method="post" class="admin-inline"><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="tab" value="gallery"><input type="hidden" name="action" value="toggle_gallery_visibility"><input type="hidden" name="image_id" value="<?php echo (int) $image['image_id']; ?>"><button class="admin-status-toggle <?php echo $image['is_visible'] ? 'is-active' : ''; ?>" type="submit"><i class="bi <?php echo $image['is_visible'] ? 'bi-toggle-on' : 'bi-toggle-off'; ?>" aria-hidden="true"></i><?php echo $image['is_visible'] ? 'Visible' : 'Hidden'; ?></button></form><details><summary class="admin-icon-button" title="Edit image"><i class="bi bi-pencil-square" aria-hidden="true"></i><span class="sr-only">Edit image</span></summary><form method="post" enctype="multipart/form-data" class="admin-form admin-gallery-editor"><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="tab" value="gallery"><input type="hidden" name="action" value="save_gallery"><input type="hidden" name="image_id" value="<?php echo (int) $image['image_id']; ?>"><label>Caption<input name="caption" maxlength="120" value="<?php echo adminEscape($image['caption']); ?>"></label><label>Menu category<select name="category_name"><option value="">No menu link</option><?php foreach ($menuCategories as $category) { ?><option value="<?php echo adminEscape($category['name']); ?>" <?php echo ($image['category_name'] ?? '') === $category['name'] ? 'selected' : ''; ?>><?php echo adminEscape($category['name']); ?><?php echo $category['is_active'] ? '' : ' (paused)'; ?></option><?php } ?></select></label><label>Order<input name="sort_order" type="number" min="0" max="999" value="<?php echo (int) $image['sort_order']; ?>"></label><label>Replace image<input name="image_file" type="file" accept="image/jpeg,image/png,image/webp"></label><label class="check"><input type="checkbox" name="is_visible" <?php echo $image['is_visible'] ? 'checked' : ''; ?>> Visible when category is active</label><button class="admin-btn" type="submit"><i class="bi bi-floppy" aria-hidden="true"></i> Save changes</button></form></details><form method="post" class="admin-inline" onsubmit="return confirm('Delete this gallery image?')"><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="tab" value="gallery"><input type="hidden" name="action" value="delete_gallery"><input type="hidden" name="image_id" value="<?php echo (int) $image['image_id']; ?>"><button class="admin-icon-button danger" type="submit" title="Delete image"><i class="bi bi-trash" aria-hidden="true"></i><span class="sr-only">Delete image</span></button></form></div></div></article><?php } ?><?php if (!$gallery) { ?><p class="admin-muted">No gallery images. Add the first one above.</p><?php } ?></div><nav class="admin-pagination admin-gallery-pagination" id="gallery-pagination" aria-label="Gallery pagination"></nav><p class="admin-category-empty" id="gallery-empty" hidden><i class="bi bi-search" aria-hidden="true"></i> No gallery images match this filter.</p>
</section>
<script>(function(){const search=document.getElementById('gallery-search'),reset=document.getElementById('gallery-reset'),tabs=document.getElementById('gallery-status-tabs'),cards=Array.from(document.querySelectorAll('#admin-gallery-list .admin-gallery-item')),empty=document.getElementById('gallery-empty'),pager=document.getElementById('gallery-pagination');if(!search||!reset||!tabs||!empty||!pager)return;let state='all',page=1;const pageSize=12;function setActive(button){tabs.querySelectorAll('[data-gallery-filter]').forEach(function(item){if(item===button)item.setAttribute('aria-current','page');else item.removeAttribute('aria-current');});}function render(){const query=search.value.trim().toLowerCase(),matching=cards.filter(function(card){return card.textContent.toLowerCase().includes(query)&&(state==='all'||(state==='automatic'?card.dataset.autoHidden==='true':card.dataset.galleryState===state));}),pages=Math.max(1,Math.ceil(matching.length/pageSize));if(page>pages)page=pages;cards.forEach(function(card){card.hidden=true;});matching.slice((page-1)*pageSize,page*pageSize).forEach(function(card){card.hidden=false;});reset.hidden=query===''&&state==='all';empty.hidden=matching.length>0;pager.innerHTML=pages>1?Array.from({length:pages},function(_,index){const number=index+1;return '<button type="button" data-page="'+number+'"'+(number===page?' class="active" aria-current="page"':'')+'>'+number+'</button>';}).join(''):'';}search.addEventListener('input',function(){page=1;render();});reset.addEventListener('click',function(){search.value='';state='all';page=1;setActive(tabs.querySelector('[data-gallery-filter="all"]'));render();search.focus();});tabs.addEventListener('click',function(event){const button=event.target.closest('[data-gallery-filter]');if(!button)return;state=button.dataset.galleryFilter;page=1;setActive(button);render();});pager.addEventListener('click',function(event){const button=event.target.closest('[data-page]');if(!button)return;page=Number(button.dataset.page);render();});render();}());</script>
<?php } ?>
<?php } ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
