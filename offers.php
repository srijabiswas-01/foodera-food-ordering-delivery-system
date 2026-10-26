<?php
require_once __DIR__ . '/includes/offers.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$offers = offerPublicList();
$categories = offerCategories(true);
$pageTitle = 'Offers | Foodera'; $activePage = 'offers'; $bodyClass = 'offers-page'; $pageStyles = array('css/offers.css');
include __DIR__ . '/includes/public-header.php';
?>
<main class="offers-main">
    <section class="offers-intro">
        <p class="offers-kicker"><i class="bi bi-stars" aria-hidden="true"></i> Foodera offers</p>
        <h1>Good food, better value.</h1>
        <p>Explore active Foodera offers. Your cart checks the final eligibility automatically before checkout.</p>
        <a class="offers-primary-action" href="menu.php">Browse the menu <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </section>

    <?php if (!$offers) { ?>
        <section class="offers-empty"><i class="bi bi-tag" aria-hidden="true"></i><h2>No active offers right now</h2><p>Fresh deals will appear here as soon as they are published.</p><a href="menu.php">Explore menu</a></section>
    <?php } else { ?>
        <div class="offers-category-links" aria-label="Offer categories"><button type="button" class="active" data-offer-category="all">All offers</button><?php foreach ($categories as $category) { ?><button type="button" data-offer-category="<?php echo (int) $category['offer_category_id']; ?>"><?php echo offerEscape($category['name']); ?></button><?php } ?><button type="button" class="offers-filter-reset" data-offer-reset hidden><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset</button></div>
        <section class="offers-grid" id="all-offers">
            <?php foreach ($offers as $offer) { ?>
                <article class="offer-card" data-offer-category="<?php echo (int) $offer['offer_category_id']; ?>">
                    <?php if ($offer['image_path']) { ?><img src="<?php echo offerEscape($offer['image_path']); ?>" alt="<?php echo offerEscape($offer['title']); ?>"><?php } ?>
                    <div class="offer-card-body">
                        <div class="offer-card-top"><span><?php echo offerEscape($offer['category_name']); ?></span><strong><?php echo offerEscape(offerFormatValue($offer)); ?></strong></div>
                        <h2><?php echo offerEscape($offer['title']); ?></h2>
                        <p><?php echo offerEscape($offer['description']); ?></p>
                        <?php if ($offer['code']) { ?><div class="offer-code"><code><?php echo offerEscape($offer['code']); ?></code><button type="button" class="copy-offer-code" data-code="<?php echo offerEscape($offer['code']); ?>" aria-label="Copy offer code <?php echo offerEscape($offer['code']); ?>"><i class="bi bi-copy" aria-hidden="true"></i><span>Copy</span></button></div><?php } else { ?><p class="offer-auto"><i class="bi bi-lightning-charge-fill" aria-hidden="true"></i> Applied automatically when eligible</p><?php } ?>
                        <dl class="offer-rules"><div><dt>Minimum order</dt><dd>Rs <?php echo number_format((float) $offer['minimum_order'], 0); ?></dd></div><div><dt>Valid until</dt><dd><?php echo offerEscape(date('d M Y', strtotime($offer['ends_at']))); ?></dd></div></dl>
                        <details><summary>Offer terms</summary><p><?php echo nl2br(offerEscape($offer['terms'])); ?></p></details>
                        <a class="offer-use" href="menu.php">Order with this offer <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                </article>
            <?php } ?>
        </section>
        <p class="filter-empty-message" id="offers-filter-empty" hidden><i class="bi bi-search" aria-hidden="true"></i> No offers match this category right now. Try another category or reset the filters.</p>
        <nav class="content-pagination" id="offers-pagination" aria-label="Offers pagination"></nav>
    <?php } ?>
</main>
<script>
document.querySelectorAll('.copy-offer-code').forEach(function (button) {
    button.addEventListener('click', function () {
        navigator.clipboard.writeText(button.dataset.code).then(function () {
            button.querySelector('span').textContent = 'Copied';
            window.setTimeout(function () { button.querySelector('span').textContent = 'Copy'; }, 1600);
        });
    });
});

(function () {
    const cards = Array.from(document.querySelectorAll('.offer-card'));
    const tabs = Array.from(document.querySelectorAll('[data-offer-category]')).filter(function (item) { return item.tagName === 'BUTTON'; });
    const reset = document.querySelector('[data-offer-reset]');
    const pager = document.getElementById('offers-pagination');
    const empty = document.getElementById('offers-filter-empty');
    const pageSize = 6;
    let category = 'all';
    let page = 1;

    function render() {
        const visible = cards.filter(function (card) { return category === 'all' || card.dataset.offerCategory === category; });
        const pages = Math.max(1, Math.ceil(visible.length / pageSize));
        if (page > pages) page = pages;
        cards.forEach(function (card) { card.hidden = true; });
        visible.slice((page - 1) * pageSize, page * pageSize).forEach(function (card) { card.hidden = false; });
        empty.hidden = visible.length > 0;
        tabs.forEach(function (tab) { tab.classList.toggle('active', tab.dataset.offerCategory === category); });
        reset.hidden = category === 'all';
        const hasPagination = pages > 1;
        pager.hidden = !hasPagination;
        pager.innerHTML = hasPagination ? Array.from({ length: pages }, function (_, index) {
            const number = index + 1;
            return '<button type="button" class="' + (number === page ? 'active' : '') + '" data-page="' + number + '" aria-label="Page ' + number + '"' + (number === page ? ' aria-current="page"' : '') + '>' + number + '</button>';
        }).join('') : '';
    }

    tabs.forEach(function (tab) { tab.addEventListener('click', function () { category = tab.dataset.offerCategory; page = 1; render(); }); });
    reset.addEventListener('click', function () { category = 'all'; page = 1; render(); });
    pager.addEventListener('click', function (event) { const button = event.target.closest('[data-page]'); if (button) { page = Number(button.dataset.page); render(); } });
    render();
}());
</script>
<?php include __DIR__ . '/includes/public-footer.php'; ?>
