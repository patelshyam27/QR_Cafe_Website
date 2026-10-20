-- ============================================================================
-- PROJECT: QR CAFE MANAGEMENT SYSTEM (SQLite Version)
-- MAPPED TO DBMS SYLLABUS & LAB PRACTICALS (Practicals 1 to 11)
-- Zero Configuration - Runs directly on cafe.db
-- ============================================================================

PRAGMA foreign_keys = ON;

-- ============================================================================
-- PRACTICAL 2: DATA DEFINITION LANGUAGE (DDL)
-- Concepts: CREATE, ALTER, DROP
-- ============================================================================

DROP VIEW IF EXISTS v_admin_bookings_report;
DROP VIEW IF EXISTS v_available_tables;
DROP TRIGGER IF EXISTS trg_audit_orders;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS cafe_orders;
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS cafe_tables;

-- Table 1: CAFE_TABLES
CREATE TABLE cafe_tables (
    table_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    table_number TEXT NOT NULL UNIQUE,
    capacity     INTEGER DEFAULT 2 NOT NULL CHECK(capacity > 0),
    table_status TEXT DEFAULT 'AVAILABLE' NOT NULL CHECK(table_status IN ('AVAILABLE', 'RESERVED', 'DISABLED'))
);

-- Table 2: CUSTOMERS
CREATE TABLE customers (
    customer_id   INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_name TEXT NOT NULL,
    phone         TEXT NOT NULL UNIQUE,
    age           INTEGER DEFAULT 18 CHECK(age >= 12),
    email         TEXT
);

-- Table 3: BOOKINGS
CREATE TABLE bookings (
    booking_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    table_id       INTEGER NOT NULL,
    customer_id    INTEGER NOT NULL,
    booking_date   TEXT NOT NULL,
    start_time     TEXT NOT NULL,
    end_time       TEXT NOT NULL,
    booking_status TEXT DEFAULT 'CONFIRMED' NOT NULL CHECK(booking_status IN ('CONFIRMED', 'CANCELLED', 'COMPLETED')),
    FOREIGN KEY (table_id) REFERENCES cafe_tables(table_id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE
);

-- Table 4: MENU_ITEMS
CREATE TABLE menu_items (
    item_id      INTEGER PRIMARY KEY AUTOINCREMENT,
    item_name    TEXT NOT NULL UNIQUE,
    price        REAL NOT NULL CHECK(price >= 0),
    category     TEXT,
    availability TEXT DEFAULT 'Y' NOT NULL CHECK(availability IN ('Y', 'N'))
);

-- Table 5: CAFE_ORDERS
CREATE TABLE cafe_orders (
    order_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_id   INTEGER NOT NULL,
    order_date   TEXT DEFAULT CURRENT_TIMESTAMP NOT NULL,
    total_amount REAL DEFAULT 0 NOT NULL CHECK(total_amount >= 0),
    order_status TEXT DEFAULT 'PLACED' NOT NULL CHECK(order_status IN ('PLACED', 'PREPARING', 'SERVED', 'CANCELLED')),
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);

-- Table 6: ORDER_ITEMS
CREATE TABLE order_items (
    order_item_id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id      INTEGER NOT NULL,
    item_id       INTEGER NOT NULL,
    quantity      INTEGER DEFAULT 1 NOT NULL CHECK(quantity > 0),
    unit_price    REAL NOT NULL CHECK(unit_price >= 0),
    FOREIGN KEY (order_id) REFERENCES cafe_orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES menu_items(item_id),
    UNIQUE(order_id, item_id)
);

-- Table 7: AUDIT_LOG (Syllabus Practical 10)
CREATE TABLE audit_log (
    log_id      INTEGER PRIMARY KEY AUTOINCREMENT,
    table_name  TEXT NOT NULL,
    operation   TEXT NOT NULL,
    change_date TEXT DEFAULT CURRENT_TIMESTAMP NOT NULL,
    details     TEXT
);

-- DDL ALTER Demonstration
ALTER TABLE cafe_tables ADD COLUMN floor_number INTEGER DEFAULT 1;


-- ============================================================================
-- PRACTICAL 3: DATA MANIPULATION LANGUAGE (DML)
-- Concepts: INSERT, SELECT, UPDATE, DELETE, LIKE '%', Relational Operators
-- ============================================================================

-- 3(a) INSERT
INSERT INTO cafe_tables (table_number, capacity, table_status) VALUES ('T01', 2, 'AVAILABLE');
INSERT INTO cafe_tables (table_number, capacity, table_status) VALUES ('T02', 4, 'AVAILABLE');
INSERT INTO cafe_tables (table_number, capacity, table_status) VALUES ('T03', 4, 'AVAILABLE');
INSERT INTO cafe_tables (table_number, capacity, table_status) VALUES ('T04', 6, 'AVAILABLE');
INSERT INTO cafe_tables (table_number, capacity, table_status) VALUES ('T05', 2, 'DISABLED');

INSERT INTO customers (customer_name, phone, age, email) VALUES ('Rahul Patel', '9876543210', 22, 'rahul@example.com');
INSERT INTO customers (customer_name, phone, age, email) VALUES ('Priya Shah', '9876543211', 20, 'priya@example.com');
INSERT INTO customers (customer_name, phone, age, email) VALUES ('Amit Sharma', '9876543212', 25, 'amit@example.com');

INSERT INTO menu_items (item_name, price, category, availability) VALUES ('Cold Coffee', 120.00, 'Beverage', 'Y');
INSERT INTO menu_items (item_name, price, category, availability) VALUES ('Cappuccino', 140.00, 'Beverage', 'Y');
INSERT INTO menu_items (item_name, price, category, availability) VALUES ('Cheese Sandwich', 150.00, 'Food', 'Y');
INSERT INTO menu_items (item_name, price, category, availability) VALUES ('Margherita Pizza', 280.00, 'Food', 'Y');
INSERT INTO menu_items (item_name, price, category, availability) VALUES ('Chocolate Brownie', 160.00, 'Dessert', 'Y');

INSERT INTO bookings (table_id, customer_id, booking_date, start_time, end_time, booking_status)
VALUES (1, 1, date('now'), '14:00', '15:30', 'CONFIRMED');

INSERT INTO bookings (table_id, customer_id, booking_date, start_time, end_time, booking_status)
VALUES (2, 2, date('now'), '16:00', '17:00', 'CONFIRMED');

-- 3(b) SELECT with Relational Operators
SELECT * FROM menu_items WHERE price >= 140.0;
SELECT * FROM cafe_tables WHERE capacity > 2 AND table_status = 'AVAILABLE';

-- 3(c) SELECT with LIKE '%'
SELECT * FROM customers WHERE customer_name LIKE 'P%';
SELECT * FROM menu_items WHERE item_name LIKE '%Coffee%';

-- 3(d) UPDATE
UPDATE customers SET email = 'rahul.p@gmail.com' WHERE customer_name = 'Rahul Patel';

-- 3(e) DELETE
INSERT INTO menu_items (item_name, price, category, availability) VALUES ('Dummy Item', 99.0, 'Food', 'N');
DELETE FROM menu_items WHERE item_name = 'Dummy Item';


-- ============================================================================
-- PRACTICAL 5: RELATIONAL JOINS
-- ============================================================================

-- Inner Join
SELECT b.booking_id, c.customer_name, c.phone, t.table_number, b.booking_date, b.booking_status
FROM bookings b
JOIN customers c ON b.customer_id = c.customer_id
JOIN cafe_tables t ON b.table_id = t.table_id;

-- Left Join
SELECT t.table_number, t.capacity, b.booking_id, b.booking_date
FROM cafe_tables t
LEFT JOIN bookings b ON t.table_id = b.table_id;


-- ============================================================================
-- PRACTICAL 6: NESTED SUBQUERIES & AGGREGATES
-- ============================================================================

SELECT COUNT(*) AS total_items, AVG(price) AS avg_price, MAX(price) AS max_price FROM menu_items;

-- Subquery with IN
SELECT customer_name, phone FROM customers 
WHERE customer_id IN (SELECT DISTINCT customer_id FROM bookings);

-- Subquery with NOT IN
SELECT table_number FROM cafe_tables 
WHERE table_id NOT IN (SELECT DISTINCT table_id FROM bookings);

-- Correlated Subquery with EXISTS
SELECT c.customer_name FROM customers c
WHERE EXISTS (
    SELECT 1 FROM bookings b WHERE b.customer_id = c.customer_id AND b.booking_status = 'CONFIRMED'
);


-- ============================================================================
-- PRACTICAL 7: VIEWS
-- ============================================================================

CREATE VIEW v_available_tables AS
SELECT table_id, table_number, capacity FROM cafe_tables WHERE table_status = 'AVAILABLE';

CREATE VIEW v_admin_bookings_report AS
SELECT 
    b.booking_id, c.customer_name, c.phone, t.table_number, 
    b.booking_date, b.start_time, b.end_time, b.booking_status
FROM bookings b
JOIN customers c ON b.customer_id = c.customer_id
JOIN cafe_tables t ON b.table_id = t.table_id;


-- ============================================================================
-- PRACTICAL 10: TRIGGERS
-- ============================================================================

CREATE TRIGGER trg_audit_orders
AFTER INSERT ON cafe_orders
BEGIN
    INSERT INTO audit_log (table_name, operation, details)
    VALUES ('CAFE_ORDERS', 'INSERT', 'New order ID ' || NEW.order_id || ' placed for booking ' || NEW.booking_id);
END;

CREATE TRIGGER trg_check_customer_age
BEFORE INSERT ON customers
FOR EACH ROW
WHEN NEW.age < 18
BEGIN
    SELECT RAISE(ABORT, 'Customer must be at least 18 years old to make a cafe booking');
END;
