<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/../includes/offers.php';
adminRequireLogin();
ensureOfferTables();

function adminOfferUpload($existing = '')
{
    if (empty($_FILES['image_file']) || $_FILES['image_file']['error'] === UPLOAD_ERR_NO_FILE) return $existing;
    if ($_FILES['image_file']['error'] !== UPLOAD_ERR_OK || $_FILES['image_file']['size'] > 4000000) return false;
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image_file']['tmp_name']);
    $extensions = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp');
    if (!isset($extensions[$mime])) return false;
    $directory = __DIR__ . '/../uploads/offers';
    if (!is_dir($directory) && !mkdir($directory, 0775, true)) return false;
    $path = 'uploads/offers/' . bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
    return move_uploaded_file($_FILES['image_file']['tmp_name'], __DIR__ . '/../' . $path) ? $path : false;
}
function adminOfferListUrl($status = 'all') { return 'offers.php' . ($status === 'all' ? '' : '?status=' . rawurlencode($status)); }
function adminOfferField($data, $name, $default = '') { return trim((string) ($data[$name] ?? $default)); }
function adminSeedOfferCategories($conn)
{
    if (!$conn) return false;
    $starterCategories = array(
        array('First Purchase', 'Welcome offers for a customer\'s first completed order.', 10),
        array('Today\'s Specials', 'Short-running offers for selected daily dishes and promotions.', 20),
        array('Order Value', 'Discounts unlocked when eligible order totals reach a set amount.', 30),
        array('Food Categories', 'Offers limited to selected menu categories or dishes.', 40),
        array('Weekend Offers', 'Recurring or limited offers for weekend ordering.', 50),
        array('Festival Offers', 'Seasonal and festival campaigns with scheduled dates.', 60),
        array('Promotion Offers', 'Campaign, partner, referral, and coupon promotions.', 70),
    );
    $existing = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM offer_categories');
    if (!$existing || (int) (mysqli_fetch_assoc($existing)['total'] ?? 0) > 0) return false;
    $stmt = mysqli_prepare($conn, 'INSERT INTO offer_categories(name,description,sort_order,is_active) VALUES(?,?,?,1)');
    if (!$stmt || !mysqli_begin_transaction($conn)) return false;
    $ok = true;
    foreach ($starterCategories as $category) {
        list($name, $description, $sortOrder) = $category;
        if (!mysqli_stmt_bind_param($stmt, 'ssi', $name, $description, $sortOrder) || !mysqli_stmt_execute($stmt)) { $ok = false; break; }
    }
    if ($ok && mysqli_commit($conn)) return true;
    mysqli_rollback($conn);
    return false;
}
function adminOfferCategoryTargets($data)
{
    $ids = array();
    foreach ((array) ($data['food_category_ids'] ?? array()) as $id) {
        $id = (int) $id;
        if ($id > 0) $ids[$id] = $id;
    }
    return array_values($ids);
}
function adminOfferItemTargets($data)
{
    $ids = array();
    foreach ((array) ($data['food_item_ids'] ?? array()) as $id) {
        $id = (int) $id;
        if ($id > 0) $ids[$id] = $id;
    }
    return array_values($ids);
}
function adminSaveOffer($data, $offerId = 0)
{
    $old = $offerId ? offerGet($offerId) : null;
    if ($offerId && !$old) return 'Offer not found.';
    $categoryId = (int) ($data['offer_category_id'] ?? 0);
    $title = adminOfferField($data, 'title');
    $description = adminOfferField($data, 'description');
    $terms = adminOfferField($data, 'terms');
    $code = strtoupper(adminOfferField($data, 'code'));
    $type = $data['discount_type'] ?? 'percentage';
    $scope = $data['customer_scope'] ?? 'all';
    $value = (float) ($data['discount_value'] ?? 0);
    $maximum = adminOfferField($data, 'maximum_discount') === '' ? null : (float) $data['maximum_discount'];
    $minimum = (float) ($data['minimum_order'] ?? 0);
    $targetCategoryIds = adminOfferCategoryTargets($data);
    $targetFoodIds = adminOfferItemTargets($data);
    $foodCategory = null;
    $startsAt = str_replace('T', ' ', adminOfferField($data, 'starts_at'));
    $endsAt = str_replace('T', ' ', adminOfferField($data, 'ends_at'));
    $totalLimit = adminOfferField($data, 'total_usage_limit') === '' ? null : (int) $data['total_usage_limit'];
    $customerLimit = max(1, (int) ($data['per_customer_limit'] ?? 1));
    $dailyLimit = adminOfferField($data, 'daily_usage_limit') === '' ? null : (int) $data['daily_usage_limit'];
    $automatic = isset($data['is_automatic']) ? 1 : 0;
    $combinable = isset($data['is_combinable']) ? 1 : 0;
    $active = isset($data['is_active']) ? 1 : 0;
    $priority = max(0, min(999, (int) ($data['priority'] ?? 0)));
    $minimumEligible = max(0, (float) ($data['minimum_eligible_subtotal'] ?? 0));
    $days = array_values(array_intersect(array('1','2','3','4','5','6','7'), (array) ($data['days_of_week'] ?? array())));
    $dailyStart = adminOfferField($data, 'daily_start_time') ?: null;
    $dailyEnd = adminOfferField($data, 'daily_end_time') ?: null;
    if ($categoryId < 1 || $title === '' || strlen($title) > 140 || $description === '' || strlen($description) > 600 || $terms === '' || !in_array($type, array('percentage','fixed','free_delivery'), true) || !in_array($scope, array('all','first_order'), true) || strtotime($startsAt) === false || strtotime($endsAt) === false || strtotime($endsAt) <= strtotime($startsAt) || $minimum < 0 || $minimumEligible < 0 || ($dailyStart && $dailyEnd && $dailyStart >= $dailyEnd) || ($type !== 'free_delivery' && $value <= 0) || ($type === 'percentage' && $value > 100) || ($maximum !== null && $maximum < 0) || ($totalLimit !== null && $totalLimit < 1) || ($dailyLimit !== null && $dailyLimit < 1)) return 'Check the offer title, schedule, discount, limits, and terms.';
    if ($code !== '' && !preg_match('/^[A-Z0-9_-]{3,40}$/', $code)) return 'Offer codes must use 3 to 40 uppercase letters, numbers, hyphens, or underscores.';
    $image = adminOfferUpload($old['image_path'] ?? '');
    if ($image === false) return 'Choose a JPG, PNG, or WebP image under 4 MB.';
    $code = $code === '' ? null : $code;
    $conn = adminDb();
    if (!$conn) return 'Database unavailable.';
    if ($targetCategoryIds) {
        $validIds = array_flip(array_map('intval', array_column(adminCategories(), 'category_id')));
        foreach ($targetCategoryIds as $targetId) if (!isset($validIds[$targetId])) return 'Choose only categories from the current menu category list.';
    }
    if ($targetFoodIds) {
        $validFoodIds = array_flip(array_map('intval', array_column(adminRows('SELECT id FROM foods'), 'id')));
        foreach ($targetFoodIds as $targetId) if (!isset($validFoodIds[$targetId])) return 'Choose only current menu items.';
    }
    $sql = $offerId ? 'UPDATE offers SET offer_category_id=?,title=?,description=?,terms=?,code=?,discount_type=?,discount_value=?,maximum_discount=?,minimum_order=?,applicable_food_category=?,customer_scope=?,starts_at=?,ends_at=?,total_usage_limit=?,per_customer_limit=?,daily_usage_limit=?,is_automatic=?,is_combinable=?,is_active=?,image_path=? WHERE offer_id=?' : 'INSERT INTO offers(offer_category_id,title,description,terms,code,discount_type,discount_value,maximum_discount,minimum_order,applicable_food_category,customer_scope,starts_at,ends_at,total_usage_limit,per_customer_limit,daily_usage_limit,is_automatic,is_combinable,is_active,image_path) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return 'Could not save this offer. The code may already be in use.';
    $params = array($categoryId,$title,$description,$terms,$code,$type,$value,$maximum,$minimum,$foodCategory,$scope,$startsAt,$endsAt,$totalLimit,$customerLimit,$dailyLimit,$automatic,$combinable,$active,$image);
    if ($offerId) $params[] = $offerId;
    mysqli_stmt_bind_param($stmt, 'isssssdddssssiiiiiis' . ($offerId ? 'i' : ''), ...$params);
    if (!mysqli_begin_transaction($conn) || !mysqli_stmt_execute($stmt)) return 'Could not save this offer. The code may already be in use.';
    $savedOfferId = $offerId ?: (int) mysqli_insert_id($conn);
    $schedule = implode(',', $days);
    $saveRules = mysqli_prepare($conn, 'UPDATE offers SET priority=?,minimum_eligible_subtotal=?,days_of_week=?,daily_start_time=?,daily_end_time=? WHERE offer_id=?');
    $ok = $saveRules && mysqli_stmt_bind_param($saveRules, 'idsssi', $priority, $minimumEligible, $schedule, $dailyStart, $dailyEnd, $savedOfferId) && mysqli_stmt_execute($saveRules);
    $clearTargets = mysqli_prepare($conn, 'DELETE FROM offer_food_categories WHERE offer_id=?');
    $ok = $ok && $clearTargets && mysqli_stmt_bind_param($clearTargets, 'i', $savedOfferId) && mysqli_stmt_execute($clearTargets);
    if ($ok && $targetCategoryIds) {
        $addTarget = mysqli_prepare($conn, 'INSERT INTO offer_food_categories(offer_id,category_id) VALUES(?,?)');
        $ok = (bool) $addTarget;
        foreach ($targetCategoryIds as $targetId) {
            if (!$ok || !mysqli_stmt_bind_param($addTarget, 'ii', $savedOfferId, $targetId) || !mysqli_stmt_execute($addTarget)) { $ok = false; break; }
        }
    }
    $clearItemTargets = mysqli_prepare($conn, 'DELETE FROM offer_food_items WHERE offer_id=?');
    $ok = $ok && $clearItemTargets && mysqli_stmt_bind_param($clearItemTargets, 'i', $savedOfferId) && mysqli_stmt_execute($clearItemTargets);
    if ($ok && $targetFoodIds) {
        $addItemTarget = mysqli_prepare($conn, 'INSERT INTO offer_food_items(offer_id,food_id) VALUES(?,?)');
        $ok = (bool) $addItemTarget;
        foreach ($targetFoodIds as $targetId) {
            if (!$ok || !mysqli_stmt_bind_param($addItemTarget, 'ii', $savedOfferId, $targetId) || !mysqli_stmt_execute($addItemTarget)) { $ok = false; break; }
        }
    }
    if ($ok && mysqli_commit($conn)) return $offerId ? 'Offer updated.' : 'Offer created.';
    mysqli_rollback($conn);
    return 'Could not save the offer targets.';
}

$offerStatus = $_GET['status'] ?? 'all';
if (!in_array($offerStatus, array('all','active','inactive'), true)) $offerStatus = 'all';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!adminVerifyPost()) adminRedirect('offers.php', 'Request expired. Please try again.');
    $conn = adminDb();
    if (isset($_POST['seed_offer_categories'])) adminRedirect('offers.php', adminSeedOfferCategories($conn) ? 'Starter offer categories created. You can now edit, pause, reorder, or delete them.' : 'Starter categories were not created because categories already exist or the database is unavailable.');
    if (isset($_POST['category_save'])) {
        $id=(int)($_POST['offer_category_id']??0); $name=adminOfferField($_POST,'name'); $description=adminOfferField($_POST,'description'); $sort=(int)($_POST['sort_order']??0); $active=isset($_POST['is_active'])?1:0;
        if ($name==='' || strlen($name)>80 || strlen($description)>255) adminRedirect('offers.php','Enter a category name and a shorter description.');
        $stmt=mysqli_prepare($conn,$id?'UPDATE offer_categories SET name=?,description=?,sort_order=?,is_active=? WHERE offer_category_id=?':'INSERT INTO offer_categories(name,description,sort_order,is_active) VALUES(?,?,?,?)');
        if($id) mysqli_stmt_bind_param($stmt,'ssiii',$name,$description,$sort,$active,$id); else mysqli_stmt_bind_param($stmt,'ssii',$name,$description,$sort,$active);
        adminRedirect('offers.php',mysqli_stmt_execute($stmt)?($id?'Offer category updated.':'Offer category created.'):'Could not save category. Category names must be unique.');
    }
    if (isset($_POST['category_delete'])) adminRedirect('offers.php',runQuery('DELETE FROM offer_categories WHERE offer_category_id=?','i',(int)$_POST['offer_category_id'])?'Offer category deleted.':'A category with offers cannot be deleted.');
    if (isset($_POST['offer_save'])) adminRedirect('offers.php',adminSaveOffer($_POST,(int)($_POST['offer_id']??0)));
    if (isset($_POST['offer_toggle_status'])) {
        $status=$_POST['status']??'all'; if(!in_array($status,array('all','active','inactive'),true))$status='all';
        adminRedirect(adminOfferListUrl($status),runQuery('UPDATE offers SET is_active=IF(is_active=1,0,1) WHERE offer_id=?','i',(int)$_POST['offer_id'])?'Offer status updated.':'Could not update this offer.');
    }
    if (isset($_POST['offer_delete'])) adminRedirect('offers.php',runQuery('DELETE FROM offers WHERE offer_id=?','i',(int)$_POST['offer_id'])?'Offer deleted.':'This offer has redemption history and cannot be deleted. Pause it instead.');
}

$categories=offerCategories(); $editingCategory=null;
foreach($categories as $category) if((int)$category['offer_category_id']===(int)($_GET['category']??0))$editingCategory=$category;
$editing=isset($_GET['edit'])?offerGet((int)$_GET['edit']):null;
$counts=array('all'=>0,'active'=>0,'inactive'=>0);
foreach(adminRows('SELECT is_active,COUNT(*) AS total FROM offers GROUP BY is_active') as $row)$counts[$row['is_active']?'active':'inactive']=(int)$row['total'];
$counts['all']=$counts['active']+$counts['inactive'];
$offers=adminRows('SELECT o.*,c.name AS category_name,(SELECT COUNT(*) FROM offer_redemptions r WHERE r.offer_id=o.offer_id) AS redemptions,(SELECT GROUP_CONCAT(fc.name ORDER BY fc.name SEPARATOR ", ") FROM offer_food_categories t JOIN food_categories fc ON fc.category_id=t.category_id WHERE t.offer_id=o.offer_id) AS target_categories FROM offers o JOIN offer_categories c ON c.offer_category_id=o.offer_category_id ORDER BY o.is_active DESC,o.ends_at DESC');
$foodCategories=adminCategories(); $foodItems=adminRows('SELECT id,name,category FROM foods WHERE is_active=1 ORDER BY category,name'); $selectedFoodCategoryIds=$editing ? array_map('intval', array_column(adminRows('SELECT category_id FROM offer_food_categories WHERE offer_id=' . (int) $editing['offer_id']), 'category_id')) : array(); $selectedFoodItemIds=$editing ? array_map('intval', array_column(adminRows('SELECT food_id FROM offer_food_items WHERE offer_id=' . (int) $editing['offer_id']), 'food_id')) : array(); $offerView=$_GET['view']??'list'; if(!in_array($offerView,array('form','list'),true))$offerView='list'; if($editing||$editingCategory)$offerView='form'; $adminTitle='Offers'; $adminPage='offers'; $adminStyles=array('offers-tabs.css'); include __DIR__.'/includes/header.php';
?>
<nav class="admin-offer-view-tabs" aria-label="Offers workspace"><a href="offers.php?view=list"<?php echo $offerView==='list'?' aria-current="page"':''; ?>><i class="bi bi-table" aria-hidden="true"></i>All offers <span><?php echo $counts['all']; ?></span></a><a href="offers.php?view=form"<?php echo $offerView==='form'?' aria-current="page"':''; ?>><i class="bi bi-plus-circle" aria-hidden="true"></i><?php echo $editing?'Edit offer':'Create offer'; ?></a></nav>
<div class="admin-offer-workspace offer-view-<?php echo adminEscape($offerView); ?>">
<div class="admin-offers-layout offer-editor-panel">
<section class="admin-card" id="offer-form"><div class="admin-toolbar"><h2><?php echo $editing?'Edit offer':'Create offer'; ?></h2><?php if($editing){?><a href="<?php echo adminEscape(adminOfferListUrl($offerStatus)); ?>">Cancel</a><?php }?></div>
<?php if(!$categories){?><p class="admin-notice">Create an offer category below before creating an offer.</p><form method="post" class="admin-inline"><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><button class="admin-btn" type="submit" name="seed_offer_categories"><i class="bi bi-magic" aria-hidden="true"></i> Create starter categories</button></form><?php }else{?>
<form method="post" enctype="multipart/form-data" class="admin-form admin-offer-form"><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="offer_id" value="<?php echo (int)($editing['offer_id']??0); ?>">
<div class="admin-form-grid"><label>Offer category<select name="offer_category_id" required><?php foreach($categories as $category){?><option value="<?php echo (int)$category['offer_category_id']; ?>" <?php echo (int)($editing['offer_category_id']??0)===(int)$category['offer_category_id']?'selected':''; ?>><?php echo adminEscape($category['name']); ?></option><?php }?></select></label><label>Offer title<input name="title" maxlength="140" value="<?php echo adminEscape($editing['title']??''); ?>" required></label></div>
<label>Description<textarea name="description" maxlength="600" rows="3" required><?php echo adminEscape($editing['description']??''); ?></textarea></label><label>Customer terms<textarea name="terms" rows="4" required><?php echo adminEscape($editing['terms']??'Valid for eligible orders while the offer is active.'); ?></textarea></label>
<div class="admin-form-grid"><label>Coupon code<input name="code" maxlength="40" value="<?php echo adminEscape($editing['code']??''); ?>" placeholder="WELCOME50"></label><label>Discount type<select name="discount_type"><option value="percentage" <?php echo ($editing['discount_type']??'')==='percentage'?'selected':''; ?>>Percentage</option><option value="fixed" <?php echo ($editing['discount_type']??'')==='fixed'?'selected':''; ?>>Fixed amount</option><option value="free_delivery" <?php echo ($editing['discount_type']??'')==='free_delivery'?'selected':''; ?>>Free delivery</option></select></label><label>Discount value<input name="discount_value" type="number" min="0" step="0.01" value="<?php echo adminEscape($editing['discount_value']??''); ?>"></label><label>Maximum discount<input name="maximum_discount" type="number" min="0" step="0.01" value="<?php echo adminEscape($editing['maximum_discount']??''); ?>" placeholder="For percentage only"></label><label>Minimum cart value<input name="minimum_order" type="number" min="0" step="0.01" value="<?php echo adminEscape($editing['minimum_order']??0); ?>"></label><label>Minimum eligible items value<input name="minimum_eligible_subtotal" type="number" min="0" step="0.01" value="<?php echo adminEscape($editing['minimum_eligible_subtotal']??0); ?>"></label><label>Priority<input name="priority" type="number" min="0" max="999" value="<?php echo adminEscape($editing['priority']??0); ?>"></label><label>Customer eligibility<select name="customer_scope"><option value="all" <?php echo ($editing['customer_scope']??'')==='all'?'selected':''; ?>>All customers</option><option value="first_order" <?php echo ($editing['customer_scope']??'')==='first_order'?'selected':''; ?>>First order only</option></select></label></div><?php if ($foodCategories) { ?><fieldset class="admin-target-picker"><legend>Eligible menu categories</legend><label class="admin-target-search"><i class="bi bi-search" aria-hidden="true"></i><input type="search" data-target-search="offer-category-targets" placeholder="Search categories" autocomplete="off"></label><div id="offer-category-targets" class="admin-target-options"><?php foreach($foodCategories as $foodCategory){?><label data-target-option><input type="checkbox" name="food_category_ids[]" value="<?php echo (int) $foodCategory['category_id']; ?>" data-menu-category="<?php echo adminEscape($foodCategory['name']); ?>" <?php echo in_array((int) $foodCategory['category_id'], $selectedFoodCategoryIds, true)?'checked':''; ?>><?php echo adminEscape($foodCategory['name']); ?></label><?php }?></div><small class="admin-muted">Selecting a category automatically selects its current menu items. Clear the category to clear those related items.</small></fieldset><?php } else { ?><p class="admin-notice">Create menu categories from <a href="menu.php">Menu</a> before limiting an offer to selected categories.</p><?php } ?><?php if ($foodItems) { ?><fieldset class="admin-target-picker"><legend>Eligible menu items</legend><label class="admin-target-search"><i class="bi bi-search" aria-hidden="true"></i><input type="search" data-target-search="offer-item-targets" placeholder="Search menu items" autocomplete="off"></label><div id="offer-item-targets" class="admin-target-options"><?php foreach($foodItems as $foodItem){?><label data-target-option><input type="checkbox" name="food_item_ids[]" value="<?php echo (int) $foodItem['id']; ?>" data-menu-category="<?php echo adminEscape($foodItem['category']); ?>" <?php echo in_array((int) $foodItem['id'], $selectedFoodItemIds, true)?'checked':''; ?>><span><?php echo adminEscape($foodItem['name']); ?></span><small><?php echo adminEscape($foodItem['category']); ?></small></label><?php }?></div><small class="admin-muted">You can also choose individual menu items. Category selection keeps its related items selected.</small></fieldset><?php } ?>
<div class="admin-form-grid"><label>Starts at<input type="datetime-local" name="starts_at" value="<?php echo adminEscape(isset($editing['starts_at'])?date('Y-m-d\TH:i',strtotime($editing['starts_at'])):date('Y-m-d\TH:i')); ?>" required></label><label>Ends at<input type="datetime-local" name="ends_at" value="<?php echo adminEscape(isset($editing['ends_at'])?date('Y-m-d\TH:i',strtotime($editing['ends_at'])):date('Y-m-d\TH:i',strtotime('+7 days'))); ?>" required></label><label>Daily start time<input name="daily_start_time" type="time" value="<?php echo adminEscape(!empty($editing['daily_start_time'])?substr($editing['daily_start_time'],0,5):''); ?>"></label><label>Daily end time<input name="daily_end_time" type="time" value="<?php echo adminEscape(!empty($editing['daily_end_time'])?substr($editing['daily_end_time'],0,5):''); ?>"></label><label>Total uses limit<input name="total_usage_limit" type="number" min="1" value="<?php echo adminEscape($editing['total_usage_limit']??''); ?>" placeholder="No limit"></label><label>Uses per customer<input name="per_customer_limit" type="number" min="1" value="<?php echo adminEscape($editing['per_customer_limit']??1); ?>"></label><label>Daily uses limit<input name="daily_usage_limit" type="number" min="1" value="<?php echo adminEscape($editing['daily_usage_limit']??''); ?>" placeholder="No limit"></label><label>Offer image<input type="file" name="image_file" accept="image/jpeg,image/png,image/webp"></label></div><fieldset class="admin-weekdays"><legend>Available days</legend><?php $selectedDays=explode(',',(string)($editing['days_of_week']??'')); foreach(array('1'=>'Mon','2'=>'Tue','3'=>'Wed','4'=>'Thu','5'=>'Fri','6'=>'Sat','7'=>'Sun') as $dayValue=>$dayLabel){ ?><label class="check"><input type="checkbox" name="days_of_week[]" value="<?php echo $dayValue; ?>" <?php echo in_array($dayValue,$selectedDays,true)?'checked':''; ?>><?php echo $dayLabel; ?></label><?php } ?><small class="admin-muted">Leave all clear for every day.</small></fieldset>
<div class="admin-check-row"><label class="check"><input type="checkbox" name="is_automatic" <?php echo !empty($editing['is_automatic'])?'checked':''; ?>> Apply automatically</label><label class="check"><input type="checkbox" name="is_combinable" <?php echo !empty($editing['is_combinable'])?'checked':''; ?>> Can combine</label><label class="check"><input type="checkbox" name="is_active" <?php echo !$editing||!empty($editing['is_active'])?'checked':''; ?>> Active</label></div><button class="admin-btn" type="submit" name="offer_save"><i class="bi bi-save" aria-hidden="true"></i><?php echo $editing?'Save offer':'Create offer'; ?></button></form><?php }?>
</section>
<section class="admin-card"><div class="admin-toolbar"><div><h2>Offer categories</h2><p class="admin-muted">These are fully editable. Change their name, description, display order, and active status whenever needed.</p></div></div><div id="categories" class="admin-category-manager"><form method="post" class="admin-form admin-category-form"><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="offer_category_id" value="<?php echo (int)($editingCategory['offer_category_id']??0); ?>"><label>Name<input name="name" maxlength="80" value="<?php echo adminEscape($editingCategory['name']??''); ?>" required></label><label>Description<input name="description" maxlength="255" value="<?php echo adminEscape($editingCategory['description']??''); ?>"></label><label>Sort order<input name="sort_order" type="number" value="<?php echo (int)($editingCategory['sort_order']??0); ?>"></label><label class="check"><input type="checkbox" name="is_active" <?php echo !$editingCategory||!empty($editingCategory['is_active'])?'checked':''; ?>> Active</label><button class="admin-btn" name="category_save"><?php echo $editingCategory?'Save category':'Add category'; ?></button></form><div class="admin-category-table"><?php foreach($categories as $category){?><div><strong><?php echo adminEscape($category['name']); ?></strong><span><?php echo adminEscape($category['description']); ?></span><div class="admin-actions"><a class="admin-icon-button" href="offers.php?category=<?php echo (int)$category['offer_category_id']; ?>#categories" title="Edit offer category" aria-label="Edit <?php echo adminEscape($category['name']); ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i></a><form method="post" class="admin-inline"><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="offer_category_id" value="<?php echo (int)$category['offer_category_id']; ?>"><button class="admin-icon-button danger" name="category_delete" title="Delete offer category" aria-label="Delete <?php echo adminEscape($category['name']); ?>"><i class="bi bi-trash" aria-hidden="true"></i></button></form></div></div><?php }?></div></div></section>
</div>
<section class="admin-card offer-list-panel"><div class="admin-toolbar"><h2>All offers</h2><a class="admin-btn secondary" href="../offers.php" target="_blank"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>Public offers</a></div><div class="admin-list-tools"><label><i class="bi bi-search" aria-hidden="true"></i><input id="admin-offer-search" type="search" placeholder="Search offers" autocomplete="off"></label><button type="button" class="admin-btn secondary" id="admin-offer-reset" hidden><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Reset</button></div><nav class="admin-offer-status-tabs" id="admin-offer-tabs" aria-label="Offer status filter"><a href="<?php echo adminEscape(adminOfferListUrl('all')); ?>" data-status="all"<?php echo $offerStatus==='all'?' aria-current="page"':''; ?>>All <span><?php echo $counts['all']; ?></span></a><a href="<?php echo adminEscape(adminOfferListUrl('active')); ?>" data-status="active"<?php echo $offerStatus==='active'?' aria-current="page"':''; ?>>Active <span><?php echo $counts['active']; ?></span></a><a href="<?php echo adminEscape(adminOfferListUrl('inactive')); ?>" data-status="inactive"<?php echo $offerStatus==='inactive'?' aria-current="page"':''; ?>>Inactive <span><?php echo $counts['inactive']; ?></span></a></nav>
<div class="admin-table-wrap"><table class="admin-table admin-offer-table" id="admin-offer-table"><thead><tr><th>Offer details</th><th>Benefit and eligibility</th><th>Schedule</th><th>Redemptions</th><th><span class="sr-only">Actions</span></th></tr></thead><tbody><?php foreach($offers as $offer){?><tr data-status="<?php echo $offer['is_active']?'active':'inactive'; ?>"><td data-label="Offer details"><div class="admin-offer-identity"><?php if($offer['image_path']){?><img src="../<?php echo adminEscape($offer['image_path']); ?>" alt=""><?php }?><div><strong><?php echo adminEscape($offer['title']); ?></strong><span class="admin-offer-category"><?php echo adminEscape($offer['category_name']); ?></span><?php if($offer['code']){?><code><?php echo adminEscape($offer['code']); ?></code><?php }?><form method="post" class="admin-inline"><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="offer_id" value="<?php echo (int)$offer['offer_id']; ?>"><input type="hidden" name="status" value="<?php echo adminEscape($offerStatus); ?>"><button class="admin-status-toggle <?php echo $offer['is_active']?'is-active':''; ?>" type="submit" name="offer_toggle_status" value="1" aria-pressed="<?php echo $offer['is_active']?'true':'false'; ?>" title="<?php echo $offer['is_active']?'Pause offer':'Activate offer'; ?>" aria-label="<?php echo $offer['is_active']?'Pause offer':'Activate offer'; ?>"><i class="bi <?php echo $offer['is_active']?'bi-toggle-on':'bi-toggle-off'; ?>" aria-hidden="true"></i><span><?php echo $offer['is_active']?'Active':'Paused'; ?></span></button></form></div></div></td><td data-label="Benefit and eligibility"><strong class="admin-offer-value"><?php echo adminEscape(offerFormatValue($offer)); ?></strong><span class="admin-offer-detail">Min. order Rs <?php echo number_format((float)$offer['minimum_order'],0); ?> &middot; <?php echo $offer['customer_scope']==='first_order'?'First order':'All customers'; ?></span><span class="admin-offer-detail">Menu: <?php echo adminEscape($offer['target_categories'] ?: 'Whole menu'); ?></span></td><td data-label="Schedule"><time datetime="<?php echo adminEscape($offer['starts_at']); ?>"><?php echo adminEscape(date('d M Y',strtotime($offer['starts_at']))); ?><small><?php echo adminEscape(date('H:i',strtotime($offer['starts_at']))); ?></small></time><i class="bi bi-arrow-down admin-offer-schedule-arrow" aria-hidden="true"></i><time datetime="<?php echo adminEscape($offer['ends_at']); ?>"><?php echo adminEscape(date('d M Y',strtotime($offer['ends_at']))); ?><small><?php echo adminEscape(date('H:i',strtotime($offer['ends_at']))); ?></small></time></td><td data-label="Redemptions"><strong class="admin-offer-usage"><?php echo (int)$offer['redemptions']; ?></strong><span class="admin-offer-detail">successful use<?php echo (int)$offer['redemptions'] === 1 ? '' : 's'; ?></span></td><td data-label="Actions"><div class="admin-actions"><a class="admin-icon-button" href="offers.php?status=<?php echo adminEscape($offerStatus); ?>&amp;edit=<?php echo (int)$offer['offer_id']; ?>#offer-form" title="Edit offer" aria-label="Edit <?php echo adminEscape($offer['title']); ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i></a><form method="post" class="admin-inline" onsubmit="return confirm('Delete this offer?')"><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="offer_id" value="<?php echo (int)$offer['offer_id']; ?>"><button class="admin-icon-button danger" name="offer_delete" title="Delete offer" aria-label="Delete <?php echo adminEscape($offer['title']); ?>"><i class="bi bi-trash" aria-hidden="true"></i></button></form></div></td></tr><?php }?></tbody></table><nav class="admin-pagination" id="admin-offer-pagination" aria-label="Offers pagination"></nav><?php if(!$offers){?><p class="admin-muted">No offers yet.</p><?php }?></div></section>
</div>
<script>
(function () { const rows=Array.from(document.querySelectorAll('#admin-offer-table tbody tr')),tabs=Array.from(document.querySelectorAll('#admin-offer-tabs a')),search=document.getElementById('admin-offer-search'),reset=document.getElementById('admin-offer-reset'),pager=document.getElementById('admin-offer-pagination'); let status='<?php echo adminEscape($offerStatus); ?>',query='',page=1; const pageSize=8; function render(){const shown=rows.filter(row=>(status==='all'||row.dataset.status===status)&&row.textContent.toLowerCase().includes(query));const pages=Math.max(1,Math.ceil(shown.length/pageSize));if(page>pages)page=pages;rows.forEach(row=>row.hidden=true);shown.slice((page-1)*pageSize,page*pageSize).forEach(row=>row.hidden=false);tabs.forEach(tab=>tab.toggleAttribute('aria-current',tab.dataset.status===status));reset.hidden=status==='all'&&query==='';pager.innerHTML=pages>1?Array.from({length:pages},(_,i)=>'<button type="button" data-page="'+(i+1)+'"'+(i+1===page?' class="active" aria-current="page"':'')+'>'+(i+1)+'</button>').join(''):'';}tabs.forEach(tab=>tab.addEventListener('click',event=>{event.preventDefault();status=tab.dataset.status;page=1;render();}));search.addEventListener('input',()=>{query=search.value.trim().toLowerCase();page=1;render();});reset.addEventListener('click',()=>{status='all';query='';search.value='';page=1;render();});pager.addEventListener('click',event=>{const button=event.target.closest('[data-page]');if(button){page=Number(button.dataset.page);render();}});render();}());
(function(){document.querySelectorAll('[data-target-search]').forEach(function(input){const list=document.getElementById(input.dataset.targetSearch);if(!list)return;input.addEventListener('input',function(){const query=input.value.trim().toLowerCase();list.querySelectorAll('[data-target-option]').forEach(function(option){option.hidden=!option.textContent.toLowerCase().includes(query);});});}());
(function(){const categoryInputs=Array.from(document.querySelectorAll('#offer-category-targets input[data-menu-category]'));const itemInputs=Array.from(document.querySelectorAll('#offer-item-targets input[data-menu-category]'));if(!categoryInputs.length||!itemInputs.length)return;categoryInputs.forEach(function(categoryInput){categoryInput.addEventListener('change',function(){const category=categoryInput.dataset.menuCategory;itemInputs.forEach(function(itemInput){if(itemInput.dataset.menuCategory===category)itemInput.checked=categoryInput.checked;});});});}());
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
