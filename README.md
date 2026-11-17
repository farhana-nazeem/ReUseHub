# ReUseHub – Rent Instead of Buy

> **Tagline:** *"Use More. Own Less."*  
> **Mission:** A sustainable peer-to-peer rental marketplace connecting product owners with local renters to eliminate unnecessary purchases, save substantial money, encourage circular reuse, and generate steady passive income.

---

## 🌟 Platform Highlights

ReUseHub is a complete, production-ready, peer-to-peer rental marketplace tailored initially for **Kerala, India** (Kochi, Trivandrum, Kozhikode, Thrissur, Kottayam, and beyond) and architected for nationwide expansion.

### Core Architecture
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5.3, Font Awesome 6.5.
- **Backend:** Core PHP 8.x with clean object-oriented PDO and session management.
- **Database:** MySQL / MariaDB (`reusehub_db`) with 10 interrelated relational tables, indexes, and cascades.
- **Design System:** Airbnb-grade visual polish with Deep Forest Green (`#174C3C`), Fresh Green (`#2F855A`), Soft Off-White (`#F8FAF7`), and Warm Yellow (`#F4C95D`).
- **Zero Heavy Frameworks:** Pure PHP & MySQL optimized for lightning-fast execution on WAMP Server.

---

## 🚀 Key Functional Modules

### 1. Brand Identity & Homepage
- **Hero Section:** Large dynamic search bar (search by product name, Kerala city filter, category selector), hero stats, and live community metrics.
- **Popular Categories:** 8 core categories (Electronics, Fashion & Wedding Wear, Photography, Camping & Trekking, Power Tools, Sports & Fitness, Event & Party, Books).
- **Featured Rentals:** Database-driven listing cards showing daily price, refundable security deposit, condition grade, location, live star rating, and wishlist heart button.
- **How It Works:** 3-step walkthrough (Explore &rarr; Book &rarr; Enjoy).
- **Rental Savings Calculator:** Interactive live widget comparing retail purchase price vs. rental cost across chosen durations.
- **Customer Testimonials & Footer:** Community reviews, Kerala address, contact information, and rental guidelines.

### 2. User Authentication & Access Control
- User registration with BCRYPT password hashing and email uniqueness verification.
- Dual-role account architecture: users can act as **Renters** and **Owners** simultaneously.
- 1-Click Demo Login helpers on `login.php` for Admin, Owner, and Renter testing.
- Profile management with avatar, contact information, address, PIN code, and password change.
- Strict session management and CSRF protection on every form.

### 3. Product Exploration & Filtering
- Full-text search across titles, descriptions, and locations.
- Multi-parameter filtering by Category, City (Kochi, Trivandrum, Kozhikode, Thrissur), Price Range (Min/Max), and Item Condition.
- Availability toggle switch to show only items currently available for rent.
- Sorting by Newest, Price (Low to High), Price (High to Low), and Popularity.
- Database pagination for high-volume collections.

### 4. Product Details & Live Booking Calculator
- High-resolution gallery with primary photo and clickable thumbnail switcher.
- Dynamic booking interface:
  - Select start and end rental dates.
  - Automatically validates that start date is not in the past and end date is valid.
  - Checks for overlapping confirmed bookings to prevent double-booking.
  - Prevents owners from booking their own listings.
  - Real-time client & server calculation of rental charges, refundable deposit, 3% marketplace service fee, and total estimated cost.
- Detailed owner profile card with active listing count, member since, and contact phone.
- Ratings & reviews breakdown with verified reviewer comments.
- Listing report modal to flag suspicious or inaccurate listings.

### 5. Multi-Image Product Listing Module
- Multi-photo upload with live client-side preview thumbnails.
- Server-side image MIME type, extension, and size validation.
- Fields: Title, category, condition grade, daily rate, security deposit, original purchase price, location, PIN code, rental guidelines.
- New submissions enter a `pending` state for administrator review.

### 6. Demo Payment Management
- Transparent cost breakdown: Rental charge + Refundable deposit + Marketplace service fee.
- Demo payment options: UPI (Google Pay, PhonePe, Paytm simulation), Credit/Debit Card, and Pay on Pickup.
- Structured transaction logging (`payments` table) ready for Razorpay / Cashfree production integration.
- Instant automated notifications dispatched to both owner and renter upon checkout.

### 7. Renter Dashboard
- Key metrics: Total rentals, pending requests, active rentals, and all-time savings.
- Tabbed booking management (All, Pending, Approved, Active, Completed, Cancelled).
- Cancellation with reason and automatic deposit refund.
- Item return flow and post-rental review & star-rating submission.
- Chronological rental history and financial savings audit trail.

### 8. Owner Dashboard
- Real-time business metrics: Total listed items, active listings, pending booking requests, completed rentals, and total rental earnings.
- Product inventory management: Add, edit, delete listings, and toggle live/hidden availability.
- Booking requests control: Accept request, decline request with reason, mark item handed over (active), and mark item safely returned (completed).

### 9. Admin Control Panel
- Platform KPI metrics: Total registered users, total products, total bookings, gross platform transaction volume (GMV), and platform commission revenue.
- Pending listings moderation queue: 1-click Approve or Reject listings.
- User management: Search, toggle active/suspended account status, switch user roles.
- Categories manager: Add, edit, or remove marketplace categories and icons.
- Reports manager: Review community dispute flags and take down infringing items.

---

## 📂 Project Directory Structure

```
ReUseHub/
│
├── index.php                 # Homepage with hero, categories, featured rentals & calculator
├── login.php                 # Authentication login with 1-click demo credentials
├── register.php              # User registration with BCRYPT password hashing
├── logout.php                # Safe session destruction
├── explore.php               # Search & filtered marketplace with pagination
├── product-details.php       # Product gallery, owner card, reviews & live booking widget
├── list-item.php             # Multi-image product listing submission
├── booking.php               # Booking confirmation & demo payment processing
├── wishlist.php              # Saved items page with AJAX toggle
├── contact.php               # Contact support, FAQs, and listing report processor
│
├── admin/                    # Platform Administration
│   ├── dashboard.php         # Admin metrics, pending approvals queue & revenue stats
│   ├── users.php             # User management, status toggles, role updates
│   ├── products.php          # Platform-wide product moderation
│   ├── bookings.php          # Booking & transaction audit trail
│   ├── categories.php        # Rental categories manager
│   └── reports.php           # Reported listings & content moderation
│
├── owner/                    # Product Owner Portal
│   ├── dashboard.php         # Owner metrics, earnings summary & quick actions
│   ├── my-products.php       # Inventory table with live availability toggles
│   ├── add-product.php       # Shortcut to list new product
│   ├── edit-product.php      # Pre-populated edit listing form
│   └── booking-requests.php  # Incoming rental request approval/rejection flow
│
├── user/                     # Renter Portal
│   ├── dashboard.php         # Renter metrics, active handovers & savings summary
│   ├── my-bookings.php       # Booking tracking, cancellation & review submission
│   ├── rental-history.php    # Completed rentals history & financial savings report
│   └── profile.php           # Profile information & password change
│
├── config/                   # Configuration
│   ├── database.php          # Singleton PDO connection & environment constants
│   └── setup_db.php          # Database setup and migration script
│
├── includes/                 # Reusable Layout & Utilities
│   ├── header.php            # HTML head, Google Fonts, Bootstrap & meta tags
│   ├── footer.php            # Modern footer, copyright, legal links & JS scripts
│   ├── navbar.php            # Navigation bar with dynamic notification & role badge
│   ├── auth.php              # Access control guards (requireLogin, requireAdmin, etc.)
│   └── functions.php         # Pricing calculation, CSRF, XSS, flash messages & helpers
│
├── assets/                   # Static Assets
│   ├── css/
│   │   └── style.css         # Custom ReUseHub design system & responsive layout
│   ├── js/
│   │   └── main.js           # AJAX wishlist, live booking calculator, savings slider
│   └── images/               # Default avatars, brand icons & placeholders
│
├── uploads/                  # User Uploads
│   ├── products/             # Product photography uploads
│   └── avatars/              # User profile avatars
│
├── database/                 # Database Resources
│   └── reusehub_db.sql       # Complete MySQL schema & realistic Kerala seed data
│
└── tests/                    # Testing
    └── test_suite.php        # 17 automated integration & business logic tests
```

---

## ⚡ Quick Start with WAMP Server

For complete step-by-step setup details, refer to [`WAMP_SETUP_GUIDE.md`](WAMP_SETUP_GUIDE.md).

1. Ensure **WAMP Server** is running with **MySQL** on port `3306`.
2. Import `database/reusehub_db.sql` via **phpMyAdmin** or run:
   ```powershell
   & "C:\wamp64\bin\php\php8.2.26\php.exe" d:\ReUseHub\config\setup_db.php
   ```
3. Start the application:
   ```powershell
   & "C:\wamp64\bin\php\php8.2.26\php.exe" -S localhost:8000
   ```
4. Open your browser at `http://localhost:8000` or via WAMP `http://localhost/ReUseHub/`.

---

## 🔒 Security Measures Implemented

1. **SQL Injection Prevention:** Every query uses PDO prepared statements with bound parameters (`execute([$val])`).
2. **Cross-Site Scripting (XSS):** All dynamic browser outputs pass through `e()` (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
3. **Cross-Site Request Forgery (CSRF):** Forms include cryptographic session tokens verified with `hash_equals()`.
4. **Password Security:** Hashes generated using `password_hash($pass, PASSWORD_BCRYPT)` and checked with `password_verify()`.
5. **Session Security:** Session IDs regenerated upon login to prevent session fixation; secure cookie flags configured.
6. **Double-Booking Guard:** `isDateRangeAvailable()` ensures overlapping booking dates are strictly prevented on the server.
7. **Client-Agnostic Pricing:** All rental charges, platform service fees, and deposits are computed exclusively on the backend.
8. **File Upload Security:** Uploaded files validated against allowed MIME types and extensions, given unique randomized filenames, and isolated from executable paths.

---

&copy; 2026 ReUseHub Technologies. Built with pride for Kerala & India.

