import os
from datetime import date
from flask import Flask, render_template, request, redirect, url_for, flash
import oracledb

app = Flask(__name__)
app.secret_key = os.getenv("FLASK_SECRET_KEY", "change-this-for-local-college-demo")

def get_connection():
    """Connect to Oracle using environment variables; no password is stored in source code."""
    user = os.getenv("DB_USER", "cafe_user")
    password = os.getenv("DB_PASSWORD", "")
    host = os.getenv("DB_HOST", "localhost")
    port = int(os.getenv("DB_PORT", "1521"))
    service = os.getenv("DB_SERVICE", "XEPDB1")
    if not password:
        raise RuntimeError("DB_PASSWORD is empty. Copy .env.example to .env and enter your Oracle password.")
    dsn = f"{host}:{port}/{service}"
    return oracledb.connect(user=user, password=password, dsn=dsn)

def fetch_all(sql, params=None):
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute(sql, params or {})
            columns = [col[0].lower() for col in cur.description]
            return [dict(zip(columns, row)) for row in cur.fetchall()]

@app.route("/")
def index():
    try:
        tables = fetch_all("""
            SELECT table_id, table_number, capacity, table_status
            FROM cafe_tables
            ORDER BY table_number
        """)
        menu = fetch_all("""
            SELECT item_id, item_name, price, category
            FROM menu_items
            WHERE availability = 'Y'
            ORDER BY category, item_name
        """)
        return render_template("index.html", tables=tables, menu=menu)
    except Exception as exc:
        app.logger.exception("Database read failed")
        return render_template("error.html", message=str(exc)), 500

@app.route("/book", methods=["GET", "POST"])
def book():
    if request.method == "GET":
        table_id = request.args.get("table_id", type=int)
        try:
            tables = fetch_all("""
                SELECT table_id, table_number, capacity
                FROM cafe_tables
                WHERE table_status = 'AVAILABLE'
                ORDER BY table_number
            """)
            return render_template("book.html", tables=tables, selected_table=table_id,
                                   today=date.today().isoformat())
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
        # Basic format/time validation.
        if len(phone) > 15 or not phone.isdigit():
            flash("Enter a phone number containing digits only (maximum 15 digits).", "error")
            return redirect(url_for("book"))
        if start_time >= end_time:
            flash("End time must be later than start time. Use 24-hour time.", "error")
            return redirect(url_for("book"))

        with get_connection() as conn:
            with conn.cursor() as cur:
                # Confirm table exists and is available for booking.
                cur.execute("""
                    SELECT table_id FROM cafe_tables
                    WHERE table_id = :table_id AND table_status = 'AVAILABLE'
                """, {"table_id": table_id})
                if cur.fetchone() is None:
                    flash("That table is not available for booking.", "error")
                    return redirect(url_for("book"))

                # Find an overlapping confirmed booking for the same date and table.
                # Times are stored as HH:MI text, so this works for zero-padded 24-hour values.
                cur.execute("""
                    SELECT COUNT(*)
                    FROM bookings
                    WHERE table_id = :table_id
                      AND booking_date = TO_DATE(:booking_date, 'YYYY-MM-DD')
                      AND booking_status = 'CONFIRMED'
                      AND start_time < :end_time
                      AND end_time > :start_time
                """, {
                    "table_id": table_id,
                    "booking_date": booking_date,
                    "start_time": start_time,
                    "end_time": end_time
                })
                if cur.fetchone()[0] > 0:
                    flash("This table already has a booking during that time. Please choose another time or table.", "error")
                    return redirect(url_for("book"))

                # Reuse customer record when phone number already exists.
                cur.execute("SELECT customer_id FROM customers WHERE phone = :phone", {"phone": phone})
                row = cur.fetchone()
                if row:
                    customer_id = row[0]
                    cur.execute("""
                        UPDATE customers SET customer_name = :name
                        WHERE customer_id = :customer_id
                    """, {"name": name, "customer_id": customer_id})
                else:
                    cur.execute("""
                        INSERT INTO customers (customer_name, phone)
                        VALUES (:name, :phone)
                        RETURNING customer_id INTO :new_id
                    """, {
                        "name": name,
                        "phone": phone,
                        "new_id": cur.var(oracledb.NUMBER)
                    })
                    # For RETURNING INTO, python-oracledb output variable is read from the bind variable.
                    # Re-query by phone for compatibility and clarity.
                    cur.execute("SELECT customer_id FROM customers WHERE phone = :phone", {"phone": phone})
                    customer_id = cur.fetchone()[0]

                cur.execute("""
                    INSERT INTO bookings
                        (table_id, customer_id, booking_date, start_time, end_time, booking_status)
                    VALUES
                        (:table_id, :customer_id, TO_DATE(:booking_date, 'YYYY-MM-DD'),
                         :start_time, :end_time, 'CONFIRMED')
                """, {
                    "table_id": table_id,
                    "customer_id": customer_id,
                    "booking_date": booking_date,
                    "start_time": start_time,
                    "end_time": end_time
                })
            conn.commit()

        flash("Booking created successfully!", "success")
        return redirect(url_for("admin_bookings"))
    except Exception as exc:
        app.logger.exception("Booking creation failed")
        flash("Booking could not be saved. Check that your Oracle tables match schema.sql. Details: " + str(exc), "error")
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
        with get_connection() as conn:
            with conn.cursor() as cur:
                cur.execute("""
                    UPDATE bookings
                    SET booking_status = 'CANCELLED'
                    WHERE booking_id = :booking_id
                      AND booking_status = 'CONFIRMED'
                """, {"booking_id": booking_id})
                changed = cur.rowcount
            conn.commit()
        flash("Booking cancelled." if changed else "No confirmed booking was found.", "success" if changed else "error")
    except Exception as exc:
        app.logger.exception("Cancellation failed")
        flash("Could not cancel booking: " + str(exc), "error")
    return redirect(url_for("admin_bookings"))

if __name__ == "__main__":
    # Local development only. Do not expose Flask's debug server to the public internet.
    app.run(host="127.0.0.1", port=5000, debug=True)
