<?php
require_once __DIR__ . '/includes/bootstrap.php';
adminRequireLogin();
ensureMisTables();
$conn = adminDb();

if (adminVerifyPost() && isset($_POST['add_rider'])) {
    $name = trim($_POST['name'] ?? ''); $phone = trim($_POST['phone'] ?? '');
    $vehicle = trim($_POST['vehicle_type'] ?? ''); $zoneIds = array_map('intval', (array)($_POST['delivery_zone_ids'] ?? array())); $email = trim($_POST['email'] ?? ''); $password = (string)($_POST['password'] ?? '');
    if ($name === '' || $phone === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) adminRedirect('operations.php', 'Add a valid rider email and a temporary password of at least 8 characters.');
    $zone = '';
    if ($zoneIds) {
        $zoneRows = adminRows('SELECT zone_id,city,zone_name FROM delivery_zones WHERE is_active=1');
        $labels = array();
        foreach ($zoneRows as $zoneRow) if (in_array((int)$zoneRow['zone_id'], $zoneIds, true)) $labels[] = $zoneRow['city'] . ' - ' . $zoneRow['zone_name'];
        $zone = implode(', ', $labels);
        if (!$zone) adminRedirect('operations.php', 'Choose valid delivery zones.');
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "INSERT INTO delivery_riders(name,phone,email,password_hash,vehicle_type,delivery_zone,availability,is_active) VALUES(?,?,?,?,?,?,'available',1)");
    mysqli_stmt_bind_param($stmt, 'ssssss', $name, $phone, $email, $hash, $vehicle, $zone);
    mysqli_stmt_execute($stmt); adminRedirect('operations.php', 'Rider account created. Share the rider login address and temporary password securely.');
}
if (adminVerifyPost() && isset($_POST['add_zone'])) {
    $city = trim($_POST['city'] ?? ''); $zoneName = trim($_POST['zone_name'] ?? '');
    if ($city === '' || $zoneName === '' || strlen($city) > 100 || strlen($zoneName) > 120) adminRedirect('operations.php', 'Enter a valid city and delivery zone.');
    $stmt = mysqli_prepare($conn, 'INSERT INTO delivery_zones(city,zone_name,is_active) VALUES(?,?,1) ON DUPLICATE KEY UPDATE is_active=1');
    mysqli_stmt_bind_param($stmt, 'ss', $city, $zoneName); mysqli_stmt_execute($stmt);
    adminRedirect('operations.php', 'Delivery zone saved. Customers can now select it in saved addresses.');
}
if (adminVerifyPost() && isset($_POST['edit_zone'])) {
    $zoneId = (int)($_POST['zone_id'] ?? 0); $city = trim($_POST['city'] ?? ''); $zoneName = trim($_POST['zone_name'] ?? '');
    if (!$zoneId || $city === '' || $zoneName === '' || strlen($city) > 100 || strlen($zoneName) > 120) adminRedirect('operations.php', 'Enter a valid city and delivery zone.');
    $stmt = mysqli_prepare($conn, 'UPDATE delivery_zones SET city=?,zone_name=? WHERE zone_id=?');
    mysqli_stmt_bind_param($stmt, 'ssi', $city, $zoneName, $zoneId); mysqli_stmt_execute($stmt);
    adminRedirect('operations.php', 'Delivery zone updated.');
}
if (adminVerifyPost() && isset($_POST['delete_zone'])) {
    $zoneId = (int)($_POST['zone_id'] ?? 0);
    if (!$zoneId) adminRedirect('operations.php', 'Choose a delivery zone.');
    $stmt = mysqli_prepare($conn, 'UPDATE delivery_zones SET is_active=0 WHERE zone_id=?');
    mysqli_stmt_bind_param($stmt, 'i', $zoneId); mysqli_stmt_execute($stmt);
    adminRedirect('operations.php', 'Delivery zone removed from new address selections. Existing saved addresses were preserved.');
}
if (adminVerifyPost() && isset($_POST['toggle_zone'])) {
    $zoneId = (int)($_POST['zone_id'] ?? 0); $active = (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0;
    if (!$zoneId) adminRedirect('operations.php', 'Choose a delivery zone.');
    $stmt = mysqli_prepare($conn, 'UPDATE delivery_zones SET is_active=? WHERE zone_id=?');
    mysqli_stmt_bind_param($stmt, 'ii', $active, $zoneId); mysqli_stmt_execute($stmt);
    adminRedirect('operations.php', $active ? 'Delivery zone activated.' : 'Delivery zone paused for new customer addresses.');
}
if (adminVerifyPost() && isset($_POST['rider_status'])) {
    $id = (int) ($_POST['rider_id'] ?? 0); $status = $_POST['availability'] ?? '';
    if ($id && in_array($status, array('offline','available','busy','on_break'), true)) {
        $stmt = mysqli_prepare($conn, 'UPDATE delivery_riders SET availability=? WHERE rider_id=?'); mysqli_stmt_bind_param($stmt, 'si', $status, $id); mysqli_stmt_execute($stmt);
    }
    adminRedirect('operations.php', 'Rider availability updated.');
}
if (adminVerifyPost() && isset($_POST['toggle_rider_active'])) {
    $id = (int) ($_POST['rider_id'] ?? 0); $active = (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0;
    if (!$id) adminRedirect('operations.php', 'Choose a rider.');
    if (!$active) {
        $assigned = adminRows("SELECT assignment_id FROM order_delivery_assignments WHERE rider_id=" . $id . " AND is_current=1 AND status NOT IN ('delivered','failed','reassigned') LIMIT 1");
        if ($assigned) adminRedirect('operations.php', 'This rider has an active delivery and cannot be deactivated yet.');
    }
    $availability = $active ? 'offline' : 'offline';
    $stmt = mysqli_prepare($conn, 'UPDATE delivery_riders SET is_active=?,availability=? WHERE rider_id=?');
    mysqli_stmt_bind_param($stmt, 'isi', $active, $availability, $id); mysqli_stmt_execute($stmt);
    adminRedirect('operations.php', $active ? 'Rider account activated. Set availability to Available when they are ready for dispatch.' : 'Rider account deactivated. Delivery history was preserved.');
}
if (adminVerifyPost() && isset($_POST['edit_rider'])) {
    $id = (int)($_POST['rider_id'] ?? 0); $name = trim($_POST['name'] ?? ''); $phone = trim($_POST['phone'] ?? ''); $vehicle = trim($_POST['vehicle_type'] ?? ''); $zones = trim($_POST['delivery_zone'] ?? '');
    if (!$id || $name === '' || $phone === '' || strlen($name) > 100 || strlen($phone) > 30 || strlen($vehicle) > 60 || strlen($zones) > 1000) adminRedirect('operations.php', 'Enter valid rider details.');
    $stmt = mysqli_prepare($conn, 'UPDATE delivery_riders SET name=?,phone=?,vehicle_type=?,delivery_zone=? WHERE rider_id=? AND is_active=1');
    mysqli_stmt_bind_param($stmt, 'ssssi', $name, $phone, $vehicle, $zones, $id); mysqli_stmt_execute($stmt);
    adminRedirect('operations.php', 'Rider details updated.');
}
if (adminVerifyPost() && isset($_POST['delete_rider'])) {
    $id = (int)($_POST['rider_id'] ?? 0);
    if (!$id) adminRedirect('operations.php', 'Choose a rider.');
    $active = adminRows("SELECT assignment_id FROM order_delivery_assignments WHERE rider_id=" . $id . " AND is_current=1 AND status NOT IN ('delivered','failed','reassigned') LIMIT 1");
    if ($active) adminRedirect('operations.php', 'This rider has an active delivery. Reassign or complete it before deactivating the account.');
    $stmt = mysqli_prepare($conn, "UPDATE delivery_riders SET is_active=0,availability='offline' WHERE rider_id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id); mysqli_stmt_execute($stmt);
    adminRedirect('operations.php', 'Rider account deactivated. Delivery history was preserved.');
}
if (adminVerifyPost() && isset($_POST['assign_rider'])) {
    $orderId = (int) ($_POST['orderid'] ?? 0); $riderId = (int) ($_POST['rider_id'] ?? 0);
    if (!$orderId || !$riderId || !mysqli_begin_transaction($conn)) adminRedirect('operations.php', 'Choose an order and an available rider.');
    $rider = mysqli_prepare($conn, "SELECT rider_id FROM delivery_riders WHERE rider_id=? AND is_active=1 AND availability='available' FOR UPDATE"); mysqli_stmt_bind_param($rider, 'i', $riderId); mysqli_stmt_execute($rider);
    $eligible = mysqli_fetch_assoc(mysqli_stmt_get_result($rider));
    $order = mysqli_prepare($conn, "SELECT status FROM order_tracking WHERE orderid=? FOR UPDATE"); mysqli_stmt_bind_param($order, 'i', $orderId); mysqli_stmt_execute($order);
    $orderRow = mysqli_fetch_assoc(mysqli_stmt_get_result($order));
    $ok = $eligible && $orderRow && $orderRow['status'] === 'ready_for_pickup';
    if ($ok) {
        $close = mysqli_prepare($conn, 'UPDATE order_delivery_assignments SET is_current=0,status=\'reassigned\' WHERE orderid=? AND is_current=1'); mysqli_stmt_bind_param($close, 'i', $orderId); $ok = mysqli_stmt_execute($close);
        $assign = mysqli_prepare($conn, 'INSERT INTO order_delivery_assignments(orderid,rider_id,assigned_by) VALUES(?,?,?)'); $actor = $_SESSION['admin_email']; mysqli_stmt_bind_param($assign, 'iis', $orderId, $riderId, $actor); $ok = $ok && mysqli_stmt_execute($assign);
        $busy = mysqli_prepare($conn, "UPDATE delivery_riders SET availability='busy' WHERE rider_id=?"); mysqli_stmt_bind_param($busy, 'i', $riderId); $ok = $ok && mysqli_stmt_execute($busy);
        $ok = $ok && orderAudit($conn, $orderId, 'admin', $actor, 'rider_assigned', null, (string)$riderId, 'Delivery rider assigned');
    }
    $ok ? mysqli_commit($conn) : mysqli_rollback($conn);
    if (!$ok) adminRedirect('operations.php', 'Only ready-for-pickup orders can be assigned to an available rider.');
    $transition = orderTransition($orderId, 'rider_assigned', 'admin', $_SESSION['admin_email'], 'Rider assigned');
    adminRedirect('operations.php', $transition[1]);
}

$riders = adminRows('SELECT r.*, COUNT(a.assignment_id) active_assignments FROM delivery_riders r LEFT JOIN order_delivery_assignments a ON a.rider_id=r.rider_id AND a.is_current=1 AND a.status NOT IN (\'delivered\',\'failed\') GROUP BY r.rider_id ORDER BY r.is_active DESC,r.name');
$zones = adminRows('SELECT zone_id,city,zone_name,is_active FROM delivery_zones ORDER BY city,zone_name');
$readyOrders = adminRows("SELECT o.orderid,o.name,o.address,f.total_amount FROM orders o JOIN order_tracking t ON t.orderid=o.orderid LEFT JOIN order_financials f ON f.orderid=o.orderid WHERE t.status='ready_for_pickup' ORDER BY t.updated_at");
$assignments = adminRows("SELECT a.*,r.name rider_name,o.name customer_name FROM order_delivery_assignments a JOIN delivery_riders r ON r.rider_id=a.rider_id JOIN orders o ON o.orderid=a.orderid WHERE a.is_current=1 ORDER BY a.assigned_at DESC");
$adminTitle='Delivery operations'; $adminPage='operations'; $adminStyles=array('operations-tabs.css'); include __DIR__ . '/includes/header.php';
?>
<section class="admin-card"><div class="admin-toolbar"><div><h2>Rider directory</h2><p class="admin-muted">Riders receive separate Foodera credentials and can see only their own assigned deliveries.</p></div></div><form method="post" class="admin-form admin-form-grid"><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><label>Name<input name="name" maxlength="100" required></label><label>Phone<input name="phone" maxlength="30" required></label><label>Email<input type="email" name="email" maxlength="100" required></label><label>Temporary password<input type="password" name="password" minlength="8" required></label><label>Vehicle<input name="vehicle_type" maxlength="60" placeholder="Bike, scooter, car"></label><label>Delivery zones<select name="delivery_zone_ids[]" multiple size="5" class="admin-multi-select" aria-describedby="rider-zone-help"><?php foreach($zones as $zone){ ?><option value="<?php echo (int)$zone['zone_id']; ?>"><?php echo adminEscape($zone['city'] . ' — ' . $zone['zone_name']); ?></option><?php } ?></select><small id="rider-zone-help" class="admin-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple zones. Leave empty for all zones.</small></label><button class="admin-btn" name="add_rider"><i class="bi bi-person-plus"></i>Create rider account</button></form><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Rider</th><th>Vehicle / zones</th><th>Current workload</th><th>Availability</th></tr></thead><tbody><?php foreach($riders as $rider){ ?><tr><td><strong><?php echo adminEscape($rider['name']); ?></strong><br><span class="admin-muted"><?php echo adminEscape($rider['email'] ?: $rider['phone']); ?></span></td><td><?php echo adminEscape($rider['vehicle_type'] ?: 'Not set'); ?><br><span class="admin-muted"><?php echo adminEscape($rider['delivery_zone'] ?: 'All zones'); ?></span></td><td><?php echo (int)$rider['active_assignments']; ?> active</td><td><form method="post"><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="rider_id" value="<?php echo (int)$rider['rider_id']; ?>"><select name="availability"><?php foreach(array('offline','available','busy','on_break') as $availability){ ?><option value="<?php echo $availability; ?>"<?php echo $rider['availability']===$availability?' selected':''; ?>><?php echo ucwords(str_replace('_',' ',$availability)); ?></option><?php } ?></select><button class="admin-icon-button" name="rider_status" title="Save availability"><i class="bi bi-save"></i></button></form></td></tr><?php } ?></tbody></table></div></section>
<section class="admin-card"><div class="admin-toolbar"><div><h2>Delivery cities and zones</h2><p class="admin-muted">Manage the areas where Foodera delivers. Removing a zone only hides it from new customer addresses.</p></div></div><form method="post" class="admin-form admin-form-grid"><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><label>City<input name="city" maxlength="100" placeholder="Kolkata" required></label><label>Delivery zone<input name="zone_name" maxlength="120" placeholder="Salt Lake" required></label><button class="admin-btn" name="add_zone"><i class="bi bi-plus-circle"></i>Add delivery zone</button></form><?php if($zones){ ?><div class="admin-table-wrap"><table class="admin-table admin-zone-table"><thead><tr><th>City</th><th>Delivery zone</th><th>Availability</th><th class="admin-zone-actions">Actions</th></tr></thead><tbody><?php foreach($zones as $zone){ ?><tr><td><strong><?php echo adminEscape($zone['city']); ?></strong></td><td><?php echo adminEscape($zone['zone_name']); ?></td><td><span class="admin-badge live">Available</span></td><td><div class="admin-row-actions"><button type="button" class="admin-icon-button" data-zone-edit="<?php echo (int)$zone['zone_id']; ?>" data-zone-city="<?php echo adminEscape($zone['city']); ?>" data-zone-name="<?php echo adminEscape($zone['zone_name']); ?>" title="Edit delivery zone" aria-label="Edit <?php echo adminEscape($zone['city'] . ' ' . $zone['zone_name']); ?>"><i class="bi bi-pencil-square"></i></button><form method="post" onsubmit="return confirm('Remove this zone from new customer address selections?')"><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="zone_id" value="<?php echo (int)$zone['zone_id']; ?>"><button class="admin-icon-button admin-danger-icon" name="delete_zone" title="Remove delivery zone" aria-label="Remove <?php echo adminEscape($zone['city'] . ' ' . $zone['zone_name']); ?>"><i class="bi bi-trash3"></i></button></form></div></td></tr><?php } ?></tbody></table></div><dialog class="admin-zone-dialog" id="zone-editor"><form method="post"><div class="admin-zone-dialog-head"><h3>Edit delivery zone</h3><button type="button" class="admin-icon-button" data-zone-close aria-label="Close"><i class="bi bi-x-lg"></i></button></div><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="zone_id" id="zone-editor-id"><label>City<input name="city" id="zone-editor-city" maxlength="100" required></label><label>Delivery zone<input name="zone_name" id="zone-editor-name" maxlength="120" required></label><div class="admin-row-actions"><button class="admin-btn" name="edit_zone">Save changes</button><button type="button" class="admin-btn secondary" data-zone-close>Cancel</button></div></form></dialog><?php } else { ?><p class="admin-muted">No delivery zones yet. Add one before customers can choose a city and zone.</p><?php } ?></section>
<section class="admin-card"><div class="admin-toolbar"><div><h2>Dispatch queue</h2><p class="admin-muted">Assign a rider after the kitchen marks an order ready for pickup.</p></div></div><?php if(!$readyOrders){ ?><p class="admin-muted">No orders are ready for rider assignment.</p><?php } else { ?><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Order</th><th>Customer / address</th><th>Total</th><th>Assign rider</th></tr></thead><tbody><?php foreach($readyOrders as $order){ ?><tr><td>#<?php echo (int)$order['orderid']; ?></td><td><strong><?php echo adminEscape($order['name']); ?></strong><br><span class="admin-muted"><?php echo adminEscape($order['address']); ?></span></td><td>Rs <?php echo number_format((float)$order['total_amount']); ?></td><td><form method="post"><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="orderid" value="<?php echo (int)$order['orderid']; ?>"><select name="rider_id" required><option value="">Choose available rider</option><?php foreach($riders as $rider){ if($rider['availability']==='available' && $rider['is_active']) { ?><option value="<?php echo (int)$rider['rider_id']; ?>"><?php echo adminEscape($rider['name']); ?></option><?php }} ?></select><button class="admin-btn" name="assign_rider">Assign</button></form></td></tr><?php } ?></tbody></table></div><?php } ?></section>
<section class="admin-card"><h2>Active delivery assignments</h2><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Order</th><th>Rider</th><th>Status</th><th>Assigned</th></tr></thead><tbody><?php foreach($assignments as $assignment){ ?><tr><td>#<?php echo (int)$assignment['orderid']; ?> · <?php echo adminEscape($assignment['customer_name']); ?></td><td><?php echo adminEscape($assignment['rider_name']); ?></td><td><span class="admin-badge live"><?php echo adminEscape(ucwords(str_replace('_',' ',$assignment['status']))); ?></span></td><td><?php echo adminEscape($assignment['assigned_at']); ?></td></tr><?php } ?></tbody></table></div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
(() => {
  const dialog = document.querySelector('#zone-editor');
  if (!dialog) return;
  document.querySelectorAll('[data-zone-edit]').forEach(button => button.addEventListener('click', () => {
    document.querySelector('#zone-editor-id').value = button.dataset.zoneEdit;
    document.querySelector('#zone-editor-city').value = button.dataset.zoneCity;
    document.querySelector('#zone-editor-name').value = button.dataset.zoneName;
    dialog.showModal();
  }));
  document.querySelectorAll('[data-zone-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
})();
(() => {
  document.querySelectorAll('.admin-multi-select').forEach(select => {
    const activeZoneIds = new Set(<?php echo json_encode(array_map('intval', array_column(array_filter($zones, function($zone) { return (int)$zone['is_active'] === 1; }), 'zone_id'))); ?>);
    [...select.options].forEach(option => { if (!activeZoneIds.has(Number(option.value))) option.remove(); });
    const picker = document.createElement('div');
    picker.className = 'admin-zone-picker';
    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'admin-zone-picker-toggle';
    const panel = document.createElement('div');
    panel.className = 'admin-zone-picker-panel';
    const search = document.createElement('input');
    search.type = 'search'; search.placeholder = 'Search city or zone'; search.setAttribute('aria-label', 'Search delivery zones');
    const list = document.createElement('div'); list.className = 'admin-zone-picker-options';
    const refresh = () => { const selected = [...select.options].filter(option => option.selected).map(option => option.textContent); toggle.innerHTML = '<i class="bi bi-search"></i> ' + (selected.length ? selected.length + ' zone' + (selected.length > 1 ? 's' : '') + ' selected' : 'Search and select zones') + ' <i class="bi bi-chevron-down"></i>'; };
    [...select.options].forEach(option => { const optionRow = document.createElement('div'); const check = document.createElement('input'); const text = document.createElement('span'); check.type = 'checkbox'; check.checked = option.selected; check.value = option.value; text.textContent = option.textContent; optionRow.append(check, text); check.addEventListener('change', () => { option.selected = check.checked; refresh(); }); optionRow.addEventListener('click', event => { if (event.target !== check) check.click(); }); list.append(optionRow); });
    toggle.addEventListener('click', () => { picker.classList.toggle('is-open'); if (picker.classList.contains('is-open')) search.focus(); });
    search.addEventListener('input', () => { const term = search.value.toLowerCase(); list.querySelectorAll('div').forEach(optionRow => optionRow.hidden = !optionRow.textContent.toLowerCase().includes(term)); });
    panel.append(search, list); picker.append(toggle, panel); select.hidden = true; select.after(picker); refresh();
    document.addEventListener('click', event => { if (!picker.contains(event.target)) picker.classList.remove('is-open'); });
  });
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const table = document.querySelector('.admin-zone-table');
  const zones = <?php echo json_encode($zones, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>;
  if (!table || !table.tBodies[0]) return;
  [...table.tBodies[0].rows].forEach((row, index) => {
    const zone = zones[index]; if (!zone) return;
    const status = row.cells[2];
    status.innerHTML = '<span class="admin-badge ' + (Number(zone.is_active) ? 'live' : 'muted') + '">' + (Number(zone.is_active) ? 'Active' : 'Inactive') + '</span>';
  });
});
</script>
<script>
/* Reliable server-data fallback for rider row actions. */
document.addEventListener('DOMContentLoaded', () => {
  const table = document.querySelector('.admin-table');
  const dialog = document.querySelector('#rider-editor');
  const riders = <?php echo json_encode($riders, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>;
  if (!table || !dialog || !table.tBodies[0]) return;
  if (table.tHead.rows[0].cells.length === 4) { const th = document.createElement('th'); th.textContent = 'Actions'; table.tHead.rows[0].append(th); }
  [...table.tBodies[0].rows].forEach((row, index) => {
    const rider = riders[index]; if (!rider) return;
    let cell = row.cells[4]; if (!cell) { cell = document.createElement('td'); row.append(cell); }
    cell.replaceChildren();
    const actions = document.createElement('div'); actions.className = 'admin-row-actions';
    const toggle = document.createElement('form'); toggle.method = 'post'; toggle.className = 'admin-zone-toggle-form';
    toggle.innerHTML = '<input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="rider_id" value="' + rider.rider_id + '"><input type="hidden" name="is_active" value="0"><label class="admin-toggle" title="' + (Number(rider.is_active) ? 'Deactivate rider account' : 'Activate rider account') + '"><input type="checkbox" name="is_active" value="1" aria-label="' + (Number(rider.is_active) ? 'Deactivate rider account' : 'Activate rider account') + '"' + (Number(rider.is_active) ? ' checked' : '') + '><span aria-hidden="true"></span></label><button type="submit" name="toggle_rider_active" class="admin-sr-only">Save</button>';
    toggle.querySelector('input[type="checkbox"]').addEventListener('change', () => toggle.requestSubmit());
    const edit = document.createElement('button'); edit.type = 'button'; edit.className = 'admin-icon-button'; edit.title = 'Edit rider'; edit.setAttribute('aria-label', 'Edit ' + rider.name); edit.innerHTML = '<i class="bi bi-pencil-square"></i>';
    edit.addEventListener('click', () => { document.querySelector('#rider-editor-id').value = rider.rider_id; document.querySelector('#rider-editor-name').value = rider.name || ''; document.querySelector('#rider-editor-phone').value = rider.phone || ''; document.querySelector('#rider-editor-vehicle').value = rider.vehicle_type || ''; document.querySelector('#rider-editor-zones').value = rider.delivery_zone || ''; dialog.showModal(); });
    const remove = document.createElement('form'); remove.method = 'post'; remove.innerHTML = '<input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="rider_id" value="' + rider.rider_id + '"><button class="admin-icon-button admin-danger-icon" name="delete_rider" title="Deactivate rider" aria-label="Deactivate rider"><i class="bi bi-trash3"></i></button>';
    remove.addEventListener('submit', event => { if (!confirm('Deactivate this rider? Their delivery history will be kept.')) event.preventDefault(); });
    actions.append(toggle, edit, remove); cell.append(actions);
  });
});
</script>
<dialog class="admin-zone-dialog" id="rider-editor"><form method="post"><div class="admin-zone-dialog-head"><h3>Edit rider</h3><button type="button" class="admin-icon-button" data-rider-close aria-label="Close"><i class="bi bi-x-lg"></i></button></div><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="rider_id" id="rider-editor-id"><label>Name<input name="name" id="rider-editor-name" maxlength="100" required></label><label>Phone<input name="phone" id="rider-editor-phone" maxlength="30" required></label><label>Vehicle<input name="vehicle_type" id="rider-editor-vehicle" maxlength="60"></label><label>Delivery zones<input name="delivery_zone" id="rider-editor-zones" maxlength="1000" placeholder="Kolkata - Salt Lake, Kolkata - New Town"></label><div class="admin-row-actions"><button class="admin-btn" name="edit_rider">Save changes</button><button type="button" class="admin-btn secondary" data-rider-close>Cancel</button></div></form></dialog>
<script>
(() => {
  /* Superseded by the DOMContentLoaded action builder above. */
  return;
  const riderData = <?php echo json_encode($riders, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>;
  const riderTable = document.querySelector('.admin-table');
  const dialog = document.querySelector('#rider-editor');
  if (!riderTable || !dialog) return;
  const head = document.createElement('th'); head.textContent = 'Actions'; head.className = 'admin-zone-actions'; riderTable.tHead.rows[0].append(head);
  riderTable.tBodies[0].rows.forEach((row, index) => {
    const id = row.querySelector('input[name="rider_id"]')?.value || riderData[index]?.rider_id;
    if (!id) return;
    const columns = row.cells; const identity = columns[0].innerText.split('\n'); const vehicleZones = columns[1].innerText.split('\n'); const rider = riderData.find(entry => Number(entry.rider_id) === Number(id)) || {};
    const cell = document.createElement('td'); const actions = document.createElement('div'); actions.className = 'admin-row-actions';
    const edit = document.createElement('button'); edit.type = 'button'; edit.className = 'admin-icon-button'; edit.title = 'Edit rider'; edit.setAttribute('aria-label', 'Edit ' + identity[0]); edit.innerHTML = '<i class="bi bi-pencil-square"></i>';
    edit.addEventListener('click', () => { document.querySelector('#rider-editor-id').value = id; document.querySelector('#rider-editor-name').value = rider.name || identity[0] || ''; document.querySelector('#rider-editor-phone').value = rider.phone || ''; document.querySelector('#rider-editor-vehicle').value = rider.vehicle_type || ''; document.querySelector('#rider-editor-zones').value = rider.delivery_zone || ''; dialog.showModal(); });
    const remove = document.createElement('form'); remove.method = 'post'; remove.innerHTML = '<input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="rider_id" value="' + id + '"><button class="admin-icon-button admin-danger-icon" name="delete_rider" title="Deactivate rider" aria-label="Deactivate ' + identity[0].replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '"><i class="bi bi-trash3"></i></button>';
    remove.addEventListener('submit', event => { if (!confirm('Deactivate this rider? Their delivery history will be kept.')) event.preventDefault(); }); actions.append(edit, remove); cell.append(actions); row.append(cell);
  });
  document.querySelectorAll('[data-rider-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
  const zoneHelp = document.querySelector('#rider-zone-help');
  if (zoneHelp) zoneHelp.textContent = 'Search and tick one or more zones. Leave empty for all zones.';
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const cards = [...document.querySelectorAll('.admin-content > .admin-card')];
  const findCard = title => cards.find(card => (card.querySelector('h2')?.textContent || '').trim() === title);
  const panels = [
    ['riders', 'Rider directory', 'Riders'],
    ['zones', 'Delivery cities and zones', 'Zones'],
    ['dispatch', 'Dispatch queue', 'Dispatch queue'],
    ['assignments', 'Active delivery assignments', 'Assignments']
  ].map(([id, title, label]) => ({ id, label, element: findCard(title) })).filter(panel => panel.element);
  if (!panels.length) return;

  const tabs = document.createElement('nav');
  tabs.className = 'operations-tabs';
  tabs.setAttribute('aria-label', 'Delivery operation sections');
  const getCount = panel => panel.element.querySelectorAll('tbody tr').length;
  const show = id => {
    panels.forEach(panel => {
      const active = panel.id === id;
      panel.element.hidden = !active;
      panel.button.classList.toggle('is-active', active);
      panel.button.setAttribute('aria-selected', String(active));
    });
    try { sessionStorage.setItem('foodera-operations-tab', id); } catch (error) {}
  };
  panels.forEach(panel => {
    panel.element.classList.add('operations-tab-panel');
    const button = document.createElement('button');
    button.type = 'button';
    button.setAttribute('role', 'tab');
    button.innerHTML = panel.label + '<span class="operations-tabs-count">' + getCount(panel) + '</span>';
    button.addEventListener('click', () => show(panel.id));
    tabs.append(button);
    panel.button = button;
  });
  const controlLink = document.createElement('a');
  controlLink.href = 'delivery-mis.php'; controlLink.className = 'admin-btn secondary operations-control-link';
  controlLink.innerHTML = '<i class="bi bi-cash-stack"></i> COD &amp; delivery resolutions';
  panels[0].element.before(tabs);
  tabs.after(controlLink);
  let saved = 'riders';
  try { saved = sessionStorage.getItem('foodera-operations-tab') || saved; } catch (error) {}
  show(panels.some(panel => panel.id === saved) ? saved : panels[0].id);
});
</script>
