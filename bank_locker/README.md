# Bank Locker Management System
**BCA Project - Rinto M Reji | Koshys Institute of Management Studies**
Guided by: Ms. Greeshma S S

---

## Setup Instructions

### Requirements
- XAMPP (Apache + MySQL + PHP 7.4+)
- Web Browser (Chrome / Edge)

### Steps to Run

1. **Copy project folder**
   - Copy the `bank_locker` folder to `C:/xampp/htdocs/`
   - So path becomes: `C:/xampp/htdocs/bank_locker/`

2. **Start XAMPP**
   - Open XAMPP Control Panel
   - Start **Apache** and **MySQL**

3. **Automated Setup (Recommended)**
   - Open browser and navigate to: http://localhost/bank_locker/setup.php
   - This script will automatically verify/create the database, run migrations, set up all columns, and seed default data.
   - Once completed, delete the `setup.php` file for security.

4. **Manual Setup (Alternative)**
   - Open browser: http://localhost/phpmyadmin
   - Create a database named `bank_locker_db`
   - Import the `database.sql` file located in the root of the project
   - Open `bank_locker/includes/config.php` and ensure your database credentials match:
     ```php
     define('DB_USER', 'root');
     define('DB_PASS', '');   // Leave blank if no password
     ```

5. **Open the Project**
   - Go to: http://localhost/bank_locker/


---

## Login Credentials

### Admin Login
- URL: http://localhost/bank_locker/admin/login.php
- Username: `admin`
- Password: `password`

### Customer Login
- URL: http://localhost/bank_locker/customer/login.php
- Register a new customer or add via Admin panel

---

## Project Structure

```
bank_locker/
├── index.php                 ← Landing page
├── database.sql              ← Database setup file
├── .htaccess
├── css/
│   └── style.css             ← Main stylesheet
├── includes/
│   ├── config.php            ← DB connection config
│   ├── functions.php         ← Helper functions
│   ├── header_admin.php      ← Admin layout header
│   ├── footer_admin.php      ← Admin layout footer
│   ├── header_customer.php   ← Customer layout header
│   └── footer_customer.php   ← Customer layout footer
├── admin/
│   ├── login.php             ← Admin login
│   ├── logout.php
│   ├── dashboard.php         ← Admin dashboard (stats)
│   ├── lockers.php           ← Add/manage lockers
│   ├── allocations.php       ← View/manage allocations
│   ├── allocate_locker.php   ← Allocate locker to customer
│   ├── customers.php         ← View/manage customers
│   ├── add_customer.php      ← Add new customer
│   └── access_log.php        ← Log and view locker access
└── customer/
    ├── login.php             ← Customer login
    ├── logout.php
    ├── register.php          ← Self-registration
    ├── dashboard.php         ← Customer dashboard
    ├── my_locker.php         ← View locker details
    ├── access_log.php        ← View access history
    └── profile.php           ← Edit profile/password
```

---

## Features

### Admin Panel
- Dashboard with live statistics
- Add and manage lockers (Small/Medium/Large)
- Register and manage customers
- Allocate lockers to customers
- Surrender/deallocate lockers
- Log locker access visits
- View full access history

### Customer Portal
- Self-registration and login
- View allocated locker details
- Check access history
- Update profile and password

---

## Technologies Used
- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Server**: Apache (XAMPP)
- **Tool**: Visual Studio Code, XAMPP

---

*Submitted for BCA Degree - Bengaluru North University*
