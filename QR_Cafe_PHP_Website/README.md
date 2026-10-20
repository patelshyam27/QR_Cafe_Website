# QR Cafe — Smart Dining Website (HTML5, Advanced CSS3, PHP & SQL)

A complete, modern, responsive QR-based cafe management and ordering website built using **HTML5**, **Advanced CSS3**, **PHP 8.3**, and **SQL (SQLite/MySQL via PDO)**.

---

## 🚀 Quick Start (1 Click!)

Inside `QR_Cafe_PHP_Website`:

1. **Double-click `run.bat`** (or open terminal here and type):
   ```bash
   php -S localhost:8000
   ```
2. Open your web browser to:
   👉 **http://localhost:8000**

---

## 🧭 Page Navigation & Features

| Page | URL | Purpose |
| :--- | :--- | :--- |
| **Home / Landing** | `http://localhost:8000/` | Hero banner, features, quick reservation CTA |
| **Digital Menu** | `http://localhost:8000/menu.php` | Category filters, live search, prices, "+ Add to Cart" |
| **Table QRs Standees** | `http://localhost:8000/qr_generator.php` | Printable QR standees for every table (`T01`, `T02`, etc.) |
| **My Order / Checkout** | `http://localhost:8000/checkout.php` | Cart review, table selection, special cooking notes, send to kitchen |
| **Live Order Tracking** | `http://localhost:8000/track_order.php?order_id=1` | Animated 3-stage kitchen progress tracker |
| **Reserve a Table** | `http://localhost:8000/book.php` | Table reservation form with 24h overlap validation |
| **Admin Dashboard** | `http://localhost:8000/admin/index.php` | KPI metrics (Revenue, Total Orders, Active Bookings) |
| **Kitchen KDS Screen** | `http://localhost:8000/admin/kitchen.php` | Live kitchen display for chefs with "Start Cooking" & "Mark Served" |
| **Orders Management** | `http://localhost:8000/admin/orders.php` | Detailed orders history with status filters |
| **Bookings Management** | `http://localhost:8000/admin/bookings.php` | View customer reservations via SQL Multi-Table JOIN |
| **Manage Menu (CRUD)** | `http://localhost:8000/admin/menu_manage.php` | Add items, edit price, toggle in-stock / out-of-stock, delete |
| **Database Inspector** | `http://localhost:8000/admin/db_viewer.php` | In-browser visual database inspector (view all tables & records) |

---

## 🗄️ Database Architecture & Syllabus Mapping

The database is powered by **PDO** in `config/db.php`:
* Default: **SQLite** (`cafe.db` inside the project folder — 0 configuration required).
* MySQL: Set `define('DB_TYPE', 'mysql');` in `config/db.php` if using XAMPP for college practical submission.

### Tables:
1. `cafe_tables`: Table numbers (`T01`–`T06`), seating capacity, availability.
2. `customers`: Customer profiles (`customer_name`, `phone`, `age`, `email`).
3. `bookings`: Reservations connecting tables to customers with dates and time intervals.
4. `menu_items`: Food and drink catalog with prices, categories, and descriptions.
5. `cafe_orders`: Order headers with table numbers, customer names, total amount, and status.
6. `order_items`: Line items mapping order IDs to menu items with quantities and unit prices.
7. `audit_log`: Logs all DML inserts/updates for auditing.
