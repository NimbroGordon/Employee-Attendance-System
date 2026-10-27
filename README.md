# AttendPro – Employee Attendance Management System

PHP + MySQL web application (EEX3218 Mini Project, Scenario 01).

## Project structure

```
attendance-system/
├── login.php          Screen 1 – Login
├── index.php          Screen 2 – Main dashboard
├── mark.php           Screen 3 – Mark daily attendance
├── employees.php      Screen 4 – Add / edit / delete employees (CRUD)
├── history.php        Screen 5 – Individual attendance history
├── reports.php        Screen 6 – Department reports
├── logout.php
├── includes/
│   ├── bootstrap.php  Loads all shared files
│   ├── db.php         Database connection (PDO)
│   ├── auth.php       Session, login guard, CSRF, helpers
│   ├── functions.php  Attendance algorithm and business rules
│   └── layout.php     Shared header / navigation / footer
├── assets/style.css   Theme and styling
└── sql/extras.sql     Users table + unique key for attendance
```

## Setup (XAMPP or WAMP)

1. Copy the `attendance-system` folder into `htdocs` (XAMPP) or `www` (WAMP).
2. Start **Apache** and **MySQL** from the control panel.
3. Open phpMyAdmin (`http://localhost/phpmyadmin`).
4. Import your original dump (`attendance_db`) first.
5. Then run `sql/extras.sql` in the `attendance_db` database.
6. Create the admin password hash. In a terminal run:
   `php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"`
   Copy the output and replace `PASTE_YOUR_HASH_HERE` in `extras.sql`
   (or run this UPDATE in phpMyAdmin:
   `UPDATE users SET password_hash='PASTE_HASH' WHERE username='admin';`).
7. If your MySQL has a password, edit `includes/db.php` (`DB_PASS`).
8. Open `http://localhost/attendance-system/` and log in with
   **admin / admin123**.

## Algorithm used for reports

- Attendance rate = (Present + Late) / total records × 100
- Punctuality rate = Present / (Present + Late) × 100
- Absenteeism rate = Absent / total records × 100
- Flag "High absenteeism" when absenteeism ≥ 20 %
- Flag "Punctuality issue" when punctuality < 70 %

Thresholds are constants at the top of `includes/functions.php`.

## Notes

- All database queries use prepared statements (PDO) to prevent SQL injection.
- All forms carry a CSRF token; all output is escaped with `e()` to prevent XSS.
- Passwords are stored with `password_hash()` and checked with `password_verify()`.
