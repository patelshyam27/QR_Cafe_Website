import os
import sqlite3
from datetime import date
from flask import Flask, render_template, request, redirect, url_for, flash

app = Flask(__name__)
app.secret_key = os.getenv("FLASK_SECRET_KEY", "cafe-secret-key-12345")

# Path to local SQLite database file
DB_PATH = os.path.join(os.path.dirname(__file__), "cafe.db")

def get_db_connection():
    """Returns a connection to the local SQLite database with row factory for dictionary access."""
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    conn.execute("PRAGMA foreign_keys = ON")  # Enforce foreign key constraints
    return conn

def init_db():
    """Automatically initializes the SQLite database with syllabus-compliant tables and sample data."""
    with get_db_connection() as conn:
        cursor = conn.cursor()
        
        # 1. CAFE_TABLES
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS cafe_tables (
                table_id INTEGER PRIMARY KEY AUTOINCREMENT,
                table_number TEXT NOT NULL UNIQUE,
                capacity INTEGER DEFAULT 2 NOT NULL CHECK(capacity > 0),
                table_status TEXT DEFAULT 'AVAILABLE' NOT NULL CHECK(table_status IN ('AVAILABLE', 'RESERVED', 'DISABLED'))
            )
        """)
        
        # 2. CUSTOMERS
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS customers (
                customer_id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_name TEXT NOT NULL,
                phone TEXT NOT NULL UNIQUE,
                age INTEGER DEFAULT 18 CHECK(age >= 12),
                email TEXT
            )
        """)
        
        # 3. BOOKINGS
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS bookings (
                booking_id INTEGER PRIMARY KEY AUTOINCREMENT,
                table_id INTEGER NOT NULL,
                customer_id INTEGER NOT NULL,
                booking_date TEXT NOT NULL,
                start_time TEXT NOT NULL,
                end_time TEXT NOT NULL,
                booking_status TEXT DEFAULT 'CONFIRMED' NOT NULL CHECK(booking_status IN ('CONFIRMED', 'CANCELLED', 'COMPLETED')),
                FOREIGN KEY (table_id) REFERENCES cafe_tables(table_id) ON DELETE CASCADE,
                FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE
            )
        """)
        
        # 4. MENU_ITEMS
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS menu_items (
                item_id INTEGER PRIMARY KEY AUTOINCREMENT,
                item_name TEXT NOT NULL UNIQUE,
                price REAL NOT NULL CHECK(price >= 0),
                category TEXT,
                availability TEXT DEFAULT 'Y' NOT NULL CHECK(availability IN ('Y', 'N'))
            )
        """)
        
        # 5. CAFE_ORDERS
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS cafe_orders (
                order_id INTEGER PRIMARY KEY AUTOINCREMENT,
                booking_id INTEGER NOT NULL,
                order_date TEXT DEFAULT CURRENT_TIMESTAMP NOT NULL,
                total_amount REAL DEFAULT 0 NOT NULL CHECK(total_amount >= 0),
                order_status TEXT DEFAULT 'PLACED' NOT NULL CHECK(order_status IN ('PLACED', 'PREPARING', 'SERVED', 'CANCELLED')),
                FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
            )
        """)
        
        # 6. ORDER_ITEMS
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS order_items (
                order_item_id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                item_id INTEGER NOT NULL,
                quantity INTEGER DEFAULT 1 NOT NULL CHECK(quantity > 0),
                unit_price REAL NOT NULL CHECK(unit_price >= 0),
                FOREIGN KEY (order_id) REFERENCES cafe_orders(order_id) ON DELETE CASCADE,
                FOREIGN KEY (item_id) REFERENCES menu_items(item_id),
                UNIQUE(order_id, item_id)
            )
        """)
        
        # 7. AUDIT_LOG (Syllabus Practical 10 Trigger Target)
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS audit_log (
                log_id INTEGER PRIMARY KEY AUTOINCREMENT,
                table_name TEXT NOT NULL,
                operation TEXT NOT NULL,
                change_date TEXT DEFAULT CURRENT_TIMESTAMP NOT NULL,
                details TEXT
            )
        """)
        
        # Triggers: Audit Trigger for Cafe Orders
        cursor.execute("""
            CREATE TRIGGER IF NOT EXISTS trg_audit_orders
            AFTER INSERT ON cafe_orders
            BEGIN
                INSERT INTO audit_log (table_name, operation, details)
                VALUES ('CAFE_ORDERS', 'INSERT', 'New order ID ' || NEW.order_id || ' placed for booking ' || NEW.booking_id);
            END;
        """)

        # Views: Admin Bookings Report View (Syllabus Practical 7)
        cursor.execute("""
            CREATE VIEW IF NOT EXISTS v_admin_bookings_report AS
            SELECT 
                b.booking_id,
                c.customer_name,
                c.phone,
                t.table_number,
                t.capacity,
                b.booking_date,
                b.start_time,
                b.end_time,
                b.booking_status
            FROM bookings b
            JOIN customers c ON b.customer_id = c.customer_id
            JOIN cafe_tables t ON b.table_id = t.table_id;
        """)

        # Insert starter tables if empty
        cursor.execute("SELECT COUNT(*) FROM cafe_tables")
        if cursor.fetchone()[0] == 0:
            cursor.executemany("""
                INSERT INTO cafe_tables (table_number, capacity, table_status)
                VALUES (?, ?, ?)
            """, [
                ('T01', 2, 'AVAILABLE'),
                ('T02', 4, 'AVAILABLE'),
                ('T03', 4, 'AVAILABLE'),
                ('T04', 6, 'AVAILABLE'),
                ('T05', 2, 'DISABLED')
            ])

        # Insert starter menu items if empty
        cursor.execute("SELECT COUNT(*) FROM menu_items")
        if cursor.fetchone()[0] == 0:
            cursor.executemany("""
                INSERT INTO menu_items (item_name, price, category, availability)
                VALUES (?, ?, ?, ?)
            """, [
                ('Cold Coffee', 120.00, 'Beverage', 'Y'),
                ('Cappuccino', 140.00, 'Beverage', 'Y'),
                ('Cheese Sandwich', 150.00, 'Food', 'Y'),
                ('Margherita Pizza', 280.00, 'Food', 'Y'),
                ('Chocolate Brownie', 160.00, 'Dessert', 'Y'),
                ('Masala Chai', 50.00, 'Beverage', 'Y')
            ])

        # Insert sample customer and booking if empty
        cursor.execute("SELECT COUNT(*) FROM customers")
        if cursor.fetchone()[0] == 0:
            cursor.execute("INSERT INTO customers (customer_name, phone, age, email) VALUES (?, ?, ?, ?)",
                           ('Rahul Patel', '9876543210', 22, 'rahul@example.com'))
            cust_id = cursor.lastrowid
            cursor.execute("""
                INSERT INTO bookings (table_id, customer_id, booking_date, start_time, end_time, booking_status)
                VALUES (1, ?, date('now'), '14:00', '15:30', 'CONFIRMED')
            """, (cust_id,))

        conn.commit()

# Initialize DB on import/startup
init_db()

def fetch_all(sql, params=()):
    with get_db_connection() as conn:
        cursor = conn.cursor()
        cursor.execute(sql, params)
        rows = cursor.fetchall()
        return [dict(row) for row in rows]

@app.route("/")
def index():
    try:
        tables = fetch_all("SELECT table_id, table_number, capacity, table_status FROM cafe_tables ORDER BY table_number")
        menu = fetch_all("SELECT item_id, item_name, price, category FROM menu_items WHERE availability = 'Y' ORDER BY category, item_name")
        return render_template("index.html", tables=tables, menu=menu)
    except Exception as exc:
        app.logger.exception("Database read failed")
        return render_template("error.html", message=str(exc)), 500

@app.route("/book", methods=["GET", "POST"])
def book():
    if request.method == "GET":
        table_id = request.args.get("table_id", type=int)
        try:
            tables = fetch_all("SELECT table_id, table_number, capacity FROM cafe_tables WHERE table_status = 'AVAILABLE' ORDER BY table_number")
            return render_template("book.html", tables=tables, selected_table=table_id, today=date.today().isoformat())
        except Exception as exc:
            app.logger.exception("Database read failed")
            return render_template("error.html", message=str(exc)), 500

    name = request.form.get("customer_name", "").strip()
    phone = request.form.get("phone", "").strip()
    table_id = request.form.get("table_id", type=int)
    booking_date = request.form.get("booking_date", "").strip()
    start_time = request.form.get("start_time", "").strip()
    end_time = request.form.get("end_time", "").strip()

    if not all([name, phone, table_id, booking_date, start_time, end_time]):
        flash("Please fill in every field.", "error")
        return redirect(url_for("book"))

    try:
        if len(phone) > 15 or not phone.isdigit():
            flash("Enter a phone number containing digits only (maximum 15 digits).", "error")
            return redirect(url_for("book"))
        if start_time >= end_time:
            flash("End time must be later than start time. Use 24-hour time.", "error")
            return redirect(url_for("book"))

        with get_db_connection() as conn:
            cur = conn.cursor()

            # Confirm table is available
            cur.execute("SELECT table_id FROM cafe_tables WHERE table_id = ? AND table_status = 'AVAILABLE'", (table_id,))
            if cur.fetchone() is None:
                flash("That table is not available for booking.", "error")
                return redirect(url_for("book"))

            # Overlap check
            cur.execute("""
                SELECT COUNT(*) FROM bookings
                WHERE table_id = ?
                  AND booking_date = ?
                  AND booking_status = 'CONFIRMED'
                  AND start_time < ?
                  AND end_time > ?
            """, (table_id, booking_date, end_time, start_time))
            if cur.fetchone()[0] > 0:
                flash("This table already has a booking during that time. Please choose another time or table.", "error")
                return redirect(url_for("book"))

            # Insert/update customer
            cur.execute("SELECT customer_id FROM customers WHERE phone = ?", (phone,))
            row = cur.fetchone()
            if row:
                customer_id = row[0]
                cur.execute("UPDATE customers SET customer_name = ? WHERE customer_id = ?", (name, customer_id))
            else:
                cur.execute("INSERT INTO customers (customer_name, phone) VALUES (?, ?)", (name, phone))
                customer_id = cur.lastrowid

            # Create booking
            cur.execute("""
                INSERT INTO bookings (table_id, customer_id, booking_date, start_time, end_time, booking_status)
                VALUES (?, ?, ?, ?, ?, 'CONFIRMED')
            """, (table_id, customer_id, booking_date, start_time, end_time))
            conn.commit()

        flash("Booking created successfully!", "success")
        return redirect(url_for("admin_bookings"))
    except Exception as exc:
        app.logger.exception("Booking creation failed")
        flash(f"Booking could not be saved: {exc}", "error")
        return redirect(url_for("book"))

@app.route("/admin/bookings")
def admin_bookings():
    try:
        bookings = fetch_all("""
            SELECT b.booking_id, c.customer_name, c.phone, t.table_number,
                   b.booking_date, b.start_time, b.end_time, b.booking_status
            FROM bookings b
            JOIN customers c ON c.customer_id = b.customer_id
            JOIN cafe_tables t ON t.table_id = b.table_id
            ORDER BY b.booking_date DESC, b.start_time
        """)
        return render_template("bookings.html", bookings=bookings)
    except Exception as exc:
        app.logger.exception("Booking report failed")
        return render_template("error.html", message=str(exc)), 500

@app.post("/admin/bookings/<int:booking_id>/cancel")
def cancel_booking(booking_id):
    try:
        with get_db_connection() as conn:
            cur = conn.cursor()
            cur.execute("""
                UPDATE bookings
                SET booking_status = 'CANCELLED'
                WHERE booking_id = ? AND booking_status = 'CONFIRMED'
            """, (booking_id,))
            changed = cur.rowcount
            conn.commit()
        flash("Booking cancelled." if changed else "No confirmed booking was found.", "success" if changed else "error")
    except Exception as exc:
        app.logger.exception("Cancellation failed")
        flash(f"Could not cancel booking: {exc}", "error")
    return redirect(url_for("admin_bookings"))

@app.route("/admin/db-viewer")
def db_viewer():
    """Built-in visual SQLite database manager: see tables and records directly in browser!"""
    selected_table = request.args.get("table", "bookings")
    with get_db_connection() as conn:
        cur = conn.cursor()
        
        # Get list of all tables
        cur.execute("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")
        tables = [row[0] for row in cur.fetchall()]
        
        if selected_table not in tables and tables:
            selected_table = tables[0]
            
        columns = []
        rows = []
        if selected_table:
            cur.execute(f"SELECT * FROM {selected_table} LIMIT 100")
            if cur.description:
                columns = [col[0] for col in cur.description]
                rows = [dict(zip(columns, r)) for r in cur.fetchall()]
                
    return render_template("db_viewer.html", tables=tables, selected_table=selected_table, columns=columns, rows=rows)

if __name__ == "__main__":
    print(f"Starting QR Cafe website with SQLite database at: {DB_PATH}")
    print("Visit http://127.0.0.1:5000 in your browser.")
    print("Visit http://127.0.0.1:5000/admin/db-viewer to view and inspect all database tables!")
    app.run(host="127.0.0.1", port=5000, debug=True)
