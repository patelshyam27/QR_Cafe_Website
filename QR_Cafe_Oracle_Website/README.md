# QR Cafe — Oracle DBMS Project

A small Flask website connected to Oracle Database. It includes:
- Customer booking form
- Table list and menu preview
- Admin booking report using SQL JOINs
- Booking cancellation (UPDATE)
- Basic overlapping-booking check
- Oracle bind variables to avoid SQL injection through values

## Requirements
- Windows
- Python 3.10+
- Oracle Database running locally
- Oracle SQL Developer is useful for creating tables, but it is not the database server itself.

## 1. Create/check database tables
In SQL Developer, connect as `cafe_user` to service `XEPDB1`.
If you have not created the six tables yet, open `schema.sql` in a SQL Worksheet and run it.
Do not run it again if those tables already exist, because the CREATE TABLE statements will fail for existing tables.
If you already created the tables using the earlier SQL from the chat, those tables are compatible with this website. Add the menu/order tables only if you need them; the website currently requires `CAFE_TABLES`, `CUSTOMERS`, `BOOKINGS`, and `MENU_ITEMS`.

Check your table data:
```sql
SELECT * FROM cafe_tables;
SELECT * FROM customers;
SELECT * FROM bookings;
SELECT * FROM menu_items;
```

## 2. Install Python
If Python is not installed, install Python 3.10 or newer from https://www.python.org/downloads/ and select "Add Python to PATH".

## 3. Configure connection
Open Command Prompt in this project folder and run:
```bat
py -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
copy .env.example .env
notepad .env
```
In `.env`, replace `DB_PASSWORD` with the password you set for `cafe_user`. Keep host `localhost`, port `1521`, and service `XEPDB1` only if those match your working SQL Developer connection. Do not share the `.env` file or password.

## 4. Start the website
```bat
python app.py
```
Open this address in your browser:
http://127.0.0.1:5000

Pages:
- `/` — tables and menu
- `/book` — booking form
- `/admin/bookings` — booking report and cancellation

## Troubleshooting
- `ORA-12541`: Oracle listener/service may not be running, or host/port are wrong.
- `ORA-01017`: username/password is incorrect.
- `ORA-12514` / `ORA-12505`: service name or SID does not match the database configuration.
- `ORA-00942`: the tables are missing or you connected as the wrong user.
- `ModuleNotFoundError`: activate `.venv` and run `pip install -r requirements.txt`.

## DBMS syllabus mapping
- DDL: schema.sql CREATE TABLE
- DML: booking INSERT, customer UPDATE, cancellation UPDATE, SELECT reports
- Constraints: PK, FK, UNIQUE, NOT NULL, CHECK, DEFAULT
- Joins: admin bookings report
- TCL: COMMIT in booking/cancellation
- Views, triggers, nested queries, DCL, normalization and indexing can be added as separate practical exercises.
