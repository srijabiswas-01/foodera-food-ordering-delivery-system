# Foodera

Foodera is a role-based food ordering and delivery management system for a small restaurant or local food-delivery business. It brings the customer ordering journey, restaurant operations, rider delivery work, and management reporting into one practical web application.

The project is designed for conventional PHP/MySQL hosting, including shared hosting such as InfinityFree. It avoids paid maps, payment gateways, live GPS services, or third-party dispatch platforms so that the full workflow can be demonstrated and operated with a standard PHP hosting account.

## Why Foodera was built

Many small food businesses handle orders through calls, messages, and disconnected spreadsheets. That makes it difficult to answer basic operational questions:

- Has the restaurant accepted the order?
- Has the kitchen started preparing it?
- Which rider is responsible for delivery?
- Was cash on delivery collected?
- Is a cancellation or refund waiting for approval?
- What does the customer see while waiting?

Foodera addresses those questions with one consistent order record and separate views for customers, administrators, and riders. The purpose is not only to place orders; it is to make each decision visible, traceable, and manageable.

## People who use the system

| Role | Main responsibility |
| --- | --- |
| Customer | Browse food, manage cart, save addresses, place orders, track delivery, request cancellation, review completed orders, and read blog content. |
| Administrator | Manage menu, categories, offers, customers, orders, cancellations, refunds, riders, delivery zones, public content, notifications, reports, and testimonials. |
| Rider | View only assigned deliveries, update delivery progress, verify delivery through OTP, record delivery issues, view delivery history, and maintain personal profile details. |

Each role has a separate login/session area so that customers cannot access admin operations and riders can access only their own delivery work.

## Core customer journey

1. A customer browses the menu, offers, gallery, blog, and business information.
2. The customer adds dishes to the cart and selects a saved delivery address.
3. The system records an order snapshot: dishes, quantities, item prices, offer calculation, address, payment method, and final amount.
4. The customer follows the order from placement to delivery through **My Orders** and **Track Order**.
5. After delivery, the customer can confirm receipt, raise a concern, or submit a service review.

This approach keeps historical orders accurate even if a menu price, category, or offer changes later.

## Order and delivery workflow

Foodera uses a forward-moving operational workflow:

```text
Order placed
→ Confirmed
→ Preparing
→ Ready for pickup
→ Rider assigned
→ Picked up
→ Out for delivery
→ Delivered
→ Completed
```

### What the statuses mean

- **Order placed** — The customer order has been received.
- **Confirmed** — The restaurant accepts the order and can fulfil it.
- **Preparing** — Kitchen work has started. Normal online cancellation is no longer available.
- **Ready for pickup** — The order is ready for dispatch.
- **Rider assigned** — An administrator assigns an eligible rider, considering availability and delivery zone.
- **Picked up** — The rider confirms collection from the restaurant.
- **Out for delivery** — The rider is travelling to the customer. A delivery OTP can be generated for verification.
- **Delivered** — The rider verifies delivery. COD collection is recorded where applicable.
- **Completed** — The customer confirms receipt or the order is closed as completed.

The order status, payment status, refund status, and delivery assignment are intentionally separate. For example, a delivered COD order can still require collection reconciliation, while an online-payment refund can be processed without changing delivery history.

## Cancellation and refund approach

Foodera follows a controlled, real-world model rather than permanently deleting information.

- Customers may request cancellation before preparation starts.
- A customer can request removal of one dish or cancellation of the full order.
- Each request has a reason and optional note.
- Administrators approve or reject the request.
- Cancelled dishes remain part of the order history with their outcome and refund information.
- Offers and totals are recalculated when a partial cancellation affects eligibility.
- Refund handling is recorded independently as: `Not required`, `Requested`, `Approved`, `Processing`, `Processed`, `Rejected`, or `Failed`.

This preserves a reliable audit trail for customer support and MIS reporting.

## Delivery management without paid services

The delivery module is deliberately suitable for shared hosting.

- Admins create rider accounts and control availability.
- Admins manage delivery cities and zones.
- Customers choose from active delivery zones when saving addresses.
- Riders can be assigned to suitable orders after the kitchen marks them ready.
- Riders see only their assigned jobs and use a delivery OTP to verify delivery.
- COD collection is recorded as part of the rider delivery workflow.
- Rider history provides completed deliveries, reported issues, and basic performance information.

The system does not require live maps, SMS APIs, a paid payment gateway, or background workers. Notifications are stored inside Foodera and shown to the relevant user after sign-in.

## Administration and MIS

The administration area supports the daily operations of a restaurant:

- Dashboard with recent orders, financial snapshot, and management shortcuts.
- Menu and category management, including active/inactive visibility.
- Offer creation, eligibility rules, dates, usage, and availability controls.
- Order management with details, status progression, cancellation queues, refund queues, and customer notifications.
- Delivery operations for riders, zones, dispatch assignments, and completed delivery records.
- Customer account controls.
- Blog categories, post publishing, comments, share metadata, and testimonial moderation.
- Public-site settings for branding, content, gallery images, and appearance presets.
- Reports for order volume, revenue, discounts, payment methods, refunds, and operational status.

## Public content and trust

The public site contains more than the order flow. It includes About, Gallery, Blog, Offers, Contact, policies, and customer account pages. Approved reviews can appear as public testimonials only when the customer has given consent. Blog likes and comments require a signed-in customer, while sharing remains public.

The policy pages explain cancellation, refunds, privacy, terms, and ethical use. Customers are asked to accept the relevant conditions at the appropriate stage of the journey.

## Project structure

```text
foodera/
├── admin/          Administration dashboard and management pages
├── rider/          Rider portal, delivery workflow, and rider-only assets
│   ├── css/        Rider styles
│   ├── includes/   Rider authentication and shared helpers
│   └── js/         Rider-only browser scripts
├── includes/       Shared public layout and application helpers
├── css/            Public/customer-facing styles
├── js/             Public/customer-facing scripts
├── assets/         Branding and static assets
├── uploads/        Admin-uploaded content such as food, offer, blog, and gallery images
├── db/             Database schema and migration files
└── root PHP pages  Public/customer-facing pages
```

Public pages intentionally remain at the project root so they work with shared hosting document roots. The admin and rider areas are separated into their own folders.

## Installation overview

1. Create a MySQL database through the hosting control panel.
2. Import `db/foodera.sql` for a fresh installation.
3. Configure the database connection details in `config.php`.
4. Upload the full project contents to the hosting web root (`htdocs`, `public_html`, or equivalent).
5. Ensure PHP can write to the `uploads/` directory.
6. Sign in to the administrator area and configure menu categories, dishes, delivery zones, riders, offers, and public-site content.

For an existing database, run the relevant migration once before using its related feature:

| Migration | Purpose |
| --- | --- |
| `db/hosting-migration.sql` | Hosting/database compatibility updates. |
| `db/site_content_migration.sql` | Public branding, gallery, and page-content management. |
| `db/customer_profile_migration.sql` | Customer profiles, preferences, photos, and saved addresses. |
| `db/offers_migration.sql` | Offer and discount management. |

Back up the database before importing any migration on an existing site.

## Hosting notes

- Upload folders with their original names and structure; relative paths are important.
- Upload `rider/css/` together with the Rider PHP pages. Rider pages use styles from that folder.
- Upload the `assets/`, `css/`, `js/`, `includes/`, `uploads/`, `admin/`, and `rider/` directories, as well as all root PHP files.
- The `legacy/` folder contains older material and is not needed for the live site unless a specific legacy page still depends on it.
- Keep database credentials private and do not commit production credentials to a public repository.

## Project boundaries

Foodera is a practical academic/small-business MIS implementation. For a production-scale service, future upgrades could include a verified payment gateway, transactional email/SMS provider, maps, real-time rider location, background queues, stronger rate limiting, security monitoring, and automated backups. These are intentionally outside the current shared-hosting scope.

## Documentation

For the detailed operational/MIS explanation, see [mis-documentation.md](mis-documentation.md).
