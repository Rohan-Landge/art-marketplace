# Art Marketplace - Setup Guide

## Installation Instructions

### 1. Prerequisites
- XAMPP (or similar Apache + MySQL + PHP server)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser

### 2. Database Setup

1. **Open phpMyAdmin**
   - Go to: `http://localhost/phpmyadmin`
   - Login with default credentials (usually root / empty password)

2. **Create Database**
   - Open the `DATABASE_SETUP.sql` file
   - Copy and paste the SQL commands into phpMyAdmin
   - Execute the queries
   - Two tables will be created: `users` and `artworks`

### 3. Configuration

1. **Open `/config/db.php`**
   - Update database credentials if needed:
     - `DB_HOST`: localhost (usually correct)
     - `DB_USER`: root (default XAMPP user)
     - `DB_PASS`: empty for XAMPP (default)
     - `DB_NAME`: art_marketplace (should match)

2. **File Permissions**
   - Ensure `/uploads/artworks/` folder has write permissions
   - On Linux/Mac: `chmod 755 uploads/artworks`

### 4. Access the Application

1. **Place folder in XAMPP**
   - Move `art-marketplace` folder to `C:\xampp\htdocs\`

2. **Start XAMPP**
   - Start Apache and MySQL services

3. **Open in Browser**
   - Navigate to: `http://localhost/art-marketplace/`

### 5. Test Accounts

After database setup, you can create accounts through the registration page.

**Or use test data:**
```sql
-- Test User (Artist)
INSERT INTO users (name, email, password, role) VALUES 
('Test Artist', 'artist@test.com', '$2y$10$hash_here', 'artist');

-- Test User (Buyer)
INSERT INTO users (name, email, password, role) VALUES 
('Test Buyer', 'buyer@test.com', '$2y$10$hash_here', 'buyer');
```

## Features

### For Artists
- ✅ Register as an artist
- ✅ Upload artworks with images
- ✅ Manage portfolio
- ✅ Track artworks
- ✅ Receive inquiries

### For Buyers
- ✅ Browse gallery
- ✅ Search and filter artworks
- ✅ Contact artists
- ✅ WhatsApp integration

### Admin Features
- Artist dashboard
- Art management
- User statistics

## Folder Structure

```
art-marketplace/
├── index.php                 # Home page
├── config/
│   └── db.php              # Database configuration
├── auth/
│   ├── login.php           # Login page
│   ├── register.php        # Registration page
│   ├── logout.php          # Logout handler
│   └── auth_check.php      # Authentication functions
├── artist/
│   ├── dashboard.php       # Artist dashboard
│   ├── upload_art.php      # Upload artwork
│   ├── my_art.php          # Manage artworks
│   └── delete_art.php      # Delete artwork
├── gallery/
│   ├── view_all.php        # Browse all artworks
│   └── view_single.php     # View single artwork
├── contact/
│   └── contact_artist.php  # Contact form
├── includes/
│   ├── header.php          # HTML head + navbar
│   ├── navbar.php          # Navigation bar
│   └── footer.php          # Footer section
├── assets/
│   ├── css/
│   │   └── style.css       # Custom styles
│   ├── js/
│   │   └── script.js       # Custom JavaScript
│   └── images/             # Image files
└── uploads/
    └── artworks/           # Uploaded artwork images
```

## Security Features Implemented

1. **Password Security**
   - Password hashing with `password_hash()`
   - Verification with `password_verify()`

2. **Database Security**
   - PDO prepared statements (prevents SQL injection)
   - Parameterized queries

3. **File Upload Security**
   - File type validation (JPG, PNG, GIF, WebP)
   - File size limits (5MB max)
   - Unique filename generation

4. **Session Security**
   - Session-based authentication
   - User role verification

5. **Input Validation**
   - Email validation
   - Required field checks
   - XSS prevention with `htmlspecialchars()`

## Customization

### Change Theme Colors
Edit `/assets/css/style.css` and modify the `:root` variables:
```css
:root {
    --primary-color: #667eea;
    --secondary-color: #764ba2;
    --success-color: #48bb78;
}
```

### Add Categories
Update the category select in `/artist/upload_art.php`

### Configure Email
Update email settings in `/contact/contact_artist.php`

### WhatsApp Integration
Add phone numbers to user profiles for WhatsApp contact links

## Troubleshooting

### Database Connection Error
- Check MySQL is running
- Verify credentials in `config/db.php`
- Ensure database name matches

### File Upload Not Working
- Check `uploads/artworks/` folder exists
- Verify folder permissions (755 or 777)
- Check PHP `upload_max_filesize` setting

### Session Issues
- Ensure PHP sessions are enabled
- Clear browser cookies and try again

### Images Not Displaying
- Verify upload path is correct
- Check image file actually exists
- Ensure file permissions are correct

## API Endpoints

Currently, this is a traditional PHP application. Future versions could include:
- REST API for artworks
- JSON responses
- Mobile app integration

## Performance Tips

1. **Optimize Images**
   - Compress artwork images before uploading
   - Use WebP format when possible

2. **Database Indexing**
   - Indexes are already added to important columns
   - Add more as needed based on usage

3. **Caching**
   - Consider implementing page caching
   - Use browser caching headers

## Future Enhancements

- [ ] Payment gateway integration
- [ ] Advanced search filters
- [ ] Artist ratings/reviews
- [ ] Wishlist feature
- [ ] Email notifications
- [ ] Admin panel
- [ ] Multi-language support
- [ ] Social media sharing
- [ ] Mobile app
- [ ] API integration

## Support

For issues or questions:
- Check the code comments
- Review error logs in browser console
- Check PHP error log

## License

This project is provided as-is for educational and commercial use.

---

**Version 1.0** - Art Marketplace Platform
