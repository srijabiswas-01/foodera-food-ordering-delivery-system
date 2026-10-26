-- phpMyAdmin SQL Dump
-- version 5.0.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 29, 2022 at 04:00 PM
-- Server version: 10.4.14-MariaDB
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `foodera`
--

CREATE DATABASE IF NOT EXISTS `foodera` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `foodera`;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `user` varchar(50) CHARACTER SET utf8mb4 NOT NULL,
  `address` varchar(50) CHARACTER SET utf8mb4 NOT NULL,
  `email` varchar(50) CHARACTER SET utf8mb4 NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
  `phone` text CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Admins are created once through /admin/login.php after import.

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `post_id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `blog_posts` (`post_id`, `title`, `slug`, `excerpt`, `content`, `category`, `image_path`, `author`, `is_featured`, `is_published`, `created_at`, `updated_at`) VALUES
(1, 'Fresh meals, clear ordering, and faster delivery', 'fresh-meals-clear-ordering-and-faster-delivery', 'Foodera brings menu browsing, cart management, checkout, and billing into one simple flow for customers.', 'Foodera is built to keep food ordering organized from the first menu view to the final bill.', 'Foodera', 'img/blog-img/1.jpg', 'Foodera Team', 1, 1, current_timestamp(), current_timestamp()),
(2, 'How restaurants can keep customers connected', 'how-restaurants-can-keep-customers-connected', 'A reliable ordering platform helps restaurant partners manage visibility, service quality, and repeat orders.', 'Foodera supports restaurants with a practical online presence and a direct ordering experience.', 'Restaurants', 'img/blog-img/2.jpg', 'Foodera Team', 0, 1, current_timestamp(), current_timestamp());

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `user` varchar(50) NOT NULL,
  `address` varchar(255) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`user`, `address`, `email`, `password`, `phone`) VALUES
('payel', '21/4/2 A.K.Mukherjee road', 'payel@gmail.com', '12345', '8617260828'),
('pinky', 'kolkata', 'pinky@gmail.com', '123', '8617260828'),
('Ruchira', '21/4/2 A.K.Mukherjee road', 'ruchi@gmail.com', '1234', '8617260828'),
('sayoni ', 'kolkata', 'sayoni@gmail.com', '852741', '9875641236'),
('srija', 'kolkata', 'srija@gmail.com', '1236', '9875641236'),
('Tithi', 'kolkata', 'tithi@gmail.com', '1234', '8617260828');

-- --------------------------------------------------------

--
-- Table structure for table `foods`
--

CREATE TABLE `foods` (
  `id` int(11) NOT NULL,
  `name` varchar(1000) NOT NULL,
  `description` longtext NOT NULL,
  `price` int(11) NOT NULL,
  `picture` varbinary(1000) NOT NULL,
  `category` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `foods`
--

INSERT INTO `foods` (`id`, `name`, `description`, `price`, `picture`, `category`) VALUES
(1, 'Coconut Shrimp with Mango Dipping Sauce', 'Coconut Shrimp, served alongside a spicy mango dipping sauce.', 180, 0x70726f66696c657069632f7370656369616c322e6a7067, 'special'),
(2, 'Chicken Biryani', 'Biryani is a mixed rice dish. It is made with Indian spices, rice, and chicken, eggs or vegetables such as potatoes.', 180, 0x70726f66696c657069632f666f6f6431322e504e47, 'continental & chinese'),
(3, 'Tanduri Chicken (Full 1 Kg)', 'Tandoori chicken is a chicken dish prepared by roasting chicken marinated in yogurt and spices in a tandoor, a cylindrical clay oven.', 390, 0x70726f66696c657069632f666f6f64332e706e67, 'continental & chinese'),
(4, 'Dahi Vada', 'Fluffy, tender, tangy and sweet dahi vada are a combination of all your favorite flavors and textures in one tasty snack. They consist of homemade fried lentil dumpling fritters, dunked in creamy whipped yogurt and topped with both spicy and sweet chutneys.', 190, 0x70726f66696c657069632f64616869766164612e6a7067, 'continental & chinese'),
(5, 'Affogato Coffee', 'Affogato style shot is basically an espresso shot poured last or on top of the coffee.', 200, 0x70726f66696c657069632f4166666f6761746f20436f666665652e6a7067, 'Drinks'),
(6, 'Americano Coffee', 'Americano is a type of coffee drink prepared by diluting an espresso with hot water.', 215, 0x70726f66696c657069632f416d65726963616e6f20436f666665652e6a7067, 'Drinks'),
(7, 'Black Coffee', 'Black coffee is simply coffee that is normally brewed without the addition of additives such as sugar, milk, cream, or added flavors.', 40, 0x70726f66696c657069632f426c61636b20436f666665652e6a7067, 'Drinks'),
(8, 'Cinnamon Roll Frappuccino', 'Cinnamon Roll Frappuccino consists a blend of cinnamon dolce syrup, white chocolate mocha sauce, vanilla bean, Frappuccino Roast Coffee, milk and ice. It is finished with whipped cream and a sprinkle of cinnamon dolce topping.', 298, 0x70726f66696c657069632f43696e6e616d6f6e20526f6c6c204672617070756363696e6f2e6a7067, 'Drinks'),
(9, 'Green Tea Creame Frappuccino', 'In Green Tea Frappuccino the taste of green tea is softened to mildness by milk and sugar. It is a refreshing and cool drink with a flavor similar to green tea ice cream.', 340, 0x70726f66696c657069632f477265656e2054656120437265616d65204672617070756363696e6f2e6a7067, 'Drinks'),
(10, 'Hot Chocolate', 'Hot chocolate is made from melted chocolate and milk. It includes both cocoa solids and cocoa butter', 275, 0x70726f66696c657069632f486f742043686f636f6c6174652e6a7067, 'Drinks'),
(11, 'Veggie Burger', 'A veggie burger is a sandwich that consist of aloo tikki, leaves, cheese slice server between slices of bread with sauce and salad.', 150, 0x70726f66696c657069632f5665674275726765722e6a7067, 'Snacks'),
(12, 'Veg frankie', 'Whole wheat roti or chapati is layered with spiced mashed potato roll, green chutney, chopped onion, pickled green chilies, and frankie masala.', 150, 0x70726f66696c657069632f5665674672616e6b69652e6a7067, 'Snacks'),
(13, 'Stuffed Mushroom', 'Stuffed mushroom is a dish prepared using edible mushrooms as its primary ingredient. This dish is typically baked or boiled with fillings of veggies.', 300, 0x70726f66696c657069632f53747566666564204d757368726f6f6d2e6a7067, 'Snacks'),
(14, 'Chicken Burger', 'Chicken Burger is a sandwich that consist of boneless, skinless chicken breast server between slices of bread with sauce and salad.', 95, 0x70726f66696c657069632f4275726765722e6a7067, 'Snacks'),
(15, 'Chicken Pizza', 'Chicken Pizza is a dish of Italian origin, consisting of a flat round base of dough baked with a topping of tomatoes and cheese, with added meat.', 209, 0x70726f66696c657069632f50697a7a612e6a7067, 'Snacks'),
(16, 'Garlic Chicken Cheese Ball', 'Garlic chicken cheese balls is minced chicken flavoured with garlic, stuffed with gooey mozzarella cheese deep fried to perfection.', 350, 0x70726f66696c657069632f4761726c6963436869636b656e43686565736542616c6c2e6a7067, 'Snacks'),
(17, 'MOHANTHAL', 'Mohanthal is a traditional Gujarati sweet dish made up from besan, ghee, sugar and nuts. It is made in Rajasthan too.', 180, 0x70726f66696c657069632f31696d672e6a7067, 'Dessert'),
(18, 'Chocolate Mousse', 'This is a classic chocolate mousse made the proper French way, as served in fine dining restaurants. Less cream, more chocolate, a more intense chocolate flavour and a beautiful creamy mouth feel.', 95, 0x70726f66696c657069632f392e6a7067, 'Dessert'),
(19, 'Almond Malai Kulfi', 'This almond malai kulfi is like happiness condensed in a matki. To make it extra special, top it off with dry fruits and make sure it is absolutely chilled before serving. So creamy, so simple - it hits all the right notes.', 150, 0x70726f66696c657069632f31302e6a7067, 'Dessert'),
(20, 'Pantua', 'Pantua is a local confection from the Indian subcontinent, notable in West Bengal, Eastern India and Bangladesh. It is a traditional Bengali sweet made of deep-fried balls of semolina, chhena, milk, ghee and sugar syrup. Pantuas range in colour from pale brown to nearly black depending on how long they are fried.', 200, 0x70726f66696c657069632f342e6a7067, 'Dessert'),
(21, 'Fudge Ice-Cream', 'Get your ice cream, cookies and cake in one with this decadent fudge-filled treat. With Betty Crocker™ Super Moist™ chocolate fudge cake mix.', 265, 0x70726f66696c657069632f31332e6a7067, 'Dessert'),
(22, 'Ras Malai', 'Ras malai or rossomalai or Rasamalei is a dessert originating from the eastern regions of the Indian subcontinent. The dessert is called rossomalai in Bengali, ras malai in Hindi, and Rasa Malei in Odia.', 195, 0x70726f66696c657069632f31382e6a7067, 'Dessert'),
(23, 'Chicken Manchurian (6 pc.)', 'The sweet tomato ketchup and soy sauce based sauce makes it a firm. The best thing about this It is not overly sweet, it has the right amount of saltiness from the soy sauce and the tanginess from vinegar to balance the dish out.', 280, 0x70726f66696c657069632f666f6f6431312e706e67, 'continental & chinese'),
(24, 'Chinese Mix Fried Rice', 'Chinese mixed fried rice, as the name says, is a delectable Chinese delight and very popular non-vegetarian dish in India and across. The fired rice is made by using non-vegetarian options like chicken, eggs and prawn along with other vegetarian options like mushroom, onion and tomato.', 200, 0x70726f66696c657069632f666f6f64382e706e67, 'continental & chinese'),
(25, 'Mushroom Stroganoff', 'A creamy sauce filled with sauteed mushroom, carrot, green beans and baby corn and served along with a bed of rice.', 299, 0x70726f66696c657069632f4d757368726f6f6d5f53746f72676f6e6f66662e6a7067, 'continental & chinese'),
(26, 'Sesame Noodles', 'Peanut buttery, textured sesame noodles with bright pops of vegetables can be on your table in 20 minutes flat.', 299, 0x70726f66696c657069632f536573616d652d4e6f6f646c65732e6a7067, 'continental & chinese'),
(27, 'Mushroom Cutlet Vegetarian Sizzler', 'Mushroom Cutlet Vegetarian Sizzler Recipe is a complete meal with a rice, stir fried veggies and crunchy mushroom cutlet served on sizzling hot plates.', 199, 0x70726f66696c657069632f4d757368726f6f6d5f4375746c65745f5665675f53697a7a6c65722e6a7067, 'continental & chinese'),
(28, 'BAKED ALASKA', 'A dessert of sponge cake and ice cream in a meringue covering, cooked in a hot oven for a very short time.', 299, 0x70726f66696c657069632f32342e6a7067, 'Dessert'),
(29, 'Tipsy (or not) Brownie Parfaits', 'Layers of Kahlua-soaked brownies, whipped cream with chocolate toffee & chocolate pudding. An easy & elegant dessert.', 650, 0x70726f66696c657069632f33302e6a7067, 'Dessert'),
(30, 'MACORONS', 'A macaron or French macaroon is a sweet meringue-based confection made with egg white, icing sugar, granulated sugar, almond meal, and foods colouring. The macaron is traditionally held to have been introduced in France by the Italian chef of queen Catherine de Medici during the Renaissance.', 350, 0x70726f66696c657069632f32362e6a7067, 'Dessert');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `messageid` int(11) NOT NULL,
  `fname` varchar(50) NOT NULL,
  `lname` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `subject` varchar(50) NOT NULL,
  `message` varchar(50) NOT NULL,
  `mdate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`messageid`, `fname`, `lname`, `email`, `subject`, `message`, `mdate`) VALUES
(1, 'payel', 'bag', 'payel@gmail.com', 'test', 'testing', '2022-01-08 05:48:43'),
(2, 'srija', 'biswas', 'srija@gmail.com', 'test', 'checking', '2022-01-08 17:03:22'),
(3, 'tithi', 'roy', 'tithi@gmail.com', 'font', 'sizing', '2022-01-08 17:18:50'),
(4, 'tithi', 'roy', 'tithi@gmail.com', 'testing', 'done', '2022-01-09 08:24:29');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `orderid` int(100) NOT NULL,
  `name` text NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` bigint(100) NOT NULL,
  `address` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`orderid`, `name`, `email`, `phone`, `address`) VALUES
(11, 'payel', 'payel@gmail.com', 8617260828, '21/4/2 A.K.Mukherjee road'),
(12, 'srija', 'srija@gmail.com', 9875641236, 'kolkata'),
(13, 'srija', 'srija@gmail.com', 9875641236, 'kolkata'),
(14, 'payel', 'payel@gmail.com', 8617260828, '21/4/2 A.K.Mukherjee road'),
(15, 'payel', 'payel@gmail.com', 8617260828, '21/4/2 A.K.Mukherjee road'),
(16, 'pinky', 'pinky@gmail.com', 8617260828, 'kolkata'),
(17, 'pinky', 'pinky@gmail.com', 8617260828, 'kolkata'),
(18, 'payel', 'payel@gmail.com', 8617260828, '21/4/2 A.K.Mukherjee road'),
(19, 'payel', 'payel@gmail.com', 8617260828, '21/4/2 A.K.Mukherjee road'),
(20, 'sayoni ', 'sayoni@gmail.com', 9875641236, 'kolkata'),
(21, 'payel', 'payel@gmail.com', 8617260828, '21/4/2 A.K.Mukherjee road'),
(22, 'Ruchira', 'ruchi@gmail.com', 8617260828, '21/4/2 A.K.Mukherjee road'),
(23, 'Ruchira', 'ruchi@gmail.com', 8617260828, '21/4/2 A.K.Mukherjee road'),
(24, 'Tithi', 'tithi@gmail.com', 8617260828, 'kolkata'),
(25, 'Tithi', 'tithi@gmail.com', 8617260828, 'kolkata'),
(26, 'Tithi', 'tithi@gmail.com', 8617260828, 'kolkata');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `name` varchar(100) NOT NULL,
  `card` varchar(20) NOT NULL,
  `exp_month` varchar(2) NOT NULL,
  `exp_year` varchar(4) NOT NULL,
  `city` text NOT NULL,
  `state` text NOT NULL,
  `zipcode` varchar(100) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`name`, `card`, `exp_month`, `exp_year`, `city`, `state`, `zipcode`, `date`) VALUES
('srijabiswas', 1212415345454659, '03', '2085', 'mumbai', 'maharastra', '700145', '2022-04-29 04:31:26'),
('payel', 1222455632336665, '02', '2022', 'mumbai', 'maharastra', '700145', '2022-04-29 04:34:52'),
('payelbag', 1224559663332155, '02', '2023', 'kolkata', 'westbengal', '700090', '2022-04-29 05:30:35'),
('payelbag', 1345456751321324, '02', '2025', 'kolkata', 'westbengal', '700090', '2022-04-29 04:21:15'),
('payelbag', 2528639547123692, '02', '2023', 'kolkata', 'westbengal', '700090', '2022-04-27 04:56:30'),
('sayonidas', 2589784351321354, '05', '2023', 'kolkata', 'westbengal', '700090', '2022-04-28 05:46:19'),
('payel', 4524158585754221, '02', '2023', 'kolkata', 'westbengal', '700090', '2022-04-29 06:10:18'),
('srija', 7854411255488652, '02', '2023', 'kolkata', 'westbengal', '700090', '2022-04-29 05:11:52');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `orderid` int(100) NOT NULL,
  `pname` varchar(100) NOT NULL,
  `pprice` int(100) NOT NULL,
  `quantity` int(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`orderid`, `pname`, `pprice`, `quantity`) VALUES
(11, 'Tanduri Chicken (Full 1 Kg)', 390, 2),
(11, 'Affogato Coffee', 200, 2),
(11, 'Veggie Burger', 150, 2),
(12, 'Chicken Pizza', 209, 1),
(12, 'Sesame Noodles', 299, 2),
(12, 'Chocolate Mousse', 95, 2),
(13, 'Tanduri Chicken (Full 1 Kg)', 390, 1),
(13, 'Affogato Coffee', 200, 1),
(14, 'Chicken Manchurian (6 pc.)', 280, 16),
(15, 'Green Tea Creame Frappuccino', 340, 10),
(15, 'Chicken Pizza', 209, 2),
(15, 'BAKED ALASKA', 299, 2),
(16, 'Tanduri Chicken (Full 1 Kg)', 390, 2),
(17, 'Americano Coffee', 215, 1),
(18, 'Americano Coffee', 215, 1),
(18, 'Affogato Coffee', 200, 1),
(19, 'BAKED ALASKA', 299, 1),
(20, 'Chicken Biryani', 180, 1),
(20, 'Affogato Coffee', 200, 1),
(21, 'Chicken Biryani', 180, 1),
(21, 'Affogato Coffee', 200, 1),
(22, 'Chicken Biryani', 180, 1),
(22, 'Affogato Coffee', 200, 1),
(23, 'Chicken Biryani', 180, 1),
(23, 'Affogato Coffee', 200, 1),
(24, 'Chicken Biryani', 180, 1),
(24, 'Affogato Coffee', 200, 1),
(25, 'Chicken Biryani', 180, 1),
(25, 'Affogato Coffee', 200, 1),
(26, 'Chicken Biryani', 180, 1),
(26, 'Affogato Coffee', 200, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD UNIQUE KEY `blog_posts_slug_unique` (`slug`);

--
-- Indexes for table `foods`
--
ALTER TABLE `foods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`messageid`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`orderid`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`card`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `foods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

ALTER TABLE `blog_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `messages`
  MODIFY `messageid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `orders`
  MODIFY `orderid` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;
CREATE TABLE IF NOT EXISTS `customer_profiles` (
  `customer_email` VARCHAR(50) NOT NULL PRIMARY KEY,
  `photo_path` VARCHAR(255) DEFAULT NULL,
  `dietary_preference` VARCHAR(30) NOT NULL DEFAULT 'none',
  `favorite_cuisines` VARCHAR(255) NOT NULL DEFAULT '',
  `allergies` VARCHAR(255) NOT NULL DEFAULT '',
  CONSTRAINT `customer_profiles_customer_fk` FOREIGN KEY (`customer_email`) REFERENCES `customers` (`email`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `customer_addresses` (
  `address_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `customer_email` VARCHAR(50) NOT NULL,
  `label` VARCHAR(50) NOT NULL,
  `address_line` VARCHAR(255) NOT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  KEY `customer_addresses_email_idx` (`customer_email`),
  CONSTRAINT `customer_addresses_customer_fk` FOREIGN KEY (`customer_email`) REFERENCES `customers` (`email`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `customer_addresses` (`customer_email`, `label`, `address_line`, `is_default`)
SELECT `email`, 'Home', `address`, 1 FROM `customers` WHERE TRIM(`address`) <> '';
CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key` VARCHAR(80) NOT NULL PRIMARY KEY,
  `setting_value` TEXT NOT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `site_gallery_images` (
  `image_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `image_path` VARCHAR(255) NOT NULL,
  `caption` VARCHAR(120) NOT NULL DEFAULT '',
  `category_name` VARCHAR(50) NOT NULL DEFAULT '',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `is_auto_hidden` TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `site_gallery_images` (`image_path`,`caption`,`sort_order`) VALUES
('image/resturent1.jpg','Restaurant interior',1),
('image/cafe.jpg','Cafe',2),
('image/dinning.jpg','Dining',3),
('image/moderndinning.jpg','Modern dining',4),
('image/res-food.jpg','Fresh food',5),
('image/GarlicChickenCheeseBall.jpg','Garlic chicken cheese balls',6),
('image/Green Tea Creame Frappuccino.jpg','Green tea frappe',7),
('image/18.jpg','Dessert',8),
('image/15.jpeg','Foodera dish',9),
('image/9.jpg','Chocolate mousse',10),
('image/23.png','Foodera dish',11);

-- Admin-managed public offers.
CREATE TABLE IF NOT EXISTS `offer_categories` (
  `offer_category_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(80) NOT NULL,
  `description` VARCHAR(255) NOT NULL DEFAULT '',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `offer_categories_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `offers` (
  `offer_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `offer_category_id` INT NOT NULL,
  `title` VARCHAR(140) NOT NULL,
  `description` VARCHAR(600) NOT NULL,
  `terms` TEXT NOT NULL,
  `code` VARCHAR(40) DEFAULT NULL,
  `discount_type` ENUM('percentage','fixed','free_delivery') NOT NULL DEFAULT 'percentage',
  `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `maximum_discount` DECIMAL(10,2) DEFAULT NULL,
  `minimum_order` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `applicable_food_category` VARCHAR(80) DEFAULT NULL,
  `customer_scope` ENUM('all','first_order') NOT NULL DEFAULT 'all',
  `starts_at` DATETIME NOT NULL,
  `ends_at` DATETIME NOT NULL,
  `total_usage_limit` INT DEFAULT NULL,
  `per_customer_limit` INT NOT NULL DEFAULT 1,
  `daily_usage_limit` INT DEFAULT NULL,
  `is_automatic` TINYINT(1) NOT NULL DEFAULT 0,
  `is_combinable` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `image_path` VARCHAR(255) NOT NULL DEFAULT '',
  `priority` INT NOT NULL DEFAULT 0,
  `minimum_eligible_subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `days_of_week` VARCHAR(32) NOT NULL DEFAULT '',
  `daily_start_time` TIME DEFAULT NULL,
  `daily_end_time` TIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `offers_code_unique` (`code`),
  KEY `offers_active_dates_idx` (`is_active`,`starts_at`,`ends_at`),
  CONSTRAINT `offers_category_fk` FOREIGN KEY (`offer_category_id`) REFERENCES `offer_categories` (`offer_category_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `offer_redemptions` (
  `redemption_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `offer_id` INT NOT NULL,
  `order_id` INT DEFAULT NULL,
  `customer_email` VARCHAR(100) NOT NULL,
  `offer_code` VARCHAR(40) DEFAULT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `discount_amount` DECIMAL(10,2) NOT NULL,
  `redeemed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `offer_redemptions_offer_idx` (`offer_id`,`redeemed_at`),
  KEY `offer_redemptions_customer_idx` (`customer_email`,`offer_id`,`redeemed_at`),
  KEY `offer_redemptions_order_idx` (`order_id`),
  CONSTRAINT `offer_redemptions_offer_fk` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`offer_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
