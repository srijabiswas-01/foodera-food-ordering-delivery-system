# Foodera PHP/MySQL Hosting Setup

## 1. Database

Create a MySQL database in your hosting panel and import:

`db/foodera.sql`

If the old database is already imported, run:

`db/hosting-migration.sql`

To enable admin-managed public images, gallery, branding, and page copy on an existing installation, also run `db/site_content_migration.sql` once. New installs using `db/foodera.sql` already include it. The web server needs write access to `uploads/site` (PHP creates this directory on first upload).

For an existing installation, also run `db/customer_profile_migration.sql` once. It adds customer photos, food preferences, multiple saved addresses, and larger password/address columns. New installations using `db/foodera.sql` already include these tables.

## 2. Configure PHP

Edit `config.php` and set the database values from InfinityFree/cPanel:

```php
define('DB_HOST', 'sqlXXX.infinityfree.com');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_NAME', 'your_database_name');
```

## 3. Upload

Upload the full project folder contents to your hosting `htdocs` / public web root.

Important folders to upload:

- `admin`
- `css`
- `db`
- `fonts`
- `image`
- `img`
- `js`
- `media`
- `profilepic`
- `uploads` (or allow PHP to create `uploads/profiles` for customer photos)
- all root `.php` files
- `config.php`

The `legacy` folder contains old duplicate/prototype files and is not required for the live site.

## MIS / Order Logic

- `menu.php` reads food items from MySQL table `foods`.
- `manage-cart.php` stores selected items in the PHP session cart.
- `cart.php` collects customer delivery details.
- `order.php` inserts the customer order into `orders` and item rows into `order_items`.
- `payment.php` inserts payment details into `payments`.
- `bill.php` now reads the latest placed order and items from MySQL.
- `admin/order-details.php` shows all orders and their ordered items.

Full MIS details are documented in `mis-documentation.md`.
## Offers

For an existing database, import `db/offers_migration.sql` once. Manage offer categories and offers at `admin/offers.php`. The public page is `offers.php`; eligibility is checked in the cart and again during checkout.

# foodera-food-ordering-delivery-system
