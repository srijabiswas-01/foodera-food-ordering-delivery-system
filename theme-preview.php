<?php
session_start();
$pageTitle = 'Foodera Theme Colors';
$activePage = '';
$pageStyles = array();
include('includes/public-header.php');

$colors = array(
    array('Light Background', '#f7f7f5'),
    array('Light Surface', '#ffffff'),
    array('Light Text', '#20242a'),
    array('Muted Text', '#666f7a'),
    array('Brand Primary Tomato', '#ff3d00'),
    array('Brand Hover', '#d93400'),
    array('Yellow Highlight', '#ffb703'),
    array('Secondary Dark', '#24262b'),
    array('Secondary Light', '#343842'),
    array('Accent Blue', '#2f86a6'),
    array('Dark Background', '#111318'),
    array('Dark Surface', '#1b1f27'),
    array('Dark Text', '#f4f6f8'),
    array('Dark Muted', '#b7bec8'),
    array('Dark Primary Tomato', '#ff5a2c'),
    array('Dark Hover Tomato', '#ff764f'),
    array('Dark Yellow Highlight', '#ffd166'),
    array('Dark Accent Blue', '#66c7df')
);
?>
<main class="theme-swatches">
    <h1>Foodera Theme Colors</h1>
    <p>These are the selected colors used by the public light and dark mode theme.</p>
    <div class="theme-grid">
        <?php foreach ($colors as $color) { ?>
            <div class="theme-card">
                <div class="theme-color" style="background: <?php echo $color[1]; ?>"></div>
                <div>
                    <strong><?php echo htmlspecialchars($color[0]); ?></strong>
                    <code><?php echo htmlspecialchars($color[1]); ?></code>
                </div>
            </div>
        <?php } ?>
    </div>
</main>
<?php include('includes/public-footer.php'); ?>
