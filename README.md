# Art Marketplace - README

## Overview
Art Marketplace is a complete PHP + MySQL web application that connects artists and buyers. Artists can upload and sell their artworks, while buyers can browse the gallery and contact artists directly.

## Key Features

### 🎨 Artist Features
- User registration and authentication
- Upload artwork with title, description, price, and category
- Manage portfolio (view, edit, delete artworks)
- Artist dashboard with statistics
- Receive inquiries from interested buyers

### 👥 Buyer Features
- Browse complete art gallery
- Search and filter by category, price, and date
- View detailed artwork information
- Contact artists directly via form or WhatsApp
- User registration

### 🔐 Security
- Secure password hashing (bcrypt)
- SQL injection prevention (prepared statements)
- File upload validation
- Session-based authentication
- CSRF protection ready

### 🎯 Admin Dashboard
- View platform statistics
- User management
- Artwork management

## Tech Stack
- **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript
- **Backend:** Core PHP (no frameworks)
- **Database:** MySQL with PDO
- **Server:** Apache (XAMPP compatible)

## Quick Start

1. **Extract files to XAMPP htdocs:**
   ```
   C:\xampp\htdocs\art-marketplace\
   ```

2. **Import database:**
   - Open phpMyAdmin
   - Import `DATABASE_SETUP.sql`

3. **Update config (if needed):**
   - Edit `config/db.php`
   - Update database credentials

4. **Access application:**
   ```
   http://localhost/art-marketplace/
   ```

## Project Structure

```
art-marketplace/
├── config/              # Database config
├── auth/                # Login, Register, Auth
├── artist/              # Artist dashboard & uploads
├── gallery/             # Browse artworks
├── contact/             # Contact forms
├── includes/            # Reusable components
├── assets/              # CSS, JS, Images
└── uploads/             # User uploaded files
```

## Default Login
- **Email:** artist@test.com
- **Password:** password123
(Create your own via registration)

## File Permissions
Ensure these folders are writable:
```bash
chmod 755 uploads/
chmod 755 uploads/artworks/
```

## Database Tables

### users
- id, name, email, password, role, bio, profile_image, phone

### artworks
- id, user_id, title, description, price, image, category, status

## Features Breakdown

### Authentication ✅
- Registration with validation
- Login with session
- Logout functionality
- Role-based access (artist/buyer)

### Artworks ✅
- Upload with image
- Display with details
- Search & filter
- Delete functionality
- Category system

### Gallery ✅
- Browse all artworks
- Sort by date/price
- Search functionality
- View single artwork

### Contact ✅
- Contact form
- Email integration
- WhatsApp links

### UI/UX ✅
- Bootstrap responsive design
- Modern card-based layout
- Mobile friendly
- Dark navbar
- Smooth animations

## Security Checklist
- [x] Password hashing
- [x] Prepared statements
- [x] Input validation
- [x] File type checking
- [x] Session authentication
- [x] XSS prevention
- [x] SQL injection prevention
- [x] File upload security

## Future Enhancements
- Payment gateway integration
- Email notifications
- Ratings and reviews
- Shopping cart
- Order management
- Admin dashboard
- Advanced analytics
- API endpoints
- Mobile app

## Troubleshooting

### Can't upload images?
- Check folder permissions
- Verify upload_max_filesize in php.ini

### Database connection fails?
- Start MySQL service
- Check credentials in config/db.php

### CSS/JS not loading?
- Clear browser cache
- Check file paths

## Support & Documentation
- See SETUP_GUIDE.md for detailed setup
- Check comments in PHP files for code explanation
- Review Bootstrap documentation for UI components

## License
Open source project. Use freely for educational and commercial purposes.

---

**Art Marketplace v1.0**
Built with ❤️ for artists and art lovers
