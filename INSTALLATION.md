# Installation Guide (XAMPP)

## Prerequisites

- [XAMPP](https://www.apachefriends.org/) installed (includes Apache, MySQL/MariaDB, PHP 8+)

## Steps

1. **Copy the project folder**

   Copy the entire `hostel_management` folder into your XAMPP `htdocs` directory:

   - Windows: `C:\xampp\htdocs\hostel_management`
   - macOS: `/Applications/XAMPP/htdocs/hostel_management`
   - Linux: `/opt/lampp/htdocs/hostel_management`

2. **Start XAMPP services**

   Open the XAMPP Control Panel and start **Apache** and **MySQL**.

3. **Create the database**

   Open **phpMyAdmin** at `http://localhost/phpmyadmin`, then:
   - Click **Import**
   - Choose the file `database/hostel_management.sql` from the project folder
   - Click **Go**

   This creates the `hostel_management` database, all four tables (plus the `users` table for login), foreign keys, indexes, and sample data.

   Alternatively, from the command line:
   ```bash
   mysql -u root -p < database/hostel_management.sql
   ```

4. **Check database credentials**

   Open `config/database.php` and confirm the credentials match your MySQL setup (defaults work for a standard XAMPP install: user `root`, no password):

   ```php
   $DB_HOST = 'localhost';
   $DB_NAME = 'hostel_management';
   $DB_USER = 'root';
   $DB_PASS = '';
   ```

5. **Run the application**

   Open your browser and go to:
   ```
   http://localhost/hostel_management/login.php
   ```

6. **Log in**

   | Username | Password |
   |----------|----------|
   | `admin`  | `admin123` |

## Troubleshooting

- **"Database connection failed"** — Make sure MySQL is running in XAMPP and that you imported `hostel_management.sql`.
- **Blank page / 500 error** — Check that your XAMPP PHP version is 8.0 or higher (`php -v` in the XAMPP shell), and check Apache's error log.
- **Login fails with correct credentials** — Re-import the SQL file; the default admin password hash must match `admin123` exactly as shipped in `hostel_management.sql`.
- **Port conflicts** — If Apache won't start, another program (e.g. Skype, IIS) may be using port 80. Change Apache's port in XAMPP's `httpd.conf`, or stop the conflicting service.
