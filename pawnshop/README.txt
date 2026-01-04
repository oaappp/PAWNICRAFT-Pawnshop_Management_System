# Pawnshop Management System (PHP/MySQL)

A role-based pawnshop management system for daily operations: customers, pawn tickets (multi-item), interest calculations, renewals, redemptions, auctions (reporting), and comprehensive reports — designed for XAMPP (Apache, PHP, MySQL).

- Backend: PHP 8.1+, MySQL 8.0+, Apache (via XAMPP)
- Frontend: Bootstrap 5, jQuery, DataTables, Chart.js, SweetAlert2
- PDFs: Works with TCPDF or FPDF (manual include)
- No Composer required (you can drop libraries under `public/vendor/`)

---

## Table of Contents

- Requirements
- Install the Tools
- Project Structure
- Local Setup (XAMPP)
- Database Schema (quick start)
- Configuration
- Running the App
- Core Features & Pages
- Automation (Cron / Task Scheduler)
- Troubleshooting
- Create and Push to GitHub
- Security Notes
- License

---

## Requirements

- XAMPP bundle (includes Apache, PHP, MySQL)
  - Apache 2.4
  - PHP 8.1+
  - MySQL 8.0+
- Browser: Chrome/Edge/Firefox
- PDF library (optional if you print PDFs)
  - TCPDF or FPDF (place under `public/vendor/` and update paths in `pawns/receipt.php`)
- Admin access to create a database (phpMyAdmin or MySQL CLI)

Optional:
- Git (to clone/push the repository)
- VS Code or your preferred editor

---

## Install the Tools

1) XAMPP
- Download: https://www.apachefriends.org/index.html
- Install and open XAMPP Control Panel.
- Start Apache and MySQL.

2) Git (optional, for GitHub)
- Download: https://git-scm.com/downloads
- Verify: `git --version`

3) PDF Library (if using PDF receipts)
- TCPDF: https://tcpdf.org/ or https://github.com/tecnickcom/TCPDF
- FPDF: http://www.fpdf.org/
- Extract into `public/vendor/` (e.g., `public/vendor/tcpdf/` or `public/vendor/fpdf/`)
- Update the require/include path in `pawns/receipt.php` to match where you placed it.

---

## Project Structure (current)

```text
PAWNSHOP_MANAGEMENT_SYSTEM
├── .vscode/launch.json
├── config/
│   ├── config.php
│   └── database.php
├── cron/
│   ├── backup_db.php
│   └── daily_tasks.php
├── includes/
│   ├── audit.php
│   ├── auth_check.php
│   ├── calc_helper.php
│   ├── csrf.php
│   ├── db.php
│   ├── footer.php
│   ├── header.php
│   ├── ids.php
│   ├── settings.php
│   ├── sidebar.php
│   └── upload.php
├── public/
│   ├── assets/
│   │   ├── css/custom.css
│   │   ├── image/
│   │   ├── js/custom.js
│   │   └── uploads/
│   ├── ids/
│   ├── items/
│   ├── vendor/
│   └── make_hash.php
├── auctions/
├── backups/
│   └── manual_backup.php
├── customers/
│   ├── create.php
│   ├── edit.php
│   ├── list.php
│   └── view.php
├── pawns/
│   ├── create.php
│   ├── list.php
│   ├── receipt.php
│   ├── redeem.php
│   ├── renew.php
│   └── view.php
├── reports/
│   ├── active_loans.php
│   ├── auction.php
│   ├── cash_flow.php
│   ├── customer_history.php
│   └── daily.php
├── users/
│   ├── create.php
│   ├── edit.php
│   └── list.php
├── dashboard.php
├── diagnostic.php
├── index.php
├── login.php
├── logout.php
└── make_hash.php

==========================================================================

Local Setup (XAMPP)
Put the project into htdocs
Windows: C:\xampp\htdocs\PAWNSHOP_MANAGEMENT_SYSTEM
macOS: /Applications/XAMPP/htdocs/PAWNSHOP_MANAGEMENT_SYSTEM
Linux (XAMPP): /opt/lampp/htdocs/PAWNSHOP_MANAGEMENT_SYSTEM
Create the database
Open phpMyAdmin: http://localhost/phpmyadmin/
Create a database (e.g., pawnshop_db, utf8mb4 collation recommended).
Import schema
If you already have a schema, import it.
If not, use the “Database Schema (quick start)” section below.
Set file permissions (if needed)
Ensure writable by Apache/PHP:
public/assets/uploads/
public/ids/
public/items/
backups/
Start Apache and MySQL in XAMPP.

Create the first admin user (see “Running the App” below).


=============================================================================


DATABASE CODES:

SET NAMES utf8mb4;

CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(120),
  role ENUM('admin','cashier','appraiser') NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  last_login DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE customers (
  customer_id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  middle_name VARCHAR(80),
  date_of_birth DATE,
  gender VARCHAR(20),
  contact_number VARCHAR(30),
  email VARCHAR(120),
  address_line1 VARCHAR(150),
  address_line2 VARCHAR(150),
  city VARCHAR(80),
  province VARCHAR(80),
  postal_code VARCHAR(15),
  id_type VARCHAR(50) NOT NULL,
  id_number VARCHAR(100) NOT NULL,
  id_image_path VARCHAR(255),
  registration_date DATE NOT NULL DEFAULT (CURRENT_DATE),
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_customer_id (id_type, id_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pawn_transactions (
  transaction_id INT AUTO_INCREMENT PRIMARY KEY,
  pawn_ticket_number VARCHAR(30) NOT NULL UNIQUE,
  customer_id INT NOT NULL,
  appraiser_id INT NOT NULL,
  transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  loan_amount DECIMAL(12,2) NOT NULL,
  interest_rate DECIMAL(6,4) NOT NULL,
  maturity_date DATE NOT NULL,
  grace_period_end DATE NOT NULL,
  status ENUM('active','redeemed','renewed','expired','auctioned') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pt_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
  CONSTRAINT fk_pt_appraiser FOREIGN KEY (appraiser_id) REFERENCES users(user_id),
  INDEX idx_pt_customer (customer_id),
  INDEX idx_pt_dates (maturity_date, grace_period_end),
  INDEX idx_pt_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pawned_items (
  item_id INT AUTO_INCREMENT PRIMARY KEY,
  transaction_id INT NOT NULL,
  item_category VARCHAR(50) NOT NULL,
  item_description TEXT,
  brand VARCHAR(80),
  model VARCHAR(80),
  appraised_value DECIMAL(12,2) NOT NULL,
  item_image_path VARCHAR(255),
  serial_number VARCHAR(120),
  condition_notes TEXT,
  CONSTRAINT fk_pi_txn FOREIGN KEY (transaction_id) REFERENCES pawn_transactions(transaction_id) ON DELETE CASCADE,
  INDEX idx_pi_txn (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payments (
  payment_id INT AUTO_INCREMENT PRIMARY KEY,
  transaction_id INT NOT NULL,
  payment_type ENUM('redemption','renewal','partial') NOT NULL,
  payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  principal_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  interest_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  penalty_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total_amount DECIMAL(12,2) NOT NULL,
  payment_method ENUM('cash','bank_transfer') NOT NULL DEFAULT 'cash',
  processed_by INT NOT NULL,
  receipt_number VARCHAR(40),
  CONSTRAINT fk_pay_txn FOREIGN KEY (transaction_id) REFERENCES pawn_transactions(transaction_id),
  CONSTRAINT fk_pay_user FOREIGN KEY (processed_by) REFERENCES users(user_id),
  INDEX idx_pay_txn_date (transaction_id, payment_date),
  INDEX idx_pay_type (payment_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE renewals (
  renewal_id INT AUTO_INCREMENT PRIMARY KEY,
  transaction_id INT NOT NULL,
  renewal_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  old_maturity_date DATE NOT NULL,
  new_maturity_date DATE NOT NULL,
  interest_paid DECIMAL(12,2) NOT NULL,
  processed_by INT NOT NULL,
  CONSTRAINT fk_ren_txn FOREIGN KEY (transaction_id) REFERENCES pawn_transactions(transaction_id),
  CONSTRAINT fk_ren_user FOREIGN KEY (processed_by) REFERENCES users(user_id),
  INDEX idx_ren_txn_date (transaction_id, renewal_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE auctions (
  auction_id INT AUTO_INCREMENT PRIMARY KEY,
  transaction_id INT NOT NULL,
  auction_date DATE NOT NULL,
  starting_price DECIMAL(12,2) NOT NULL,
  winning_bid DECIMAL(12,2),
  bidder_name VARCHAR(120),
  bidder_contact VARCHAR(120),
  status ENUM('pending','sold','cancelled') NOT NULL DEFAULT 'pending',
  processed_by INT NOT NULL,
  CONSTRAINT fk_auc_txn FOREIGN KEY (transaction_id) REFERENCES pawn_transactions(transaction_id),
  CONSTRAINT fk_auc_user FOREIGN KEY (processed_by) REFERENCES users(user_id),
  INDEX idx_auc_date (auction_date),
  INDEX idx_auc_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE audit_logs (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action_type VARCHAR(80) NOT NULL,
  table_affected VARCHAR(80),
  record_id BIGINT,
  old_values JSON,
  new_values JSON,
  ip_address VARCHAR(45),
  timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
  INDEX idx_audit_time (timestamp),
  INDEX idx_audit_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


===============================================================================

Configuration
Database credentials
Open config/config.php and/or config/database.php and set:
DB host, name, username, password
Charset (utf8mb4)
If defined, set BASE_URL — e.g., /PAWNSHOP_MANAGEMENT_SYSTEM/
Upload paths
Ensure your upload helper (includes/upload.php) points to existing directories:
public/assets/uploads/ (and/or public/ids/, public/items/ depending on your config)
Timezone (recommended)
In php.ini, set: date.timezone = Asia/Manila (or your timezone)
Restart Apache after changes.
PDF library path (if printing PDFs)
In pawns/receipt.php, update the require_once path to your TCPDF/FPDF install under public/vendor/.
Running the App
Start services
In XAMPP Control Panel, start Apache and MySQL.
Create first admin user
Open a browser:
http://localhost/PAWNSHOP_MANAGEMENT_SYSTEM/public/make_hash.php (or http://localhost/PAWNSHOP_MANAGEMENT_SYSTEM/make_hash.php)

Generate a password hash and insert into MySQL:
INSERT INTO users (username, password_hash, full_name, email, role, status)
VALUES ('admin', '<paste_hash_here>', 'Administrator', 'admin@local', 'admin', 'active');

Access the app
Visit: http://localhost/PAWNSHOP_MANAGEMENT_SYSTEM/
Log in with your admin credentials.

======================================================================================


Core Features & Pages
Dashboard: /dashboard.php
Customers: /customers/list.php → Add /customers/create.php
Pawns: /pawns/list.php → New /pawns/create.php → Ticket /pawns/receipt.php
Renewals: /pawns/renew.php?ticket=PT-...
Redemptions: /pawns/redeem.php?ticket=PT-...
Reports:
Daily: /reports/daily.php
Active Loans: /reports/active_loans.php
Cash Flow: /reports/cash_flow.php
Auction: /reports/auction.php
Customer History: /reports/customer_history.php
Users: /users/list.php
Backups: /backups/manual_backup.php

