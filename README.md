# Student Hostel Management System

A complete, production-quality web application for managing university hostel operations — students, rooms, room allocations, and payments — built for **ICT 2308 Database Systems (Mini Project)**.

## Tech Stack

| Layer     | Technology |
|-----------|------------|
| Frontend  | HTML5, CSS3, Bootstrap 5, JavaScript |
| Backend   | PHP 8+ (PDO, prepared statements) |
| Database  | MySQL (3NF normalized) |
| Server    | XAMPP (Apache + MySQL) |

## Features

- **Secure admin login** with hashed passwords (bcrypt) and CSRF-protected forms
- **Dashboard** with live KPIs: total students, available rooms, occupied rooms, total payments collected
- **Students module** — Create, Read, Update, Delete, Search
- **Rooms module** — Create, Read, Update, Delete, Search, automatic occupancy/status tracking
- **Allocations module** — Assign student to room, Transfer between rooms, Checkout, full allocation history, all wrapped in database transactions to keep room occupancy consistent
- **Payments module** — Create, Read, Update, Delete, Search, payment history
- **Reports** — Student Report, Room Report, Allocation Report, Payment Report (printable)
- All queries use **prepared statements** to prevent SQL injection; all output is escaped to prevent XSS

## Project Structure

```
hostel_management/
├── config/
│   └── database.php          # PDO database connection
├── includes/
│   ├── auth_check.php        # Session/auth guard (included on every protected page)
│   ├── functions.php         # Shared helper functions (sanitization, validation, formatting)
│   ├── header.php             # Shared HTML head + top navbar
│   ├── sidebar.php            # Shared sidebar navigation
│   ├── footer.php             # Shared closing markup + scripts
│   ├── student_form_fields.php
│   ├── room_form_fields.php
│   └── payment_form_fields.php
├── assets/
│   ├── css/style.css          # Application styling
│   ├── js/script.js           # Sidebar toggle, modal helpers, live search, delete confirmation
│   └── img/                   # (reserved for images)
├── database/
│   └── hostel_management.sql  # Full schema + sample data
├── docs/
│   ├── ER_DIAGRAM.md
│   ├── RELATIONAL_SCHEMA.md
│   ├── DATA_DICTIONARY.md
│   ├── NORMALIZATION.md
│   └── CRUD_MAPPING.md
├── login.php
├── logout.php
├── dashboard.php
├── students.php
├── rooms.php
├── allocations.php
├── payments.php
├── reports.php
├── README.md
└── INSTALLATION.md
```

## Default Login

| Username | Password |
|----------|----------|
| `admin`  | `admin123` |

## Documentation

See the `docs/` folder for the ER diagram (Mermaid), relational schema, data dictionary, 1NF/2NF/3NF normalization notes, and the CRUD mapping table.

See `INSTALLATION.md` for step-by-step XAMPP setup instructions.

## Security Notes

- All database access goes through PDO prepared statements — no string-concatenated SQL anywhere in the codebase.
- All user-supplied output is passed through `htmlspecialchars()` before being echoed.
- Passwords are stored using `password_hash()` (bcrypt) and verified with `password_verify()`.
- All state-changing forms include a CSRF token validated with `hash_equals()`.
- Server-side validation backs up all client-side (HTML5) validation — never trust the browser alone.
