# ⭐ ModernBlog — PHP Blog Platform

A fully-featured, production-style blog platform built with **PHP 8+, MySQL, and Vanilla JavaScript**.

No frameworks, no bloat — pure backend logic with a modern UI.

---

# 🚀 Features

## 🌐 Public Frontend

* Fully responsive modern UI (Dark/Light mode)
* Featured posts section with hero layout
* Post cards with reading time, views, likes, and comments
* Full article page with:

  * Table of Contents
  * Syntax Highlighting
  * Previous / Next navigation
* Like & Share system (Twitter, Facebook, Copy Link)
* AJAX-based comment system with moderation
* Live search with instant dropdown results
* Category filtering and search pages
* RSS Feed support (`feed.php`)
* Reading progress bar
* Back-to-top button

## 🛠 Admin Panel

* Dashboard with analytics and statistics
* Create/Edit posts using **Quill.js Rich Text Editor**
* Featured image preview support
* Tag management system
* Post management with:

  * Publish/Draft options
  * Bulk actions
  * Pagination and filters
* Category management (CRUD)
* Comment moderation system
* Contact messages inbox
* Site settings management
* Secure authentication system

## 🔐 Security Features

* CSRF Protection
* Password hashing using **bcrypt**
* PDO Prepared Statements
* SQL Injection protection
* Rate limiting on comments
* Security headers against XSS and Clickjacking
* Protected `includes/` directory using `.htaccess`

---

# 🧰 Tech Stack

| Layer               | Technology                      |
| ------------------- | ------------------------------- |
| Backend             | PHP 8+                          |
| Database            | MySQL / MariaDB                 |
| Frontend            | HTML5, CSS3, Vanilla JavaScript |
| Editor              | Quill.js                        |
| Icons               | Font Awesome 6                  |
| Fonts               | Google Fonts                    |
| Syntax Highlighting | Prism.js                        |
| Server              | Apache (XAMPP Recommended)      |

---

# 📁 Project Structure

```text
Blog platform/
├── admin/
├── api/
├── assets/
├── includes/
├── .htaccess
├── 404.php
├── about.php
├── category.php
├── contact.php
├── feed.php
├── index.php
├── login.php
├── post.php
├── search.php
└── setup.php
```

---

# ⚙️ Installation

## 1. Clone Repository

```bash
git clone https://github.com/YOUR_USERNAME/YOUR_REPOSITORY.git
```

Move the project into:

```text
C:\xampp\htdocs\
```

## 2. Start Server

Open **XAMPP Control Panel** and start:

* Apache
* MySQL

## 3. Run Setup

Open in browser:

```text
http://localhost/Blog%20platform/setup.php
```

The setup script will automatically:

* Create the database
* Create all required tables
* Insert sample data

## 4. Admin Login

```text
URL: http://localhost/Blog%20platform/login.php

Username: admin
Password: admin123
```

⚠️ Change the admin password immediately after first login.

---

# 🗄 Database Configuration

File: `includes/db.php`

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'blog_platform');
define('DB_USER', 'root');
define('DB_PASS', '');
```

---

# 📸 Screenshots

Add screenshots here:

* Homepage
* Single Post Page
* Admin Dashboard

---

# 🔒 Post Setup Security

After installation, disable access to `setup.php`.

Add the following rule inside `.htaccess`:

```apache
RewriteRule ^setup\.php$ - [F,L]
```

---

# 📌 Default Credentials

| Field    | Value                                         |
| -------- | --------------------------------------------- |
| Username | admin                                         |
| Password | admin123                                      |
| Email    | [admin@example.com](mailto:admin@example.com) |

---

# 👨‍💻 Author

**Mumtaz Sanjar**

GitHub: https://github.com/Mumtazsanjar

---

# ⭐ Support

If you found this project helpful, please consider giving it a ⭐ on GitHub.

---

# 📬 Contact

For collaboration, freelance work, or project inquiries, feel free to connect through GitHub.

---

## 💡 Note

This project was developed for learning, portfolio, and professional showcase purposes. It demonstrates secure coding practices, modern PHP architecture, and full-stack web development concepts.
