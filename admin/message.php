<?php
require_once __DIR__ . '/includes/bootstrap.php'; adminRequireLogin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!adminVerifyPost()) adminRedirect('message.php', 'Request expired.');
    $id = (int) ($_POST['messageid'] ?? 0);
    if ($id > 0 && isset($_POST['delete'])) adminRedirect('message.php', deleteMessage($id) ? 'Message deleted.' : 'Could not delete message.');
}
$messages = adminRows('SELECT * FROM messages ORDER BY mdate DESC');
$adminTitle = 'Messages'; $adminPage = 'messages'; include __DIR__ . '/includes/header.php';
?>
<section class="admin-card"><h2>Customer messages</h2><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>From</th><th>Subject</th><th>Message</th><th>Date</th><th></th></tr></thead><tbody><?php foreach ($messages as $row) { ?><tr><td><?php echo adminEscape(trim($row['fname'] . ' ' . $row['lname'])); ?><br><a href="mailto:<?php echo adminEscape($row['email']); ?>"><?php echo adminEscape($row['email']); ?></a></td><td><?php echo adminEscape($row['subject']); ?></td><td><?php echo adminEscape($row['message']); ?></td><td><?php echo adminEscape($row['mdate']); ?></td><td><form method="post" onsubmit="return confirm('Delete this message?')"><input type="hidden" name="csrf" value="<?php echo adminEscape(adminToken()); ?>"><input type="hidden" name="messageid" value="<?php echo (int) $row['messageid']; ?>"><button class="admin-icon-button danger" name="delete" title="Delete message" aria-label="Delete message from <?php echo adminEscape(trim($row['fname'] . ' ' . $row['lname'])); ?>"><i class="bi bi-trash" aria-hidden="true"></i></button></form></td></tr><?php } ?></tbody></table><?php if (!$messages) { ?><p class="admin-muted">No messages yet.</p><?php } ?></div></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
