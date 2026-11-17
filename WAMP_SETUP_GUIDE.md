# ReUseHub – WAMP Server Setup & Installation Guide

**Tagline:** *"Use More. Own Less."*  
**Platform:** ReUseHub Peer-to-Peer Rental Marketplace  
**Target Environment:** WAMP Server (Windows, Apache, MySQL / MariaDB, PHP 7.4 - 8.4+)

---

## 1. Prerequisites

Before installing, ensure your WAMP Server has the following active:
- **WAMP Server** installed (e.g., `C:\wamp64` or `C:\wamp`).
- **Apache** and **MySQL** services running (Green WAMP tray icon).
- **PHP** 7.4, 8.0, 8.1, 8.2, 8.3, or 8.4 enabled.
- **PDO MySQL Extension** (enabled by default in WAMP).

---

## 2. Database Installation (`reusehub_db`)

You can set up the database using either **phpMyAdmin** (browser) or the **CLI Setup Script**.

### Option A: Via phpMyAdmin (Recommended for GUI Users)
1. Open your browser and navigate to:
   ```
   http://localhost/phpmyadmin/
   ```
2. Log in with your MySQL credentials (Default: Username `root`, Password *leave blank*).
3. Click on the **Import** tab at the top.
4. Click **Choose File** and select:
   ```
   D:\ReUseHub\database\reusehub_db.sql
   ```
5. Click **Go** (or **Import**) at the bottom.
6. The database `reusehub_db` and all 10 tables will be automatically created and populated with realistic seed data.

### Option B: Via Command Line (1-Click Automated Setup)
Run the automated migration script directly from your terminal:
```powershell
& "C:\wamp64\bin\php\php8.2.26\php.exe" d:\ReUseHub\config\setup_db.php
```

---

## 3. Running ReUseHub in WAMP

Choose whichever method best matches your development setup:

### Method 1: Instant Development Server (Zero Setup)
Open PowerShell or Command Prompt in the `d:\ReUseHub` directory and run:
```powershell
cd D:\ReUseHub
& "C:\wamp64\bin\php\php8.2.26\php.exe" -S localhost:8000
```
Then open your browser and navigate to:
```
http://localhost:8000
```

### Method 2: Access via WAMP Apache Webroot
Copy the project folder to your WAMP `www` folder:
```powershell
Copy-Item -Recurse -Path "D:\ReUseHub" -Destination "C:\wamp64\www\ReUseHub"
```
Then open your browser and navigate to:
```
http://localhost/ReUseHub/
```

### Method 3: Apache Virtual Host (Production-Style Local Domain)
1. Right-click the WAMP tray icon &rarr; **Your VirtualHosts** &rarr; **VirtualHost Management**.
2. Set **Name of the Virtual Host**: `reusehub.local`
3. Set **Absolute path to Virtual Host folder**: `D:/ReUseHub`
4. Click **Start the creation of the VirtualHost** and restart Apache.
5. Open your browser to: `http://reusehub.local`

---

## 4. Pre-Configured Test Accounts (Seed Logins)

All seed accounts use the default password: **`Password123`**

| Role | Name | Email Address | Password | Initial City |
| :--- | :--- | :--- | :--- | :--- |
| **Platform Admin** | ReUseHub Admin | `admin@reusehub.com` | `Password123` | Kochi, Kerala |
| **Product Owner** | Rahul Menon | `rahul@gmail.com` | `Password123` | Kakkanad, Kochi |
| **Product Owner** | Ananya Nair | `ananya@gmail.com` | `Password123` | Trivandrum |
| **Product Owner** | Favas Rahman | `favas@gmail.com` | `Password123` | Kozhikode |
| **Renter** | Sneha Varma | `sneha@gmail.com` | `Password123` | Thrissur |

> **Pro Tip:** On the Login page (`login.php`), use the **1-Click Quick Fill** buttons at the bottom to instantly populate credentials for Admin, Owner, or Renter.

---

## 5. Verification & Automated Test Suite

To verify system health, database constraints, calculations, and conflict prevention, run the automated test suite:
```powershell
& "C:\wamp64\bin\php\php8.2.26\php.exe" d:\ReUseHub\tests\test_suite.php
```

All 17 integration tests will execute and confirm:
- Database connectivity & schema referential integrity.
- BCRYPT password security & authentication.
- Real-time rental duration, daily rates, deposit, and 3% platform fee calculations.
- Overlapping booking prevention.
- Notification dispatching.
- Wishlist operations and XSS sanitization.

