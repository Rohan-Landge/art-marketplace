# Art Marketplace Professional Upgrade - Installation Guide

## Important: Database Migration Required

This upgrade introduces new tables and features. You MUST run the database migration SQL file to enable all professional features.

### Step 1: Run Database Migration

1. Open phpMyAdmin or your MySQL client
2. Navigate to your `art_marketplace` database
3. Click "SQL" tab and paste the contents of: `DATABASE_MIGRATION_PROFESSIONAL.sql`
4. Click "Go" to execute

**OR** run via command line:

```bash
mysql -u root -p art_marketplace < DATABASE_MIGRATION_PROFESSIONAL.sql
```

### Step 2: Verify Installation

After running the migration, verify these tables exist:

- ✅ `orders` (updated with new columns)
- ✅ `wishlist`
- ✅ `reviews`
- ✅ `notifications`
- ✅ `categories`
- ✅ `admin_logs`
- ✅ `csrf_tokens`

---

## New Features Installed

### 1. Admin Panel
- **Access:** Login with admin account, auto-redirects to `/admin/dashboard.php`
- **Features:**
  - Dashboard analytics (users, artists, artworks, orders, revenue)
  - Manage Users (block/unblock, promote to admin)
  - Manage Artworks (activate/deactivate/delete)
  - Manage Orders (update order status, view details)
  - Admin Logs for audit trail

### 2. Order Management System
- **Buyer Orders:** `/orders/my_orders.php` - View purchased artworks
- **Artist Orders:** `/orders/artist_orders.php` - View sales, update shipping status
- **Admin Orders:** `/admin/orders.php` - Manage all orders

**Order Statuses:**
- pending
- paid
- processing
- shipped
- delivered
- cancelled

### 3. Wishlist / Favorites System
- **Access:** `/wishlist/index.php` (logged-in users only)
- **Features:**
  - Add/remove artworks from wishlist
  - View all wishlisted items
  - Quick links to artwork details
  - Heart icon toggle on gallery

### 4. Notifications System
- **Location:** Navbar notification bell icon
- **Types:**
  - Payment success
  - Artwork sold
  - New order
  - Order shipped
  - Order delivered
  - New review
  - New message
  - Artist followed

### 5. Reviews & Ratings System
- **Location:** On artwork detail pages
- **Features:**
  - 1-5 star ratings
  - Written reviews
  - Prevent duplicate reviews
  - Display average rating and review count

### 6. Security Improvements
- ✅ CSRF token protection on all forms
- ✅ Admin role-based access control
- ✅ User account blocking
- ✅ Input sanitization with `sanitize()`
- ✅ Output escaping with `escape()`
- ✅ Prepared statements everywhere
- ✅ Session security improvements
- ✅ Image upload validation with MIME type checking
- ✅ Unique filename generation for uploads

### 7. Helper Functions System
- **Location:** `/config/helpers.php`
- **Functions available:**
  - `is_authenticated()` - Check if user logged in
  - `is_admin()`, `is_artist()`, `is_buyer()` - Check user role
  - `require_auth()`, `require_admin()`, `require_artist()` - Enforce access
  - `generate_csrf_token()`, `verify_csrf_token()` - CSRF protection
  - `sanitize()`, `escape()` - Input/output security
  - `format_currency()`, `format_date()`, `format_datetime()` - Formatting
  - `create_notification()` - Send notifications
  - `get_unread_notifications_count()` - Get notification badge count
  - `is_in_wishlist()` - Check wishlist status
  - `get_artist_earnings()`, `get_artist_sales_count()` - Artist analytics
  - Many more... see `/config/helpers.php` for full list

---

## Authentication Changes

### Admin Account Setup

1. Create admin user with registration or directly in database:

```sql
UPDATE users SET is_admin = 1, role = 'artist' WHERE id = 1;
```

2. Admin users automatically redirect to `/admin/dashboard.php` on login

### User Blocking

Blocked users cannot login. Admins can block/unblock users in admin panel.

---

## File Structure

```
art-marketplace/
├── admin/
│   ├── dashboard.php          (Admin dashboard with analytics)
│   ├── users.php              (Manage users)
│   ├── artworks.php           (Manage artworks)
│   └── orders.php             (Manage orders)
├── ajax/
│   ├── mark_notification_read.php
│   └── toggle_wishlist.php
├── orders/
│   ├── my_orders.php          (Buyer orders)
│   └── artist_orders.php      (Artist orders)
├── wishlist/
│   └── index.php              (Wishlist page)
├── config/
│   ├── helpers.php            (Core functions)
│   ├── ImageValidator.php     (Image upload validation)
│   └── ...
├── DATABASE_MIGRATION_PROFESSIONAL.sql
└── ...
```

---

## Updated Pages

The following pages have been updated with new features:

- ✅ `auth/login.php` - Admin login support, account blocking
- ✅ `includes/navbar.php` - Admin link, wishlist, notifications dropdown
- ✅ `payment/verify_payment.php` - Order creation and notifications
- ✅ `orders/my_orders.php` - Professional layout
- ✅ `orders/artist_orders.php` - Order status management

---

## Image Upload Security

New image validation system:

```php
require_once __DIR__ . '/../config/ImageValidator.php';

$validator = new ImageValidator();
$result = $validator->save($_FILES['image'], $upload_dir);

if ($result['success']) {
    // Use $result['filename'] and $result['filepath']
} else {
    // Handle errors: $result['errors']
}
```

**Allowed types:** JPG, PNG, WebP (max 5MB)

---

## CSRF Protection Example

```php
// Generate token
$token = generate_csrf_token();

// In form
echo '<input type="hidden" name="csrf_token" value="' . $token . '">';

// Verify
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    die('Security verification failed');
}
```

---

## Testing Checklist

- [ ] Database migration ran successfully
- [ ] Create admin user and login
- [ ] Admin panel accessible and showing data
- [ ] Upload artwork with new image validator
- [ ] Purchase artwork and verify order created
- [ ] Check notifications appear
- [ ] Test wishlist toggle
- [ ] Artist can update order status
- [ ] Admin can manage users/artworks/orders
- [ ] Block/unblock user functionality works
- [ ] CSRF tokens on forms
- [ ] Password toggle eye icon works
- [ ] Mobile responsive layout

---

## Existing Features Preserved

All existing functionality remains intact:

- ✅ User authentication (register/login/logout)
- ✅ Forgot password with email reset
- ✅ Password reset flow
- ✅ Password visibility toggle
- ✅ Razorpay payment integration
- ✅ Artwork upload
- ✅ Gallery viewing
- ✅ Contact artist
- ✅ INR currency
- ✅ Bootstrap 5 design
- ✅ Responsive layout

---

## Support

For issues or questions:

1. Check database migration ran without errors
2. Verify all new files exist in correct directories
3. Check PHP error logs: `/xampp/php/logs/`
4. Verify database has new tables

---

## Next Steps

1. Run database migration
2. Test admin account
3. Explore new features
4. Update your UI as needed
5. Deploy to production

---

**Last Updated:** May 20, 2026
**Version:** Professional Marketplace 1.0
