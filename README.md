# Modern E-Commerce Platform

A modern, open-source e-commerce platform built with PHP, designed to provide a full-featured online shopping experience with both customer-facing and administrative capabilities.

## 📋 Project Description

This is a comprehensive e-commerce web application developed using PHP and MySQL. It provides a complete shopping solution with product browsing, user authentication, shopping cart functionality, order management, and a robust admin dashboard for managing products, orders, and users. The platform emphasizes security best practices and modern web standards.

## ✨ Main Features

- **Product Catalog**: Browse and search through a comprehensive product inventory
- **Featured & Latest Products**: Showcase highlighted and recently added items on the homepage
- **User Registration & Login**: Secure user authentication system with role-based access control
- **Shopping Cart**: Full-featured cart system with add, update, and remove functionality
- **Checkout Process**: Complete order processing with form validation and order confirmation
- **Admin Dashboard**: Comprehensive administrative panel for managing:
  - Products (add, edit, delete)
  - Orders and order status updates
  - User management and role assignment
  - Order invoicing
- **Custom 404 Page**: User-friendly error handling for missing pages
- **Security Best Practices**: 
  - Password hashing
  - Prepared statements (PDO) to prevent SQL injection
  - Input validation and sanitization
  - CSRF protection
  - Session security

## 🛠️ Tech Stack

**Backend:**
- PHP (Procedural with PDO for database operations)
- MySQL database

**Frontend:**
- HTML5
- CSS3
- JavaScript (ES6+)
- Font Awesome icons

**Server:**
- Apache (with `.htaccess` for URL rewriting)

## 📁 Project Structure

```
ecommerce/
├── admin/              # Admin dashboard pages and functionality
│   ├── dashboard.php   # Admin overview page
│   ├── products.php    # Product management
│   ├── orders.php      # Order management
│   ├── users.php       # User management
│   └── ...
├── assets/             # Static files
│   ├── css/           # Stylesheets
│   ├── js/            # JavaScript files
│   └── images/        # Image assets
├── config/            # Configuration files
│   ├── constants.php  # Global constants and settings
│   └── database.php   # Database connection and functions
├── database/          # Database schema
│   └── ecommerce.sql  # SQL schema for initial setup
├── functions/         # Reusable PHP functions
│   ├── cart.php       # Shopping cart functions
│   ├── security.php   # Security utilities
│   ├── validation.php # Input validation
│   └── utilities.php  # General utility functions
├── includes/          # Shared includes (header, footer, navigation)
├── products/          # Product-related pages
│   ├── all.php        # All products listing
│   ├── category.php   # Category-based product view
│   └── details.php    # Product detail page
├── process/           # Form processing scripts
│   ├── login_process.php
│   ├── register_process.php
│   ├── cart_process.php
│   └── order_process.php
├── user/              # User-facing pages
│   ├── login.php      # Login page
│   ├── register.php   # Registration page
│   ├── cart.php       # Shopping cart
│   ├── checkout.php   # Checkout process
│   ├── profile.php    # User profile
│   └── orders.php     # Order history
├── index.php          # Homepage (featured and latest products)
├── 404.php           # Custom error page
└── .htaccess         # Apache configuration
```

## 🚀 Setup Instructions

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache server (XAMPP, LAMP, WAMP, or similar)
- Web browser

### Installation Steps

1. **Clone the Repository**
   ```bash
   git clone https://github.com/Robel-Y/ecommerce.git
   cd ecommerce
   ```

2. **Import the Database**
   - Open phpMyAdmin or your MySQL client
   - Create a new database (or use the default name `modern_shop_db`)
   - Import the database schema:
     ```bash
     mysql -u your_username -p your_database_name < database/ecommerce.sql
     ```
   - Or import via phpMyAdmin by selecting the `database/ecommerce.sql` file

3. **Configure Database Credentials**
   - Open `config/constants.php`
   - Update the database connection settings:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'modern_shop_db');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     ```

4. **Set Up Local Server**
   - **Using XAMPP:**
     - Copy the project folder to `htdocs/` directory
     - Start Apache and MySQL from XAMPP Control Panel
   - **Using LAMP/WAMP:**
     - Copy the project to your web server directory (`/var/www/html/` for LAMP)
     - Start Apache and MySQL services

5. **Access the Application**
   - Open your web browser
   - Navigate to: `http://localhost/ecommerce/`
   - For admin access: `http://localhost/ecommerce/admin/`

6. **Default Admin Credentials** (if seeded in database)
   - Check the database for default admin user or create one manually

## 🤝 Contributing

Contributions are welcome! Here's how you can help:

1. **Fork the Repository**
   - Click the 'Fork' button at the top right of this page

2. **Create a Feature Branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

3. **Make Your Changes**
   - Write clean, well-documented code
   - Follow existing code style and conventions
   - Test your changes thoroughly

4. **Commit Your Changes**
   ```bash
   git add .
   git commit -m "Add: description of your changes"
   ```

5. **Push to Your Fork**
   ```bash
   git push origin feature/your-feature-name
   ```

6. **Open a Pull Request**
   - Go to the original repository
   - Click 'New Pull Request'
   - Provide a clear description of your changes

### Coding Standards
- Follow PHP best practices
- Use meaningful variable and function names
- Comment complex logic
- Maintain security standards (input validation, prepared statements, etc.)
- Test all functionality before submitting

## 📄 License & Usage

This project is open-source and available for educational and commercial use. Feel free to use, modify, and distribute this code for your projects.

**Note:** This is a learning/demonstration project. For production use, consider additional security hardening, performance optimization, and comprehensive testing.

## 🐛 Issues & Support

If you encounter any issues or have questions:
- Open an issue on GitHub
- Provide detailed information about the problem
- Include steps to reproduce if applicable

## 🙏 Acknowledgments

Built with modern web development practices and security in mind. Special thanks to all contributors and the open-source community.

---

**Happy Shopping! 🛍️**
