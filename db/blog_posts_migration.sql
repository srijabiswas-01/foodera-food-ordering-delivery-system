USE `foodera`;

CREATE TABLE IF NOT EXISTS `blog_posts` (
  `post_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(180) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `excerpt` varchar(500) NOT NULL,
  `content` text NOT NULL,
  `category` varchar(80) NOT NULL DEFAULT 'Food',
  `image_path` varchar(255) NOT NULL DEFAULT 'img/blog-img/1.jpg',
  `author` varchar(80) NOT NULL DEFAULT 'Foodera Team',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`post_id`),
  UNIQUE KEY `blog_posts_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `blog_posts` (`title`, `slug`, `excerpt`, `content`, `category`, `image_path`, `author`, `is_featured`, `is_published`)
SELECT 'Fresh meals, clear ordering, and faster delivery',
       'fresh-meals-clear-ordering-and-faster-delivery',
       'Foodera brings menu browsing, cart management, checkout, and billing into one simple flow for customers.',
       'Foodera is built to keep food ordering organized from the first menu view to the final bill.',
       'Foodera',
       'img/blog-img/1.jpg',
       'Foodera Team',
       1,
       1
WHERE NOT EXISTS (
    SELECT 1 FROM `blog_posts` WHERE `slug` = 'fresh-meals-clear-ordering-and-faster-delivery'
);

INSERT INTO `blog_posts` (`title`, `slug`, `excerpt`, `content`, `category`, `image_path`, `author`, `is_featured`, `is_published`)
SELECT 'How restaurants can keep customers connected',
       'how-restaurants-can-keep-customers-connected',
       'A reliable ordering platform helps restaurant partners manage visibility, service quality, and repeat orders.',
       'Foodera supports restaurants with a practical online presence and a direct ordering experience.',
       'Restaurants',
       'img/blog-img/2.jpg',
       'Foodera Team',
       0,
       1
WHERE NOT EXISTS (
    SELECT 1 FROM `blog_posts` WHERE `slug` = 'how-restaurants-can-keep-customers-connected'
);
