<?php
// ============================================================================
// QR CAFE WEBSITE - DATABASE CONFIGURATION & INITIALIZATION (PDO)
// Supports: SQLite (Default: 0 configuration) & MySQL (XAMPP)
// ============================================================================

define('DB_TYPE', 'sqlite'); // Options: 'sqlite' or 'mysql'

// SQLite Configuration
define('SQLITE_FILE', __DIR__ . '/../cafe.db');

// MySQL Configuration (For XAMPP if needed)
define('MYSQL_HOST', 'localhost');
define('MYSQL_PORT', '3306');
define('MYSQL_DB', 'cafe_db');
define('MYSQL_USER', 'root');
define('MYSQL_PASS', '');

function getDB() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        if (DB_TYPE === 'sqlite') {
            $pdo = new PDO('sqlite:' . SQLITE_FILE);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA foreign_keys = ON;');
        } else {
            $dsn = "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DB . ";charset=utf8mb4";
            $pdo = new PDO($dsn, MYSQL_USER, MYSQL_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        }
    } catch (PDOException $e) {
        die("<div style='font-family:sans-serif;padding:30px;background:#fee2e2;color:#991b1b;border-radius:8px;'>
             <h3>Database Connection Failed</h3><p>{$e->getMessage()}</p></div>");
    }

    return $pdo;
}

// Automatically create tables & starter data on first run
function initDatabase() {
    $db = getDB();
    
    // 1. Tables Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS cafe_tables (
            table_id INTEGER PRIMARY KEY " . (DB_TYPE === 'sqlite' ? "AUTOINCREMENT" : "AUTO_INCREMENT") . ",
            table_number VARCHAR(10) NOT NULL UNIQUE,
            capacity INT DEFAULT 2 NOT NULL,
            table_status VARCHAR(20) DEFAULT 'AVAILABLE' NOT NULL
        )
    ");

    // 2. Customers Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS customers (
            customer_id INTEGER PRIMARY KEY " . (DB_TYPE === 'sqlite' ? "AUTOINCREMENT" : "AUTO_INCREMENT") . ",
            customer_name VARCHAR(100) NOT NULL,
            phone VARCHAR(15) NOT NULL UNIQUE,
            age INT DEFAULT 18,
            email VARCHAR(100)
        )
    ");

    // 3. Bookings Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS bookings (
            booking_id INTEGER PRIMARY KEY " . (DB_TYPE === 'sqlite' ? "AUTOINCREMENT" : "AUTO_INCREMENT") . ",
            table_id INT NOT NULL,
            customer_id INT NOT NULL,
            booking_date VARCHAR(15) NOT NULL,
            start_time VARCHAR(10) NOT NULL,
            end_time VARCHAR(10) NOT NULL,
            booking_status VARCHAR(20) DEFAULT 'CONFIRMED' NOT NULL,
            FOREIGN KEY (table_id) REFERENCES cafe_tables(table_id) ON DELETE CASCADE,
            FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE
        )
    ");

    // 4. Menu Items Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS menu_items (
            item_id INTEGER PRIMARY KEY " . (DB_TYPE === 'sqlite' ? "AUTOINCREMENT" : "AUTO_INCREMENT") . ",
            item_name VARCHAR(100) NOT NULL UNIQUE,
            price DECIMAL(8,2) NOT NULL,
            category VARCHAR(40) NOT NULL,
            description TEXT,
            image_url VARCHAR(255),
            availability CHAR(1) DEFAULT 'Y' NOT NULL
        )
    ");

    // 5. Orders Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS cafe_orders (
            order_id INTEGER PRIMARY KEY " . (DB_TYPE === 'sqlite' ? "AUTOINCREMENT" : "AUTO_INCREMENT") . ",
            table_number VARCHAR(10) NOT NULL,
            customer_name VARCHAR(100) DEFAULT 'Guest' NOT NULL,
            order_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            total_amount DECIMAL(10,2) DEFAULT 0 NOT NULL,
            order_status VARCHAR(20) DEFAULT 'PLACED' NOT NULL,
            notes TEXT
        )
    ");

    // 6. Order Items Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            order_item_id INTEGER PRIMARY KEY " . (DB_TYPE === 'sqlite' ? "AUTOINCREMENT" : "AUTO_INCREMENT") . ",
            order_id INT NOT NULL,
            item_id INT NOT NULL,
            quantity INT DEFAULT 1 NOT NULL,
            unit_price DECIMAL(8,2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES cafe_orders(order_id) ON DELETE CASCADE,
            FOREIGN KEY (item_id) REFERENCES menu_items(item_id)
        )
    ");

    // 7. Audit Log Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS audit_log (
            log_id INTEGER PRIMARY KEY " . (DB_TYPE === 'sqlite' ? "AUTOINCREMENT" : "AUTO_INCREMENT") . ",
            table_name VARCHAR(50) NOT NULL,
            operation VARCHAR(20) NOT NULL,
            change_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            details TEXT
        )
    ");

    // Starter Tables
    $count = $db->query("SELECT COUNT(*) FROM cafe_tables")->fetchColumn();
    if ($count == 0) {
        $stmt = $db->prepare("INSERT INTO cafe_tables (table_number, capacity, table_status) VALUES (?, ?, ?)");
        $tables = [
            ['T01', 2, 'AVAILABLE'],
            ['T02', 4, 'AVAILABLE'],
            ['T03', 4, 'AVAILABLE'],
            ['T04', 6, 'AVAILABLE'],
            ['T05', 2, 'AVAILABLE'],
            ['T06', 8, 'AVAILABLE']
        ];
        foreach ($tables as $t) {
            $stmt->execute($t);
        }
    }

    // Starter Menu Items
    $menuCount = $db->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
    if ($menuCount == 0) {
        $stmt = $db->prepare("INSERT INTO menu_items (item_name, price, category, description, availability) VALUES (?, ?, ?, ?, 'Y')");
        $items = [
            ['Signature Cold Coffee', 120.00, 'Beverages', 'Thick, creamy blended espresso served chilled with chocolate syrup.'],
            ['Caramel Macchiato', 150.00, 'Beverages', 'Freshly brewed espresso with steamed milk and rich caramel drizzle.'],
            ['Masala Chai', 50.00, 'Beverages', 'Authentic Indian spiced tea brewed with ginger and cardamom.'],
            ['Classic Cappuccino', 130.00, 'Beverages', 'Rich espresso with equal parts steamed milk and velvety foam.'],
            ['Grilled Cheese Sandwich', 140.00, 'Food', 'Crispy artisan sourdough bread loaded with melted cheddar and mozzarella.'],
            ['Paneer Tikka Panini', 180.00, 'Food', 'Marinated tandoori paneer slices with mint mayo and grilled veggies.'],
            ['Farmhouse Margherita Pizza', 260.00, 'Food', 'Hand-tossed crust with fresh tomato sauce, basil, and gooey mozzarella.'],
            ['Peri-Peri Crispy Fries', 110.00, 'Food', 'Golden french fries tossed with spicy house peri-peri seasoning.'],
            ['Belgian Chocolate Brownie', 150.00, 'Desserts', 'Fudgy warm chocolate brownie topped with chocolate fudge sauce.'],
            ['New York Cheesecake', 210.00, 'Desserts', 'Velvety smooth cream cheese filling on a buttery graham cracker crust.']
        ];
        foreach ($items as $item) {
            $stmt->execute($item);
        }
    }
}

initDatabase();
