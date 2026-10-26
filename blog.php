<?php
require_once __DIR__ . '/app-functions.php';
require_once __DIR__ . '/includes/site-content.php';
session_start();
if (empty($_SESSION['blog_csrf'])) $_SESSION['blog_csrf'] = bin2hex(random_bytes(32));
$postId = max(0, (int) ($_GET['post'] ?? 0));
$post = $postId ? getBlogPost($postId) : null;
$isBlogMember = !empty($_SESSION['user']) && !empty($_SESSION['email']);
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target = max(0, (int) ($_POST['post_id'] ?? 0));
    $targetPost = $target ? getBlogPost($target) : null;
    if (!$targetPost || !hash_equals($_SESSION['blog_csrf'], $_POST['csrf'] ?? '')) {
        $notice = 'Request could not be completed.';
    } elseif (($_POST['action'] ?? '') === 'comment') {
        if (!$isBlogMember) {
            $notice = 'Please sign in to comment on this story.';
        } else {
        $name = trim($_SESSION['user']);
        $email = trim($_SESSION['email']);
        $comment = trim($_POST['comment'] ?? '');
        if ($name === '' || strlen($name) > 80 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190 || $comment === '' || strlen($comment) > 2000) {
            $notice = 'Enter a name, valid email, and a comment under 2000 characters.';
        } elseif (!blogInteractionTablesReady()) {
            $notice = 'Comments are temporarily unavailable.';
        } else {
            $notice = runQuery('INSERT INTO blog_comments (post_id, name, email, comment) VALUES (?, ?, ?, ?)', 'isss', $target, $name, $email, $comment)
                ? 'Thanks. Your comment is awaiting approval.' : 'Could not submit your comment.';
        }
        }
    } elseif (($_POST['action'] ?? '') === 'like') {
        if (!$isBlogMember) {
            $notice = 'Please sign in to like this story.';
        } elseif (!blogInteractionTablesReady()) {
            $notice = 'Likes are temporarily unavailable.';
        } else {
            $hash = hash('sha256', strtolower(trim($_SESSION['email'])));
            if (blogHasLiked($target, $_SESSION['email'])) {
                $notice = 'You have already liked this post.';
            } else {
                $notice = runQuery('INSERT INTO blog_likes (post_id, voter_hash) VALUES (?, ?)', 'is', $target, $hash) ? 'Thanks for liking this post.' : 'Could not record your like.';
            }
        }
    }
    $postId = $target;
    $post = $targetPost;
}
$pageTitle = $post ? $post['title'] . ' | Foodera Blog' : 'Foodera Blog';
$baseUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$pageCanonical = $baseUrl . '/blog.php' . ($post ? '?post=' . $postId : '');
$pageMetaDescription = $post ? $post['excerpt'] : 'Food, ordering tips, and news from the Foodera community.';
$pageMetaImage = $post && !empty($post['image_path']) ? $baseUrl . '/' . ltrim($post['image_path'], '/') : '';
$activePage = 'blog';
$bodyClass = 'blog-page';
$pageStyles = array('css/menu.css', 'css/blog-pro.css');
include __DIR__ . '/includes/public-header.php';
function blogEscape($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
$hasLikedPost = $post && $isBlogMember ? blogHasLiked($postId, $_SESSION['email']) : false;
$relatedPosts = array();
if ($post) {
    $publishedPosts = getBlogPosts();
    $candidates = $publishedPosts ? mysqli_fetch_all($publishedPosts, MYSQLI_ASSOC) : array();
    $sameCategoryPosts = array();
    $otherPosts = array();

    foreach ($candidates as $candidate) {
        if ((int) $candidate['post_id'] === $postId) continue;
        if ($candidate['category'] === $post['category']) {
            $sameCategoryPosts[] = $candidate;
        } else {
            $otherPosts[] = $candidate;
        }
    }
    $relatedPosts = array_slice(array_merge($sameCategoryPosts, $otherPosts), 0, 4);
}
?>
<main class="foodera-blog">
<?php if ($notice !== '') { ?><p class="blog-notice" role="status"><?php echo blogEscape($notice); ?></p><?php } ?>
<?php if ($post) { ?>
    <div class="blog-article-layout">
        <div class="blog-reading-column">
            <article class="blog-article">
                <a class="blog-back" href="blog.php"><i class="fas fa-arrow-left"></i> All stories</a>
                <p class="blog-kicker"><?php echo blogEscape($post['category']); ?> &middot; <?php echo date('M j, Y', strtotime($post['created_at'])); ?></p>
                <h1><?php echo blogEscape($post['title']); ?></h1>
                <p class="blog-lead"><?php echo blogEscape($post['excerpt']); ?></p>
                <img class="blog-article-image" src="<?php echo blogEscape($post['image_path']); ?>" alt="<?php echo blogEscape($post['title']); ?>">
                <div class="blog-prose"><?php echo renderBlogContent($post['content']); ?></div>
                <p class="blog-byline">By <?php echo blogEscape($post['author']); ?></p>
                <div class="blog-actions">
                    <?php if ($isBlogMember) { ?><form method="post" action="blog.php?post=<?php echo $postId; ?>">
                        <input type="hidden" name="csrf" value="<?php echo blogEscape($_SESSION['blog_csrf']); ?>">
                        <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
                        <input type="hidden" name="action" value="like">
                        <button type="submit"<?php echo $hasLikedPost ? ' class="is-liked" disabled aria-label="You already liked this post"' : ''; ?>><i class="<?php echo $hasLikedPost ? 'fas' : 'far'; ?> fa-heart"></i> <?php echo $hasLikedPost ? 'Liked' : 'Like'; ?> (<?php echo blogCount('blog_likes', $postId); ?>)</button>
                    </form><?php } else { ?><a class="blog-login-action" href="login.php">Sign in to like (<?php echo blogCount('blog_likes', $postId); ?>)</a><?php } ?>
                    <button type="button" id="blog-share" data-url="<?php echo blogEscape('blog.php?post=' . $postId); ?>"><i class="fas fa-share-alt"></i> Share</button>
                </div>
            </article>
            <section class="blog-comments" id="comments">
                <h2>Comments (<?php echo blogCount('blog_comments', $postId); ?>)</h2>
                <?php $comments = getBlogComments($postId); if ($comments) { while ($comment = mysqli_fetch_assoc($comments)) { ?>
                    <article class="blog-comment"><strong><?php echo blogEscape($comment['name']); ?></strong><time><?php echo date('M j, Y', strtotime($comment['created_at'])); ?></time><p><?php echo nl2br(blogEscape($comment['comment'])); ?></p></article>
                <?php } } ?>
                <?php if ($isBlogMember) { ?><form method="post" action="blog.php?post=<?php echo $postId; ?>#comments" class="blog-comment-form">
                    <h3>Join the conversation</h3><p>Comments appear after review.</p>
                    <input type="hidden" name="csrf" value="<?php echo blogEscape($_SESSION['blog_csrf']); ?>">
                    <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
                    <input type="hidden" name="action" value="comment">
                    <p class="blog-comment-account">Commenting as <strong><?php echo blogEscape($_SESSION['user']); ?></strong>.</p>
                    <label>Comment<textarea name="comment" rows="5" maxlength="2000" required></textarea></label>
                    <button type="submit">Post comment</button>
                </form><?php } else { ?><div class="blog-auth-prompt"><i class="bi bi-chat-left-text" aria-hidden="true"></i><div><h3>Join the conversation</h3><p>Sign in to like this story or submit a comment for review.</p></div><a href="login.php">Sign in</a></div><?php } ?>
            </section>
        </div>
        <?php if ($relatedPosts) { ?><aside class="blog-related<?php echo count($relatedPosts) === 1 ? ' has-one-story' : ''; ?>" aria-labelledby="more-stories-heading">
            <div class="blog-related-heading"><p class="blog-kicker">Continue reading</p><h2 id="more-stories-heading">More from Foodera</h2><p><?php echo count($relatedPosts) === 1 ? 'A story picked for you.' : count($relatedPosts) . ' stories picked for you.'; ?></p></div>
            <div class="blog-related-list"><?php foreach ($relatedPosts as $relatedPost) { ?><a class="blog-related-item" href="blog.php?post=<?php echo (int) $relatedPost['post_id']; ?>"><img src="<?php echo blogEscape($relatedPost['image_path']); ?>" alt="<?php echo blogEscape($relatedPost['title']); ?>"><span class="blog-related-copy"><span class="blog-related-category"><?php echo blogEscape($relatedPost['category']); ?></span><strong><?php echo blogEscape($relatedPost['title']); ?></strong><time><?php echo date('d M Y', strtotime($relatedPost['created_at'])); ?></time></span></a><?php } ?></div>
            <a class="blog-related-all" href="blog.php">View all stories <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </aside><?php } ?>
    </div>
<?php } else { ?>
    <?php $postsResult = getBlogPosts(); $posts = $postsResult ? mysqli_fetch_all($postsResult, MYSQLI_ASSOC) : array(); $categories = array_filter(getBlogCategories(true), function ($category) { return (int) $category['post_count'] > 0; }); $visibleCategoryNames = array_map(function ($category) { return $category['name']; }, $categories); $posts = array_values(array_filter($posts, function ($item) use ($visibleCategoryNames) { return in_array($item['category'], $visibleCategoryNames, true); })); $featured = null; foreach ($posts as $candidate) { if (!empty($candidate['is_featured'])) { $featured = $candidate; break; } } if (!$featured && $posts) $featured = $posts[0]; ?>
    <header class="blog-intro blog-publication-intro"><p class="blog-kicker">Foodera journal</p><h1><?php echo siteEscape(siteSetting('blog.title')); ?></h1><p><?php echo siteEscape(siteSetting('blog.intro')); ?></p></header>
    <?php if ($featured) { ?><section class="blog-featured"><a class="blog-featured-image" href="blog.php?post=<?php echo (int) $featured['post_id']; ?>"><img src="<?php echo blogEscape($featured['image_path']); ?>" alt="<?php echo blogEscape($featured['title']); ?>"></a><div class="blog-featured-copy"><p class="blog-kicker">Featured &middot; <?php echo blogEscape($featured['category']); ?></p><h2><a href="blog.php?post=<?php echo (int) $featured['post_id']; ?>"><?php echo blogEscape($featured['title']); ?></a></h2><p><?php echo blogEscape($featured['excerpt']); ?></p><div class="blog-story-meta"><span><i class="bi bi-person" aria-hidden="true"></i><?php echo blogEscape($featured['author']); ?></span><span><i class="bi bi-calendar3" aria-hidden="true"></i><?php echo date('d M Y', strtotime($featured['created_at'])); ?></span></div><a class="blog-featured-read" href="blog.php?post=<?php echo (int) $featured['post_id']; ?>">Read featured story <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div></section><?php } ?>
    <?php if ($posts) { ?><section class="blog-feed"><div class="blog-feed-heading"><div><p class="blog-kicker">Latest stories</p><h2>Fresh from Foodera</h2></div><label class="blog-search"><i class="bi bi-search" aria-hidden="true"></i><input id="blog-search" type="search" placeholder="Search stories" aria-label="Search stories"></label></div><div class="blog-category-filters" aria-label="Filter stories by category"><button type="button" class="is-active" data-category="all">All stories</button><?php foreach ($categories as $category) { ?><button type="button" data-category="<?php echo blogEscape(strtolower($category['name'])); ?>"><?php echo blogEscape($category['name']); ?></button><?php } ?></div><div class="blog-story-grid" id="blog-story-grid"><?php foreach ($posts as $item) { if ($featured && (int) $item['post_id'] === (int) $featured['post_id']) continue; ?><article class="blog-story-card" data-category="<?php echo blogEscape(strtolower($item['category'])); ?>" data-search="<?php echo blogEscape(strtolower($item['title'] . ' ' . $item['excerpt'] . ' ' . $item['category'])); ?>"><a class="blog-story-image" href="blog.php?post=<?php echo (int) $item['post_id']; ?>"><img src="<?php echo blogEscape($item['image_path']); ?>" alt="<?php echo blogEscape($item['title']); ?>"></a><div><p class="blog-kicker"><?php echo blogEscape($item['category']); ?></p><h3><a href="blog.php?post=<?php echo (int) $item['post_id']; ?>"><?php echo blogEscape($item['title']); ?></a></h3><p><?php echo blogEscape($item['excerpt']); ?></p><div class="blog-story-footer"><time><?php echo date('d M Y', strtotime($item['created_at'])); ?></time><a href="blog.php?post=<?php echo (int) $item['post_id']; ?>" aria-label="Read <?php echo blogEscape($item['title']); ?>">Read <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div></div></article><?php } ?></div><p class="blog-filter-empty" id="blog-filter-empty" hidden>No stories match this search.</p></section><?php } else { ?><p class="blog-empty">No stories are published yet. Check back soon.</p><?php } ?>
<?php } ?>
</main>
<script>
const blogShare = document.getElementById('blog-share');
if (blogShare) blogShare.addEventListener('click', async () => {
    const url = new URL(blogShare.dataset.url, location.href).href;
    if (navigator.share) {
        try { await navigator.share({title: document.title, url}); } catch (_) {}
    } else if (navigator.clipboard) {
        await navigator.clipboard.writeText(url);
        blogShare.textContent = 'Link copied';
    } else { window.prompt('Copy this link', url); }
});
const blogSearch = document.getElementById('blog-search');
const blogFilters = Array.from(document.querySelectorAll('.blog-category-filters [data-category]'));
const blogCards = Array.from(document.querySelectorAll('.blog-story-card'));
const blogEmpty = document.getElementById('blog-filter-empty');
let activeBlogCategory = 'all';
function filterBlogStories() {
    const query = (blogSearch ? blogSearch.value : '').trim().toLowerCase();
    let visible = 0;
    blogCards.forEach(function (card) {
        const matches = (activeBlogCategory === 'all' || card.dataset.category === activeBlogCategory) && (!query || card.dataset.search.includes(query));
        card.hidden = !matches;
        if (matches) visible++;
    });
    if (blogEmpty) blogEmpty.hidden = visible > 0;
}
if (blogSearch) blogSearch.addEventListener('input', filterBlogStories);
blogFilters.forEach(function (button) { button.addEventListener('click', function () { activeBlogCategory = button.dataset.category; blogFilters.forEach(function (filter) { filter.classList.toggle('is-active', filter === button); }); filterBlogStories(); }); });
</script>
<?php include __DIR__ . '/includes/public-footer.php'; ?>
