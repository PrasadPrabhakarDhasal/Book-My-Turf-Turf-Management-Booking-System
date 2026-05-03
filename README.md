# TURF BOOKING WEBSITE - SETUP & INSTALLATION GUIDE

## 📋 PROJECT OVERVIEW
A complete turf booking system with advanced features including:
- Real-time slot availability
- Booking registration form
- Admin panel for management
- AJAX-based live updates
- Responsive design with sports theme
- Input validation and error handling

---

## 📁 FILES CREATED

### 1. **schema.sql** - Database Schema
- Creates `turf_booking` database
- Tables: `bookings`, `turfs`, `admin_users`
- Includes sample data and indexes

### 2. **db.php** - Database Connection
- MySQL connection configuration
- Helper functions for validation and sanitization
- JSON response helpers

### 3. **index.php** - Main Landing Page
- Booking form with all required fields
- Real-time slots display table
- Responsive design
- Success modal

### 4. **booking.php** - Booking Submission Handler
- Process booking requests via AJAX/POST
- Validation and duplicate prevention
- Database insertion
- Conflict detection

### 5. **slots.php** - Slots API Endpoint
- Fetch available slots for selected date/turf
- Time slot generation (6 AM - 10 PM)
- Real-time availability checking
- Turf list retrieval

### 6. **admin.php** - Admin Dashboard
- Admin authentication
- View all bookings in table format
- Approve/Cancel/Delete bookings
- Update booking status
- Simple demo login: admin/admin123

### 7. **style.css** - Styling
- Modern sports theme with green and blue colors
- Responsive grid layouts
- Smooth animations and transitions
- Mobile-friendly design
- Status badges and visual feedback

### 8. **script.js** - Frontend Logic
- Real-time validation
- AJAX requests for bookings and slots
- Auto-refresh slots every 30 seconds
- Modal dialogs
- Form handling and error display

---

## 🔧 INSTALLATION STEPS

### Quick Start for Login/Signup (XAMPP)
1. Start **Apache** and **MySQL** in XAMPP Control Panel.
2. Open this URL in browser:
   `http://localhost/wtl%20project%20(2)/wtl%20project/setup_database.php`
3. After success, open:
   - `http://localhost/wtl%20project%20(2)/wtl%20project/signup.html`
   - `http://localhost/wtl%20project%20(2)/wtl%20project/login.html`

### Step 1: Database Setup
1. Open phpMyAdmin or MySQL command line
2. Execute the `schema.sql` file to create the database
```sql
SOURCE path/to/schema.sql;
```

### Step 2: Configure Database Connection
Edit `db.php` and update:
```php
define('DB_HOST', 'localhost');      // Your MySQL host
define('DB_USER', 'root');            // Your MySQL username
define('DB_PASS', '');                // Your MySQL password
define('DB_NAME', 'turf_booking');    // Database name
```

### Step 3: Place Files on Web Server
Copy all files to your web server directory:
- Windows (XAMPP): `C:\xampp\htdocs\turf-booking\`
- Linux (Apache): `/var/www/html/turf-booking/`
- macOS (MAMP): `/Applications/MAMP/htdocs/turf-booking/`

### Step 4: Access the Application
```
User Site:    http://localhost/turf-booking/index.php
Admin Panel:  http://localhost/turf-booking/admin.php
```

---

## 📊 DATABASE SCHEMA

### bookings Table
```
booking_id (INT, Primary Key, Auto Increment)
name (VARCHAR 100)
mobile (VARCHAR 15)
email (VARCHAR 100)
date (DATE)
start_time (TIME)
end_time (TIME)
turf_name (VARCHAR 100)
status (ENUM: pending, approved, cancelled)
created_at (TIMESTAMP)
updated_at (TIMESTAMP)
```

### turfs Table
```
turf_id (INT, Primary Key)
turf_name (VARCHAR 100, Unique)
location (VARCHAR 255)
price_per_hour (DECIMAL 10,2)
created_at (TIMESTAMP)
```

### admin_users Table
```
admin_id (INT, Primary Key)
username (VARCHAR 50, Unique)
password (VARCHAR 255, Hashed)
email (VARCHAR 100)
created_at (TIMESTAMP)
```

---

## 🎯 FEATURES IMPLEMENTED

### ✅ Booking Registration Form
- Full Name validation
- 10-digit mobile number validation
- Email validation
- Date selection (today or future)
- Start & End time selection
- Turf selection
- Real-time validation feedback

### ✅ Slot Availability Table
- Time slots from 6 AM to 10 PM
- Live availability status
- Color-coded badges (Green=Available, Red=Reserved)
- Click on available slot to auto-fill times
- Auto-refresh every 30 seconds

### ✅ Booking Status Management
- Pending (Awaiting approval)
- Approved (Confirmed)
- Cancelled (Not available)

### ✅ Admin Panel Features
- Secure login (Demo: admin/admin123)
- View all bookings in table format
- Approve pending bookings
- Cancel approved bookings
- Delete bookings permanently
- Logout functionality

### ✅ Input Validation
- Required fields validation
- Email format validation
- Phone number format (exactly 10 digits)
- Time format validation
- Date validation (no past dates)
- Time range validation (start < end)
- Duplicate booking prevention

### ✅ AJAX Functionality
- Fetch slots without page reload
- Submit bookings without refresh
- Admin operations without refresh
- Auto-update slots every 30 seconds
- Real-time error handling

### ✅ Responsive Design
- Mobile-friendly layout
- Tablet optimized
- Desktop full-featured view
- Flexbox and Grid layouts
- Touch-friendly buttons

---

## 🎨 COLOR SCHEME

| Color | Usage |
|-------|-------|
| #0f3460 (Dark Blue) | Primary, Navigation, Buttons |
| #27ae60 (Green) | Available slots, Success actions |
| #e74c3c (Red) | Reserved slots, Danger actions |
| #f39c12 (Orange) | Warnings |
| #ecf0f1 (Light) | Backgrounds |

---

## 🔐 SECURITY FEATURES

✓ SQL Injection Prevention (MySQLi prepared via real_escape_string)
✓ Input Sanitization (htmlspecialchars)
✓ Password Hashing (bcrypt)
✓ Session Management
✓ CSRF Protection (basic)
✓ XSS Prevention
✓ Data Validation

---

## 📱 API ENDPOINTS

### Booking Operations
```
POST   /booking.php?action=book        - Submit new booking
GET    /booking.php?action=get_bookings - Fetch all bookings
```

### Slots Operations
```
GET    /slots.php?action=get_slots&date=YYYY-MM-DD&turf=NAME - Get slots
GET    /slots.php?action=get_turfs - Get all turfs
```

### Admin Operations
```
POST   /admin.php?action=login - Admin login
POST   /admin.php?action=update_status - Update booking status
POST   /admin.php?action=delete_booking - Delete booking
GET    /admin.php?logout=1 - Logout
```

---

## 🧪 TESTING

### Test Bookings
1. Go to http://localhost/turf-booking/index.php
2. Fill in the form:
   - Name: John Doe
   - Mobile: 9876543210
   - Email: john@example.com
   - Turf: Premium Turf 1
   - Date: Tomorrow
   - Time: 6:00 AM - 7:00 AM
3. Click "Book Turf"
4. Verify in Admin Panel

### Test Admin Features
1. Go to http://localhost/turf-booking/admin.php
2. Login with:
   - Username: admin
   - Password: admin123
3. Try: Approve, Cancel, Delete bookings

### Test Real-time Updates
1. Open two browser windows
2. Make a booking in one window
3. Check slots update in the other window

---

## 🐛 TROUBLESHOOTING

### Issue: Database Connection Failed
**Solution:** Check db.php credentials and ensure MySQL is running

### Issue: Booking Form Not Submitting
**Solution:** Check browser console for errors. Verify booking.php is in same directory

### Issue: Admin Login Not Working
**Solution:** Verify admin_users table has data. Check password hash in db

### Issue: Slots Not Loading
**Solution:** Check date is valid. Verify turf name matches database

---

## 📝 NOTES

- Default slot times: 6 AM to 10 PM (1-hour slots)
- Minimum booking time: 1 hour
- Past dates cannot be booked
- Once approved, slots are blocked
- Admin can cancel any booking at any time
- Session expires on browser close (can be modified)

---

## 🚀 IMPROVEMENTS YOU CAN ADD

1. **Payment Gateway Integration** (Razorpay, Stripe)
2. **Email Notifications** (Booking confirmation, reminders)
3. **SMS Notifications** (Twilio API)
4. **User Registration** (Login/Registration for users)
5. **Booking History** (User dashboard)
6. **Reviews & Ratings** (Turf reviews)
7. **Multiple Admins** (Role-based access)
8. **Advanced Scheduling** (Multi-day bookings)
9. **Pricing Rules** (Peak hours, discounts)
10. **Analytics Dashboard** (Charts, reports)

---

## 📞 SUPPORT

For issues or questions, check:
- Browser console (F12) for JavaScript errors
- Server error logs in logs/ directory
- Database logs if available

---

**Created:** 2024
**Version:** 1.0
**Status:** Production Ready ✅
