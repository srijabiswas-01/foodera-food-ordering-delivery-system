<?php
require_once(__DIR__ . '/config.php');

function dbConnect()
{
    mysqli_report(MYSQLI_REPORT_OFF);

    set_error_handler(function () {
        return true;
    });

    try {
        $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    } catch (mysqli_sql_exception $e) {
        restore_error_handler();
        return false;
    }

    restore_error_handler();

    if (!$conn) {
        return false;
    }

    mysqli_set_charset($conn, 'utf8mb4');
    return $conn;
}

function runQuery($sql, $types = '', ...$params)
{
    $conn = dbConnect();
    if (!$conn) {
        return false;
    }

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    return mysqli_stmt_execute($stmt);
}

function updateCustomer($data)
{
    return runQuery(
        'UPDATE customers SET user = ?, address = ?, phone = ? WHERE email = ?',
        'ssss',
        $data['user'],
        $data['address'],
        $data['phone'],
        $data['email']
    );
}

function getAllCustomer($email)
{
    $conn = dbConnect();
    if (!$conn) {
        return false;
    }

    $stmt = mysqli_prepare($conn, 'SELECT * FROM customers WHERE email = ?');
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

function login()
{
    $conn = dbConnect();
    if (!$conn) {
        return false;
    }

    $stmt = mysqli_prepare($conn, 'SELECT * FROM customers WHERE user = ? LIMIT 1');
    if (!$stmt) return false;
    $user = trim($_POST['user'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    mysqli_stmt_bind_param($stmt, 's', $user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    if (!$row || (array_key_exists('is_active', $row) && !(int) $row['is_active'])) return false;
    $valid = password_verify($password, $row['password']);
    if (!$valid && strlen($row['password']) < 50 && hash_equals($row['password'], $password)) $valid = true;
    if (!$valid) return false;
    if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
        runQuery('UPDATE customers SET password=? WHERE email=?', 'ss', password_hash($password, PASSWORD_DEFAULT), $row['email']);
    }
    mysqli_data_seek($result, 0);
    return $result;
}

function allcustomer()
{
    $conn = dbConnect();
    return $conn ? mysqli_query($conn, 'SELECT * FROM customers ORDER BY user') : false;
}

function deleteCustomer($email)
{
    return runQuery('DELETE FROM customers WHERE email = ?', 's', $email);
}

function loginadmin()
{
    $conn = dbConnect();
    if (!$conn) return false;
    $user = trim($_POST['user'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $stmt = mysqli_prepare($conn, 'SELECT * FROM admins WHERE user = ? LIMIT 1');
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 's', $user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    if (!$row) return false;
    $valid = password_verify($password, $row['password']);
    if (!$valid && strlen($row['password']) < 50 && hash_equals($row['password'], $password)) $valid = true;
    if (!$valid) return false;
    if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        runQuery('UPDATE admins SET password=? WHERE email=?', 'ss', $hash, $row['email']);
    }
    return $row;
}

function addNewAdmin($data)
{
    $conn = dbConnect();
    if (!$conn) return 'Database connection failed.';
    $user = trim($data['user'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = (string) ($data['password'] ?? '');
    if ($user === '' || strlen($user) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 50 || strlen($password) < 12) return 'Enter a user name, valid email, and password of at least 12 characters.';
    $address = trim($data['address'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $lock = mysqli_query($conn, "SELECT GET_LOCK('foodera_first_admin', 5) AS acquired");
    if (!$lock || (int) mysqli_fetch_assoc($lock)['acquired'] !== 1) return 'Admin setup is busy. Try again.';
    $count = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM admins');
    if (!$count || (int) mysqli_fetch_assoc($count)['total'] !== 0) {
        mysqli_query($conn, "SELECT RELEASE_LOCK('foodera_first_admin')");
        return 'Admin setup is closed.';
    }
    $stmt = mysqli_prepare($conn, 'INSERT INTO admins(user, address, email, password, phone) VALUES (?, ?, ?, ?, ?)');
    $ok = false;
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sssss', $user, $address, $email, $hash, $phone);
        $ok = mysqli_stmt_execute($stmt);
    }
    mysqli_query($conn, "SELECT RELEASE_LOCK('foodera_first_admin')");
    return $ok ? 'Admin account created. Sign in now.' : 'Could not create admin account.';
}

function addNewCustomer($data)
{
    $conn = dbConnect();
    if (!$conn) return 'Database connection failed.';
    $column = mysqli_query($conn, "SHOW COLUMNS FROM customers LIKE 'password'");
    $field = $column ? mysqli_fetch_assoc($column) : null;
    if (!$field || (preg_match('/varchar\((\d+)\)/i', $field['Type'], $match) && (int) $match[1] < 255)) return 'Run db/customer_profile_migration.sql before registering.';
    $name = trim($data['user'] ?? '');
    $email = trim($data['email'] ?? '');
    $address = trim($data['address'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $password = (string) ($data['password'] ?? '');
    if ($name === '' || strlen($name) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 50 || $address === '' || strlen($address) > 255 || strlen($password) < 8 || !preg_match('/^[0-9]{10}$/', $phone)) return 'Check your name, email, address, phone, and password (8 characters minimum).';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ok = runQuery(
        'INSERT INTO customers(user, address, email, password, phone) VALUES (?, ?, ?, ?, ?)',
        'sssss',
        $name, $address, $email, $hash, $phone
    );

    if ($ok && $address !== '') {
        runQuery('INSERT INTO customer_addresses (customer_email,label,address_line,is_default) VALUES (?,?,?,1)', 'sss', $email, 'Home', $address);
    }
    return $ok ? 'Added successfully' : 'Failed. Email may already exist.';
}

function getCustomerAddresses($email)
{
    $conn = dbConnect();
    if (!$conn) return array();
    $stmt = mysqli_prepare($conn, 'SELECT address_id,label,address_line,city,delivery_zone,is_default FROM customer_addresses WHERE customer_email=? ORDER BY is_default DESC,address_id DESC');
    if (!$stmt) return array();
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
}

function getCustomerProfile($email)
{
    $conn = dbConnect();
    if (!$conn) return null;
    $stmt = mysqli_prepare($conn, 'SELECT photo_path,dietary_preference,favorite_cuisines,allergies FROM customer_profiles WHERE customer_email=?');
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return $result ? mysqli_fetch_assoc($result) : null;
}

function addFood($data)
{
    $conn = dbConnect();
    if (!$conn) {
        return 'Connection failed';
    }

    if (!isset($_FILES['picture']) || $_FILES['picture']['error'] !== UPLOAD_ERR_OK) {
        return 'Please select a valid picture.';
    }

    $public_path = 'profilepic/' . basename($_FILES['picture']['name']);
    $target_file = __DIR__ . '/' . $public_path;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowedTypes = array('jpg', 'jpeg', 'png', 'gif', 'webp');

    if (!in_array($imageFileType, $allowedTypes, true)) {
        return 'Sorry, only JPG, JPEG, PNG, GIF & WEBP files are allowed.';
    }

    $stmt = mysqli_prepare($conn, 'INSERT INTO foods(id, name, description, price, picture, category) VALUES (?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'ississ', $data['id'], $data['name'], $data['description'], $data['price'], $public_path, $data['category']);

    if (!mysqli_stmt_execute($stmt)) {
        return 'Registration failed. Food ID may already exist.';
    }

    return move_uploaded_file($_FILES['picture']['tmp_name'], $target_file)
        ? 'Registration successfully completed.'
        : 'Food saved, but there was an error uploading your file.';
}

function getAllfood()
{
    $conn = dbConnect();
    return $conn ? mysqli_query($conn, 'SELECT * FROM foods ORDER BY id') : false;
}


function sendmessage($data)
{
    return runQuery(
        'INSERT INTO messages(fname, lname, email, subject, message) VALUES (?, ?, ?, ?, ?)',
        'sssss',
        $data['fname'],
        $data['lname'],
        $data['email'],
        $data['subject'],
        $data['message']
    );
}

function getAllMessage()
{
    $conn = dbConnect();
    return $conn ? mysqli_query($conn, 'SELECT * FROM messages ORDER BY mdate DESC') : false;
}

function deleteMessage($messageid)
{
    return runQuery('DELETE FROM messages WHERE messageid = ?', 'i', $messageid);
}

function ensureBlogPostsTable()
{
    return runQuery(
        "CREATE TABLE IF NOT EXISTS blog_posts (
            post_id INT NOT NULL AUTO_INCREMENT,
            title VARCHAR(180) NOT NULL,
            slug VARCHAR(200) NOT NULL,
            excerpt VARCHAR(500) NOT NULL,
            content TEXT NOT NULL,
            category VARCHAR(80) NOT NULL DEFAULT 'Food',
            image_path VARCHAR(255) NOT NULL DEFAULT 'img/blog-img/1.jpg',
            author VARCHAR(80) NOT NULL DEFAULT 'Foodera Team',
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            is_published TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (post_id),
            UNIQUE KEY blog_posts_slug_unique (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function ensureBlogCategoriesTable()
{
    if (!ensureBlogPostsTable()) return false;
    $ok = runQuery("CREATE TABLE IF NOT EXISTS blog_categories (
        category_id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(80) NOT NULL,
        slug VARCHAR(90) NOT NULL,
        description VARCHAR(255) NOT NULL DEFAULT '',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (category_id),
        UNIQUE KEY blog_categories_name_unique (name),
        UNIQUE KEY blog_categories_slug_unique (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (!$ok) return false;
    $conn = dbConnect();
    if (!$conn) return false;
    $legacy = mysqli_query($conn, 'SELECT DISTINCT category FROM blog_posts WHERE category <> ""');
    if ($legacy) while ($row = mysqli_fetch_assoc($legacy)) {
        $name = trim($row['category']);
        $slug = createSlug($name);
        runQuery('INSERT IGNORE INTO blog_categories(name, slug) VALUES (?, ?)', 'ss', $name, $slug);
    }
    return true;
}

function getBlogCategories($activeOnly = false)
{
    if (!ensureBlogCategoriesTable()) return array();
    $conn = dbConnect();
    if (!$conn) return array();
    $sql = 'SELECT c.*, COUNT(p.post_id) AS post_count FROM blog_categories c LEFT JOIN blog_posts p ON p.category=c.name GROUP BY c.category_id';
    if ($activeOnly) $sql .= ' HAVING c.is_active=1';
    $sql .= ' ORDER BY c.name ASC';
    $result = mysqli_query($conn, $sql);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
}

function addBlogCategory($data)
{
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    if ($name === '' || strlen($name) > 80 || strlen($description) > 255) return 'Enter a category name and a description under 255 characters.';
    if (!ensureBlogCategoriesTable()) return 'Blog categories are unavailable.';
    return runQuery('INSERT INTO blog_categories(name, slug, description, is_active) VALUES (?, ?, ?, ?)', 'sssi', $name, createSlug($name), $description, isset($data['is_active']) ? 1 : 0) ? 'Category created.' : 'That category already exists.';
}

function updateBlogCategory($categoryId, $data)
{
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    if ($categoryId < 1 || $name === '' || strlen($name) > 80 || strlen($description) > 255) return 'Enter a valid category name and description.';
    $conn = dbConnect();
    if (!$conn) return 'Database connection failed.';
    $lookup = mysqli_prepare($conn, 'SELECT name FROM blog_categories WHERE category_id=? LIMIT 1');
    mysqli_stmt_bind_param($lookup, 'i', $categoryId);
    mysqli_stmt_execute($lookup);
    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($lookup));
    if (!$existing) return 'Category not found.';
    $oldName = $existing['name'];
    $ok = runQuery('UPDATE blog_categories SET name=?, slug=?, description=?, is_active=? WHERE category_id=?', 'sssii', $name, createSlug($name), $description, isset($data['is_active']) ? 1 : 0, $categoryId);
    if ($ok && $oldName !== $name) runQuery('UPDATE blog_posts SET category=? WHERE category=?', 'ss', $name, $oldName);
    return $ok ? 'Category updated.' : 'Could not update the category.';
}

function deleteBlogCategory($categoryId)
{
    $conn = dbConnect();
    if (!$conn) return false;
    $lookup = mysqli_prepare($conn, 'SELECT name FROM blog_categories WHERE category_id=? LIMIT 1');
    mysqli_stmt_bind_param($lookup, 'i', $categoryId);
    mysqli_stmt_execute($lookup);
    $category = mysqli_fetch_assoc(mysqli_stmt_get_result($lookup));
    if (!$category) return false;
    $usage = mysqli_prepare($conn, 'SELECT post_id FROM blog_posts WHERE category=? LIMIT 1');
    mysqli_stmt_bind_param($usage, 's', $category['name']);
    mysqli_stmt_execute($usage);
    if (mysqli_fetch_assoc(mysqli_stmt_get_result($usage))) return false;
    return runQuery('DELETE FROM blog_categories WHERE category_id=?', 'i', $categoryId);
}

function createSlug($text)
{
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text), '-'));
    return $slug !== '' ? $slug : 'foodera-post';
}

function blogUploadImageFile($file, $title)
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return array('', 'Choose a JPG, PNG, or WebP image under 4 MB.');
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) < 1 || $file['size'] > 4000000 || !is_uploaded_file($file['tmp_name'])) return array('', 'The image could not be uploaded. Choose a JPG, PNG, or WebP image under 4 MB.');
    $extensions = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($extensions[$mime]) || !@getimagesize($file['tmp_name'])) return array('', 'Choose a valid JPG, PNG, or WebP image.');
    $directory = __DIR__ . '/uploads/blog';
    if ((!is_dir($directory) && !mkdir($directory, 0775, true)) || !is_writable($directory)) return array('', 'Blog image storage is not writable.');
    $path = 'uploads/blog/' . createSlug($title) . '-' . bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/' . $path)) return array('', 'The blog image could not be stored.');
    return array($path, '');
}

function blogUploadImage($title)
{
    return blogUploadImageFile($_FILES['image_file'] ?? null, $title);
}

function sanitizeBlogContent($content)
{
    $content = trim((string) $content);
    if ($content === '' || strlen($content) > 60000) return '';
    if (!class_exists('DOMDocument')) return strip_tags($content, '<p><br><strong><em><u><s><h2><h3><h4><ul><ol><li><blockquote><a><img>');

    $allowedTags = array('p', 'br', 'strong', 'em', 'u', 's', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote', 'a', 'img');
    $document = new DOMDocument('1.0', 'UTF-8');
    $previousErrors = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="utf-8" ?><div id="blog-content-root">' . $content . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previousErrors);
    $root = $document->getElementById('blog-content-root');
    if (!$root) return '';

    $cleanNode = function ($node) use (&$cleanNode, $allowedTags, $document) {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $tag = strtolower($child->nodeName);
                if (!in_array($tag, $allowedTags, true)) {
                    if (in_array($tag, array('script', 'style', 'iframe', 'object', 'embed'), true)) {
                        $node->removeChild($child);
                        continue;
                    }
                    while ($child->firstChild) $node->insertBefore($child->firstChild, $child);
                    $node->removeChild($child);
                    continue;
                }
                $href = $tag === 'a' ? trim($child->getAttribute('href')) : '';
                $src = $tag === 'img' ? trim($child->getAttribute('src')) : '';
                $alt = $tag === 'img' ? trim($child->getAttribute('alt')) : '';
                $attributes = iterator_to_array($child->attributes);
                foreach ($attributes as $attribute) $child->removeAttribute($attribute->name);
                if ($tag === 'a') {
                    if (preg_match('~^(https?://|mailto:)~i', $href)) {
                        $child->setAttribute('href', $href);
                        $child->setAttribute('rel', 'noopener noreferrer');
                        $child->setAttribute('target', '_blank');
                    } else {
                        $child->removeAttribute('href');
                    }
                }
                if ($tag === 'img') {
                    if (!preg_match('~^(?:\.\./)?uploads/blog/[a-z0-9-]+-[a-f0-9]{24}\.(?:jpg|png|webp)$~i', $src)) {
                        $node->removeChild($child);
                        continue;
                    }
                    $child->setAttribute('src', $src);
                    $child->setAttribute('alt', $alt);
                }
                $cleanNode($child);
            }
        }
    };
    $cleanNode($root);
    $html = '';
    foreach ($root->childNodes as $child) $html .= $document->saveHTML($child);
    return trim($html);
}

function renderBlogContent($content)
{
    $content = (string) $content;
    if ($content === strip_tags($content)) return nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8'));
    return sanitizeBlogContent($content);
}

function deleteManagedBlogImage($path)
{
    if (preg_match('~^uploads/blog/[a-z0-9-]+-[a-f0-9]{24}\.(jpg|png|webp)$~', (string) $path)) @unlink(__DIR__ . '/' . $path);
}

function addBlogPost($data)
{
    if (!ensureBlogPostsTable()) return 'Blog table is unavailable.';
    $title = trim($data['title'] ?? '');
    $excerpt = trim($data['excerpt'] ?? '');
    $content = sanitizeBlogContent($data['content'] ?? '');
    if ($title === '' || $excerpt === '' || $content === '' || strlen($title) > 180 || strlen($excerpt) > 500) return 'Enter a title, excerpt, and content within the allowed lengths.';

    list($imagePath, $imageError) = blogUploadImage($title);
    if ($imageError) return $imageError;

    $slug = createSlug($title);
    $conn = dbConnect();
    if (!$conn) {
        return 'Database connection failed.';
    }

    $baseSlug = $slug;
    $counter = 2;
    while (true) {
        $stmt = mysqli_prepare($conn, 'SELECT post_id FROM blog_posts WHERE slug = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $slug);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (!$result || mysqli_num_rows($result) === 0) {
            break;
        }
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }

    $isFeatured = isset($data['is_featured']) ? 1 : 0;
    $isPublished = isset($data['is_published']) ? 1 : 0;
    $stmt = mysqli_prepare($conn, 'INSERT INTO blog_posts(title, slug, excerpt, content, category, image_path, author, is_featured, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $category = trim($data['category'] ?? '') ?: 'Food';
    $author = trim($data['author'] ?? '') ?: 'Foodera Team';
    mysqli_stmt_bind_param($stmt, 'sssssssii', $title, $slug, $excerpt, $content, $category, $imagePath, $author, $isFeatured, $isPublished);

    if (mysqli_stmt_execute($stmt)) return 'Blog post added successfully.';
    deleteManagedBlogImage($imagePath);
    return 'Failed to add blog post.';
}

function getBlogPosts($publishedOnly = true, $limit = 0)
{
    ensureBlogPostsTable();
    $conn = dbConnect();
    if (!$conn) {
        return false;
    }

    $sql = 'SELECT * FROM blog_posts';
    if ($publishedOnly) {
        $sql .= ' WHERE is_published = 1';
    }
    $sql .= ' ORDER BY is_featured DESC, created_at DESC';
    if ($limit > 0) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    return mysqli_query($conn, $sql);
}

function deleteBlogPost($postId)
{
    ensureBlogPostsTable();
    return runQuery('DELETE FROM blog_posts WHERE post_id = ?', 'i', $postId);
}

function getBlogPost($postId, $publishedOnly = true)
{
    $conn = dbConnect();
    if (!$conn) return null;
    $sql = 'SELECT * FROM blog_posts WHERE post_id = ?' . ($publishedOnly ? ' AND is_published = 1' : '');
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, 'i', $postId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return $result ? mysqli_fetch_assoc($result) : null;
}

function updateBlogPost($postId, $data)
{
    $old = getBlogPost($postId, false);
    if (!$old) return 'Post not found.';
    $title = trim($data['title'] ?? '');
    $excerpt = trim($data['excerpt'] ?? '');
    $content = sanitizeBlogContent($data['content'] ?? '');
    if ($title === '' || $excerpt === '' || $content === '') return 'Title, excerpt, and content are required.';
    $imagePath = $old['image_path'];
    $replacedImage = false;
    if (!empty($_FILES['image_file']['name'])) {
        list($imagePath, $imageError) = blogUploadImage($title);
        if ($imageError) return $imageError;
        $replacedImage = true;
    }
    $category = trim($data['category'] ?? '') ?: 'Food';
    $author = trim($data['author'] ?? '') ?: 'Foodera Team';
    $featured = isset($data['is_featured']) ? 1 : 0;
    $published = isset($data['is_published']) ? 1 : 0;
    $ok = runQuery('UPDATE blog_posts SET title=?, excerpt=?, content=?, category=?, image_path=?, author=?, is_featured=?, is_published=? WHERE post_id=?', 'ssssssiii', $title, $excerpt, $content, $category, $imagePath, $author, $featured, $published, $postId);
    if ($ok && $replacedImage) deleteManagedBlogImage($old['image_path']);
    if (!$ok && $replacedImage) deleteManagedBlogImage($imagePath);
    return $ok ? 'Post updated.' : 'Could not update post.';
}

function blogInteractionTablesReady()
{
    static $ready = null;
    if ($ready !== null) return $ready;
    $conn = dbConnect();
    if (!$conn) return $ready = false;
    $comments = mysqli_query($conn, "CREATE TABLE IF NOT EXISTS blog_comments (comment_id INT AUTO_INCREMENT PRIMARY KEY, post_id INT NOT NULL, name VARCHAR(80) NOT NULL, email VARCHAR(190) NOT NULL, comment TEXT NOT NULL, is_approved TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX (post_id), CONSTRAINT blog_comments_post_fk FOREIGN KEY (post_id) REFERENCES blog_posts(post_id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $likes = mysqli_query($conn, "CREATE TABLE IF NOT EXISTS blog_likes (post_id INT NOT NULL, voter_hash CHAR(64) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (post_id, voter_hash), CONSTRAINT blog_likes_post_fk FOREIGN KEY (post_id) REFERENCES blog_posts(post_id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    return $ready = (bool) ($comments && $likes);
}

function blogCount($table, $postId)
{
    if (!blogInteractionTablesReady()) return 0;
    $conn = dbConnect();
    $sql = $table === 'blog_likes' ? 'SELECT COUNT(*) AS total FROM blog_likes WHERE post_id=?' : 'SELECT COUNT(*) AS total FROM blog_comments WHERE post_id=? AND is_approved=1';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $postId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return (int) $row['total'];
}

function blogHasLiked($postId, $email)
{
    if ($postId < 1 || !filter_var($email, FILTER_VALIDATE_EMAIL) || !blogInteractionTablesReady()) return false;
    $conn = dbConnect();
    if (!$conn) return false;
    $hash = hash('sha256', strtolower(trim($email)));
    $stmt = mysqli_prepare($conn, 'SELECT 1 FROM blog_likes WHERE post_id=? AND voter_hash=? LIMIT 1');
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'is', $postId, $hash);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return $result && mysqli_fetch_assoc($result) !== null;
}

function getBlogComments($postId)
{
    if (!blogInteractionTablesReady()) return false;
    $conn = dbConnect();
    $stmt = mysqli_prepare($conn, 'SELECT name, comment, created_at FROM blog_comments WHERE post_id=? AND is_approved=1 ORDER BY created_at DESC');
    mysqli_stmt_bind_param($stmt, 'i', $postId);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

function payment($data)
{
    $ok = runQuery(
        'INSERT INTO payments(name, card, exp_month, exp_year, city, state, zipcode) VALUES (?, ?, ?, ?, ?, ?, ?)',
        'sssssss',
        $data['name'],
        $data['card'],
        $data['month'],
        $data['year'],
        $data['city'],
        $data['state'],
        $data['zipcode']
    );

    if ($ok) {
        header('Location: bill.php');
        exit();
    }

    return 'Payment failed';
}

function ensureOrderPaymentsTable()
{
    static $ready = null;
    if ($ready !== null) return $ready;
    $conn = dbConnect();
    if (!$conn) return $ready = false;
    $sql = "CREATE TABLE IF NOT EXISTS order_payments (
        orderid INT NOT NULL PRIMARY KEY,
        payment_method ENUM('cod','upi','card','netbanking') NOT NULL,
        payment_status ENUM('pending','payment_requested','paid','failed','refunded') NOT NULL DEFAULT 'pending',
        reference_label VARCHAR(120) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY order_payments_method_idx (payment_method)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    return $ready = (bool) mysqli_query($conn, $sql);
}

function ensureOrderTrackingTable()
{
    static $ready = null;
    if ($ready !== null) return $ready;
    $conn = dbConnect();
    if (!$conn) return $ready = false;
    $sql = "CREATE TABLE IF NOT EXISTS order_tracking (
        orderid INT NOT NULL PRIMARY KEY,
        status ENUM('placed','confirmed','preparing','ready_for_pickup','rider_assigned','out_for_delivery','delivered','completed','cancelled') NOT NULL DEFAULT 'placed',
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY order_tracking_status_idx (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    return $ready = (bool) mysqli_query($conn, $sql);
}

function ensureOrderManagementTables()
{
    static $ready = null;
    if ($ready !== null) return $ready;
    if (!ensureOrderTrackingTable()) return $ready = false;
    $conn = dbConnect();
    if (!$conn) return $ready = false;
    $history = mysqli_query($conn, "CREATE TABLE IF NOT EXISTS order_status_history (history_id INT AUTO_INCREMENT PRIMARY KEY, orderid INT NOT NULL, status VARCHAR(40) NOT NULL, note VARCHAR(500) DEFAULT NULL, changed_by VARCHAR(120) NOT NULL DEFAULT 'system', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY order_status_history_order_idx (orderid)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $cancellations = mysqli_query($conn, "CREATE TABLE IF NOT EXISTS order_cancellations (cancellation_id INT AUTO_INCREMENT PRIMARY KEY, orderid INT NOT NULL UNIQUE, customer_email VARCHAR(100) NOT NULL, reason VARCHAR(120) NOT NULL, customer_note VARCHAR(500) DEFAULT NULL, status ENUM('requested','approved','rejected') NOT NULL DEFAULT 'requested', admin_note VARCHAR(500) DEFAULT NULL, requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, decided_at TIMESTAMP NULL DEFAULT NULL, KEY order_cancellations_email_idx (customer_email)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $refunds = mysqli_query($conn, "CREATE TABLE IF NOT EXISTS order_refunds (refund_id INT AUTO_INCREMENT PRIMARY KEY, orderid INT NOT NULL UNIQUE, amount DECIMAL(10,2) NOT NULL DEFAULT 0, status ENUM('not_required','requested','approved','processing','processed','rejected','failed') NOT NULL DEFAULT 'not_required', reference_label VARCHAR(120) DEFAULT NULL, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, KEY order_refunds_status_idx (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $itemCancellations = mysqli_query($conn, "CREATE TABLE IF NOT EXISTS order_item_cancellations (item_cancellation_id INT AUTO_INCREMENT PRIMARY KEY, orderid INT NOT NULL, pname VARCHAR(100) NOT NULL, quantity INT NOT NULL, amount DECIMAL(10,2) NOT NULL, reason VARCHAR(120) NOT NULL, customer_note VARCHAR(500) DEFAULT NULL, status ENUM('requested','approved','rejected') NOT NULL DEFAULT 'requested', admin_note VARCHAR(500) DEFAULT NULL, decided_at TIMESTAMP NULL DEFAULT NULL, refund_status ENUM('not_required','requested','approved','processing','processed','rejected','failed') NOT NULL DEFAULT 'not_required', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY order_item_cancellations_order_idx (orderid)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach (array(
        "ALTER TABLE order_payments MODIFY payment_status ENUM('pending','payment_requested','paid','failed','refunded') NOT NULL DEFAULT 'pending'",
        "ALTER TABLE order_tracking MODIFY status ENUM('placed','confirmed','preparing','ready_for_pickup','rider_assigned','out_for_delivery','delivered','completed','cancelled') NOT NULL DEFAULT 'placed'",
        "ALTER TABLE order_refunds MODIFY status ENUM('not_required','requested','approved','processing','processed','rejected','failed') NOT NULL DEFAULT 'not_required'",
        "ALTER TABLE order_item_cancellations ADD COLUMN status ENUM('requested','approved','rejected') NOT NULL DEFAULT 'requested' AFTER customer_note",
        "ALTER TABLE order_item_cancellations ADD COLUMN admin_note VARCHAR(500) DEFAULT NULL AFTER status",
        "ALTER TABLE order_item_cancellations ADD COLUMN decided_at TIMESTAMP NULL DEFAULT NULL AFTER admin_note",
        "ALTER TABLE order_item_cancellations MODIFY refund_status ENUM('not_required','requested','approved','processing','processed','rejected','failed') NOT NULL DEFAULT 'not_required'"
    ) as $migration) @mysqli_query($conn, $migration);
    return $ready = (bool) ($history && $cancellations && $refunds && $itemCancellations);
}

function orderWorkflowStatuses()
{
    return array('payment_pending','placed','confirmed','preparing','ready_for_pickup','rider_assigned','picked_up','out_for_delivery','delivered','completed','cancelled','rejected','payment_failed','delivery_failed','returned');
}

/*
 * Operations MIS foundation.  These tables deliberately extend the existing
 * order records instead of replacing them, so historic Foodera orders remain
 * readable while the admin can operate the new workflow.
 */
function ensureMisTables()
{
    static $ready = null;
    if ($ready !== null) return $ready;
    if (!ensureOrderManagementTables() || !ensureOrderPaymentsTable()) return $ready = false;
    $conn = dbConnect();
    if (!$conn) return $ready = false;

    $queries = array(
        "ALTER TABLE customers ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1",
        "ALTER TABLE order_tracking MODIFY status ENUM('payment_pending','placed','confirmed','preparing','ready_for_pickup','rider_assigned','picked_up','out_for_delivery','delivered','completed','cancelled','rejected','payment_failed','delivery_failed','returned') NOT NULL DEFAULT 'placed'",
        "ALTER TABLE order_payments MODIFY payment_status ENUM('pending','payment_requested','authorized','paid','failed','partially_refunded','refunded') NOT NULL DEFAULT 'pending'",
        "ALTER TABLE order_payments ADD COLUMN provider_name VARCHAR(80) DEFAULT NULL AFTER reference_label",
        "ALTER TABLE order_payments ADD COLUMN provider_transaction_id VARCHAR(160) DEFAULT NULL AFTER provider_name",
        "ALTER TABLE order_payments ADD COLUMN verified_at TIMESTAMP NULL DEFAULT NULL AFTER provider_transaction_id",
        "ALTER TABLE delivery_riders ADD COLUMN email VARCHAR(100) DEFAULT NULL AFTER phone",
        "ALTER TABLE delivery_riders ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL AFTER email",
        "ALTER TABLE delivery_riders ADD COLUMN personal_address TEXT DEFAULT NULL AFTER delivery_zone",
        "ALTER TABLE delivery_riders ADD COLUMN gender ENUM('female','male','non_binary','prefer_not_to_say') DEFAULT NULL AFTER personal_address",
        "ALTER TABLE delivery_riders ADD COLUMN date_of_birth DATE DEFAULT NULL AFTER gender",
        "ALTER TABLE delivery_riders ADD COLUMN emergency_contact_name VARCHAR(100) DEFAULT NULL AFTER date_of_birth",
        "ALTER TABLE delivery_riders ADD COLUMN emergency_contact_phone VARCHAR(30) DEFAULT NULL AFTER emergency_contact_name",
        "ALTER TABLE delivery_riders MODIFY delivery_zone TEXT NOT NULL",
        "ALTER TABLE customer_addresses ADD COLUMN city VARCHAR(100) NOT NULL DEFAULT '' AFTER address_line",
        "ALTER TABLE customer_addresses ADD COLUMN delivery_zone VARCHAR(120) NOT NULL DEFAULT '' AFTER city",
        "CREATE TABLE IF NOT EXISTS order_audit_logs (audit_id BIGINT AUTO_INCREMENT PRIMARY KEY, orderid INT NOT NULL, actor_type ENUM('admin','customer','system','rider') NOT NULL DEFAULT 'system', actor_identifier VARCHAR(120) NOT NULL DEFAULT 'system', action VARCHAR(80) NOT NULL, previous_value VARCHAR(120) DEFAULT NULL, new_value VARCHAR(120) DEFAULT NULL, reason VARCHAR(500) DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY order_audit_order_idx(orderid), KEY order_audit_action_idx(action,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS delivery_riders (rider_id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, phone VARCHAR(30) NOT NULL, email VARCHAR(100) DEFAULT NULL, password_hash VARCHAR(255) DEFAULT NULL, vehicle_type VARCHAR(60) NOT NULL DEFAULT '', delivery_zone TEXT NOT NULL, availability ENUM('offline','available','busy','on_break') NOT NULL DEFAULT 'offline', is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY delivery_riders_email_unique(email), KEY delivery_riders_available_idx(is_active,availability)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS delivery_zones (zone_id INT AUTO_INCREMENT PRIMARY KEY, city VARCHAR(100) NOT NULL, zone_name VARCHAR(120) NOT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY delivery_zone_unique(city,zone_name), KEY delivery_zone_active_idx(is_active,city,zone_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "INSERT IGNORE INTO delivery_zones(city,zone_name,is_active) VALUES ('Kolkata','Shyambazar',1),('Kolkata','Hatibagan',1),('Kolkata','Bagbazar',1),('Kolkata','Maniktala',1),('Kolkata','Ultadanga',1),('Kolkata','Belgachia',1),('Kolkata','Chitpur',1),('Kolkata','Sovabazar',1),('Kolkata','Burrabazar',1),('Kolkata','Sealdah',1),('Kolkata','Cossipore',1),('Kolkata','Sinthee',1)",
        "INSERT IGNORE INTO delivery_zones(city,zone_name,is_active) VALUES ('Kolkata','Salt Lake',1),('Kolkata','New Town',1),('Kolkata','Park Street',1),('Kolkata','Ballygunge',1),('Kolkata','Gariahat',1),('Kolkata','Tollygunge',1),('Kolkata','Behala',1),('Kolkata','Jadavpur',1),('Kolkata','Dum Dum',1),('Kolkata','Kestopur',1),('Howrah','Shibpur',1),('Howrah','Belur',1),('North 24 Parganas','Barasat',1),('North 24 Parganas','Madhyamgram',1)",
        "CREATE TABLE IF NOT EXISTS order_delivery_assignments (assignment_id INT AUTO_INCREMENT PRIMARY KEY, orderid INT NOT NULL, rider_id INT NOT NULL, status ENUM('assigned','accepted','picked_up','out_for_delivery','delivered','failed','reassigned') NOT NULL DEFAULT 'assigned', assigned_by VARCHAR(120) NOT NULL, assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, accepted_at TIMESTAMP NULL DEFAULT NULL, delivered_at TIMESTAMP NULL DEFAULT NULL, failure_reason VARCHAR(500) DEFAULT NULL, is_current TINYINT(1) NOT NULL DEFAULT 1, KEY order_delivery_order_idx(orderid,is_current), KEY order_delivery_rider_idx(rider_id,is_current)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS support_tickets (ticket_id INT AUTO_INCREMENT PRIMARY KEY, ticket_reference VARCHAR(30) NOT NULL UNIQUE, customer_email VARCHAR(100) NOT NULL, orderid INT DEFAULT NULL, category VARCHAR(60) NOT NULL DEFAULT 'other', subject VARCHAR(180) NOT NULL, status ENUM('open','in_progress','waiting_for_customer','resolved','closed') NOT NULL DEFAULT 'open', assigned_admin VARCHAR(100) DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, KEY support_tickets_customer_idx(customer_email,status), KEY support_tickets_status_idx(status,updated_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS support_ticket_messages (message_id INT AUTO_INCREMENT PRIMARY KEY, ticket_id INT NOT NULL, sender_type ENUM('customer','admin') NOT NULL, sender_identifier VARCHAR(100) NOT NULL, message TEXT NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY support_ticket_messages_idx(ticket_id,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS delivery_otps (otp_id BIGINT AUTO_INCREMENT PRIMARY KEY, orderid INT NOT NULL UNIQUE, otp_hash VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, verified_at TIMESTAMP NULL DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY delivery_otps_expiry_idx(expires_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS cod_collections (collection_id BIGINT AUTO_INCREMENT PRIMARY KEY, orderid INT NOT NULL UNIQUE, rider_id INT NOT NULL, expected_amount DECIMAL(10,2) NOT NULL, collected_amount DECIMAL(10,2) DEFAULT NULL, status ENUM('pending','recorded','reconciled','disputed') NOT NULL DEFAULT 'pending', recorded_at TIMESTAMP NULL DEFAULT NULL, reconciled_at TIMESTAMP NULL DEFAULT NULL, admin_note VARCHAR(500) DEFAULT NULL, KEY cod_collections_status_idx(status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS notification_jobs (job_id BIGINT AUTO_INCREMENT PRIMARY KEY, event_key VARCHAR(190) NOT NULL, recipient_type ENUM('customer','admin','rider') NOT NULL, recipient_identifier VARCHAR(120) NOT NULL DEFAULT '', channel ENUM('in_app','email','sms','whatsapp','push') NOT NULL DEFAULT 'in_app', title VARCHAR(160) NOT NULL, body VARCHAR(500) NOT NULL, link_path VARCHAR(255) NOT NULL DEFAULT '', status ENUM('pending','processing','sent','failed') NOT NULL DEFAULT 'pending', attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, provider_response VARCHAR(500) DEFAULT NULL, available_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, sent_at TIMESTAMP NULL DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY notification_jobs_dedupe(event_key,recipient_type,recipient_identifier,channel), KEY notification_jobs_queue_idx(status,available_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS system_settings (setting_key VARCHAR(100) NOT NULL PRIMARY KEY, setting_value TEXT NOT NULL, is_sensitive TINYINT(1) NOT NULL DEFAULT 0, updated_by VARCHAR(120) DEFAULT NULL, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ,"CREATE TABLE IF NOT EXISTS delivery_feedback (feedback_id BIGINT AUTO_INCREMENT PRIMARY KEY, orderid INT NOT NULL UNIQUE, customer_email VARCHAR(100) NOT NULL, receipt_confirmed TINYINT(1) NOT NULL DEFAULT 0, food_rating TINYINT UNSIGNED DEFAULT NULL, service_rating TINYINT UNSIGNED DEFAULT NULL, ordering_rating TINYINT UNSIGNED DEFAULT NULL, comment VARCHAR(1000) NOT NULL DEFAULT '', consent_to_feature TINYINT(1) NOT NULL DEFAULT 0, status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, KEY delivery_feedback_status_idx(status,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ,"CREATE TABLE IF NOT EXISTS order_policy_acceptances (acceptance_id BIGINT AUTO_INCREMENT PRIMARY KEY, orderid INT NOT NULL UNIQUE, customer_email VARCHAR(100) NOT NULL, policy_version VARCHAR(30) NOT NULL, accepted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY order_policy_customer_idx(customer_email), KEY order_policy_order_idx(orderid)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    $ok = true;
    foreach ($queries as $query) {
        $result = @mysqli_query($conn, $query);
        /* ADD COLUMN is deliberately idempotent for shared-host deployments. */
        if (stripos(ltrim($query), 'CREATE TABLE') === 0) $ok = $result && $ok;
    }
    return $ready = $ok;
}

function orderAllowedTransitions($from)
{
    $map = array(
        'payment_pending' => array('placed','payment_failed','cancelled'),
        'placed' => array('confirmed','rejected','cancelled'),
        'confirmed' => array('preparing','cancelled'),
        'preparing' => array('ready_for_pickup'),
        'ready_for_pickup' => array('rider_assigned'),
        'rider_assigned' => array('picked_up','delivery_failed'),
        'picked_up' => array('out_for_delivery','delivery_failed'),
        'out_for_delivery' => array('delivered','delivery_failed','returned'),
        'delivered' => array('completed'),
        'delivery_failed' => array('rider_assigned','returned','cancelled'),
        'returned' => array('cancelled'),
        'rejected' => array(), 'cancelled' => array(), 'payment_failed' => array(), 'completed' => array()
    );
    return $map[$from] ?? array();
}

function orderAudit($conn, $orderId, $actorType, $actor, $action, $previous = null, $next = null, $reason = null)
{
    $stmt = mysqli_prepare($conn, 'INSERT INTO order_audit_logs(orderid,actor_type,actor_identifier,action,previous_value,new_value,reason) VALUES(?,?,?,?,?,?,?)');
    return $stmt && mysqli_stmt_bind_param($stmt, 'issssss', $orderId, $actorType, $actor, $action, $previous, $next, $reason) && mysqli_stmt_execute($stmt);
}

function orderQueueNotification($conn, $eventKey, $type, $recipient, $title, $body, $link)
{
    $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO notification_jobs(event_key,recipient_type,recipient_identifier,title,body,link_path) VALUES(?,?,?,?,?,?)");
    return $stmt && mysqli_stmt_bind_param($stmt, 'ssssss', $eventKey, $type, $recipient, $title, $body, $link) && mysqli_stmt_execute($stmt);
}

function createSupportTicket($email, $subject, $message, $category = 'other', $orderId = null)
{
    if (!ensureMisTables() || !filter_var($email, FILTER_VALIDATE_EMAIL) || trim($subject) === '' || trim($message) === '') return false;
    $conn = dbConnect();
    $reference = 'FD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $stmt = mysqli_prepare($conn, 'INSERT INTO support_tickets(ticket_reference,customer_email,orderid,category,subject) VALUES(?,?,?,?,?)');
    mysqli_stmt_bind_param($stmt, 'ssiss', $reference, $email, $orderId, $category, $subject);
    if (!$stmt || !mysqli_stmt_execute($stmt)) return false;
    $ticketId = mysqli_insert_id($conn);
    $entry = mysqli_prepare($conn, "INSERT INTO support_ticket_messages(ticket_id,sender_type,sender_identifier,message) VALUES(?, 'customer', ?, ?)");
    mysqli_stmt_bind_param($entry, 'iss', $ticketId, $email, $message);
    if (!$entry || !mysqli_stmt_execute($entry)) return false;
    createNotification('admin', '', 'New support ticket ' . $reference, $subject, 'support.php');
    return $reference;
}

function orderTransition($orderId, $nextStatus, $actorType, $actor, $reason = '')
{
    if (!ensureMisTables() || !in_array($nextStatus, orderWorkflowStatuses(), true)) return array(false, 'Invalid workflow request.');
    $conn = dbConnect();
    if (!$conn || !mysqli_begin_transaction($conn)) return array(false, 'Operations database is unavailable.');
    $stmt = mysqli_prepare($conn, 'SELECT o.email,COALESCE(t.status,\'placed\') status FROM orders o LEFT JOIN order_tracking t ON t.orderid=o.orderid WHERE o.orderid=? FOR UPDATE');
    mysqli_stmt_bind_param($stmt, 'i', $orderId); mysqli_stmt_execute($stmt);
    $order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$order) { mysqli_rollback($conn); return array(false, 'Order not found.'); }
    $current = $order['status'];
    if ($current === $nextStatus) { mysqli_rollback($conn); return array(true, 'Order status is already up to date.'); }
    if (!in_array($nextStatus, orderAllowedTransitions($current), true)) { mysqli_rollback($conn); return array(false, 'This order cannot move from ' . str_replace('_', ' ', $current) . ' to ' . str_replace('_', ' ', $nextStatus) . '.'); }
    if ($current === 'payment_pending' && $nextStatus === 'placed') {
        $payment = mysqli_prepare($conn, "SELECT payment_status FROM order_payments WHERE orderid=? FOR UPDATE");
        mysqli_stmt_bind_param($payment, 'i', $orderId); mysqli_stmt_execute($payment);
        $paymentRow = mysqli_fetch_assoc(mysqli_stmt_get_result($payment));
        if (($paymentRow['payment_status'] ?? '') !== 'paid') { mysqli_rollback($conn); return array(false, 'Verify the online payment before placing this order in the restaurant queue.'); }
    }
    $update = mysqli_prepare($conn, 'UPDATE order_tracking SET status=? WHERE orderid=?');
    $ok = $update && mysqli_stmt_bind_param($update, 'si', $nextStatus, $orderId) && mysqli_stmt_execute($update);
    $ok = $ok && orderRecordStatus($conn, $orderId, $nextStatus, $reason ?: 'Workflow updated', $actor);
    $ok = $ok && orderAudit($conn, $orderId, $actorType, $actor, 'status_changed', $current, $nextStatus, $reason ?: null);
    $title = 'Order update'; $body = 'Order #' . $orderId . ' is now ' . ucwords(str_replace('_', ' ', $nextStatus)) . '.';
    $ok = $ok && orderQueueNotification($conn, 'order:' . $orderId . ':status:' . $nextStatus, 'customer', $order['email'], $title, $body, 'track-order.php?order=' . $orderId);
    if (!$ok || !mysqli_commit($conn)) { mysqli_rollback($conn); return array(false, 'The status change could not be saved.'); }
    createNotification('customer', $order['email'], $title, $body, 'track-order.php?order=' . $orderId);
    return array(true, 'Order moved to ' . ucwords(str_replace('_', ' ', $nextStatus)) . '.');
}

function ensureNotificationTable()
{
    static $ready = null;
    if ($ready !== null) return $ready;
    $conn = dbConnect();
    if (!$conn) return $ready = false;
    $sql = "CREATE TABLE IF NOT EXISTS notifications (notification_id INT AUTO_INCREMENT PRIMARY KEY, recipient_type ENUM('customer','admin') NOT NULL, recipient_email VARCHAR(100) DEFAULT NULL, title VARCHAR(160) NOT NULL, body VARCHAR(500) NOT NULL, link_path VARCHAR(255) NOT NULL DEFAULT '', is_read TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY notifications_recipient_idx (recipient_type,recipient_email,is_read,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    return $ready = (bool) mysqli_query($conn, $sql);
}

function createNotification($type, $email, $title, $body, $link = '')
{
    if (!ensureNotificationTable()) return false;
    $conn = dbConnect();
    $stmt = mysqli_prepare($conn, 'INSERT INTO notifications(recipient_type,recipient_email,title,body,link_path) VALUES(?,?,?,?,?)');
    return $stmt && mysqli_stmt_bind_param($stmt, 'sssss', $type, $email, $title, $body, $link) && mysqli_stmt_execute($stmt);
}

function notificationUnreadCount($type, $email = null)
{
    if (!ensureNotificationTable()) return 0;
    $conn = dbConnect();
    $sql = $type === 'admin' ? "SELECT COUNT(*) total FROM notifications WHERE recipient_type='admin' AND is_read=0" : "SELECT COUNT(*) total FROM notifications WHERE recipient_type='customer' AND recipient_email=? AND is_read=0";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return 0;
    if ($type !== 'admin') mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    return (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0);
}

function orderRecordStatus($conn, $orderId, $status, $note = '', $changedBy = 'system')
{
    $stmt = mysqli_prepare($conn, 'INSERT INTO order_status_history(orderid,status,note,changed_by) VALUES(?,?,?,?)');
    return $stmt && mysqli_stmt_bind_param($stmt, 'isss', $orderId, $status, $note, $changedBy) && mysqli_stmt_execute($stmt);
}

function getLatestOrderByEmail($email)
{
    $conn = dbConnect();
    if (!$conn) {
        return null;
    }

    $stmt = mysqli_prepare($conn, 'SELECT * FROM `orders` WHERE email = ? ORDER BY orderid DESC LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return $result ? mysqli_fetch_assoc($result) : null;
}

function getOrderItems($orderid)
{
    $conn = dbConnect();
    if (!$conn) {
        return false;
    }

    $stmt = mysqli_prepare($conn, 'SELECT * FROM order_items WHERE orderid = ?');
    mysqli_stmt_bind_param($stmt, 'i', $orderid);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}
?>
