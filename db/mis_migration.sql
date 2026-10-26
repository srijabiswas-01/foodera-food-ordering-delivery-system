-- Foodera MIS migration. Run once on an existing database before enabling
-- the operations modules. Existing orders and customer data are preserved.

ALTER TABLE order_tracking MODIFY status ENUM(
  'payment_pending','placed','confirmed','preparing','ready_for_pickup',
  'rider_assigned','picked_up','out_for_delivery','delivered','completed',
  'cancelled','rejected','payment_failed','delivery_failed','returned'
) NOT NULL DEFAULT 'placed';

ALTER TABLE order_payments MODIFY payment_status ENUM(
  'pending','payment_requested','authorized','paid','failed',
  'partially_refunded','refunded'
) NOT NULL DEFAULT 'pending';

ALTER TABLE order_payments ADD COLUMN provider_name VARCHAR(80) DEFAULT NULL AFTER reference_label;
ALTER TABLE order_payments ADD COLUMN provider_transaction_id VARCHAR(160) DEFAULT NULL AFTER provider_name;
ALTER TABLE order_payments ADD COLUMN verified_at TIMESTAMP NULL DEFAULT NULL AFTER provider_transaction_id;

CREATE TABLE IF NOT EXISTS order_audit_logs (
  audit_id BIGINT AUTO_INCREMENT PRIMARY KEY, orderid INT NOT NULL,
  actor_type ENUM('admin','customer','system','rider') NOT NULL DEFAULT 'system',
  actor_identifier VARCHAR(120) NOT NULL DEFAULT 'system', action VARCHAR(80) NOT NULL,
  previous_value VARCHAR(120) DEFAULT NULL, new_value VARCHAR(120) DEFAULT NULL,
  reason VARCHAR(500) DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY order_audit_order_idx(orderid), KEY order_audit_action_idx(action,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS delivery_riders (
  rider_id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, phone VARCHAR(30) NOT NULL,
  email VARCHAR(100) DEFAULT NULL, password_hash VARCHAR(255) DEFAULT NULL,
  vehicle_type VARCHAR(60) NOT NULL DEFAULT '', delivery_zone VARCHAR(120) NOT NULL DEFAULT '',
  availability ENUM('offline','available','busy','on_break') NOT NULL DEFAULT 'offline',
  is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY delivery_riders_email_unique(email), KEY delivery_riders_available_idx(is_active,availability)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_delivery_assignments (
  assignment_id INT AUTO_INCREMENT PRIMARY KEY, orderid INT NOT NULL, rider_id INT NOT NULL,
  status ENUM('assigned','accepted','picked_up','out_for_delivery','delivered','failed','reassigned') NOT NULL DEFAULT 'assigned',
  assigned_by VARCHAR(120) NOT NULL, assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  accepted_at TIMESTAMP NULL DEFAULT NULL, delivered_at TIMESTAMP NULL DEFAULT NULL,
  failure_reason VARCHAR(500) DEFAULT NULL, is_current TINYINT(1) NOT NULL DEFAULT 1,
  KEY order_delivery_order_idx(orderid,is_current), KEY order_delivery_rider_idx(rider_id,is_current)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS delivery_otps (
  otp_id BIGINT AUTO_INCREMENT PRIMARY KEY, orderid INT NOT NULL UNIQUE,
  otp_hash VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, verified_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY delivery_otps_expiry_idx(expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cod_collections (
  collection_id BIGINT AUTO_INCREMENT PRIMARY KEY, orderid INT NOT NULL UNIQUE, rider_id INT NOT NULL,
  expected_amount DECIMAL(10,2) NOT NULL, collected_amount DECIMAL(10,2) DEFAULT NULL,
  status ENUM('pending','recorded','reconciled','disputed') NOT NULL DEFAULT 'pending',
  recorded_at TIMESTAMP NULL DEFAULT NULL, reconciled_at TIMESTAMP NULL DEFAULT NULL,
  admin_note VARCHAR(500) DEFAULT NULL, KEY cod_collections_status_idx(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS support_tickets (
  ticket_id INT AUTO_INCREMENT PRIMARY KEY, ticket_reference VARCHAR(30) NOT NULL UNIQUE,
  customer_email VARCHAR(100) NOT NULL, orderid INT DEFAULT NULL, category VARCHAR(60) NOT NULL DEFAULT 'other',
  subject VARCHAR(180) NOT NULL, status ENUM('open','in_progress','waiting_for_customer','resolved','closed') NOT NULL DEFAULT 'open',
  assigned_admin VARCHAR(100) DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY support_tickets_customer_idx(customer_email,status), KEY support_tickets_status_idx(status,updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS support_ticket_messages (
  message_id INT AUTO_INCREMENT PRIMARY KEY, ticket_id INT NOT NULL,
  sender_type ENUM('customer','admin') NOT NULL, sender_identifier VARCHAR(100) NOT NULL,
  message TEXT NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY support_ticket_messages_idx(ticket_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notification_jobs (
  job_id BIGINT AUTO_INCREMENT PRIMARY KEY, event_key VARCHAR(190) NOT NULL,
  recipient_type ENUM('customer','admin','rider') NOT NULL, recipient_identifier VARCHAR(120) NOT NULL DEFAULT '',
  channel ENUM('in_app','email','sms','whatsapp','push') NOT NULL DEFAULT 'in_app',
  title VARCHAR(160) NOT NULL, body VARCHAR(500) NOT NULL, link_path VARCHAR(255) NOT NULL DEFAULT '',
  status ENUM('pending','processing','sent','failed') NOT NULL DEFAULT 'pending',
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, provider_response VARCHAR(500) DEFAULT NULL,
  available_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, sent_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY notification_jobs_dedupe(event_key,recipient_type,recipient_identifier,channel),
  KEY notification_jobs_queue_idx(status,available_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS system_settings (
  setting_key VARCHAR(100) NOT NULL PRIMARY KEY, setting_value TEXT NOT NULL,
  is_sensitive TINYINT(1) NOT NULL DEFAULT 0, updated_by VARCHAR(120) DEFAULT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Never import the historical `payments` table from db/foodera.sql in a live
-- deployment. It contains unsafe legacy card data and is not used by MIS.
