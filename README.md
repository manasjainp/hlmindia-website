# Hertz Logic Machinery India LLP - Website

A corporate website for **Hertz Logic Machinery India LLP**, a company specializing in high-quality beverage machinery spare parts and engineering solutions. The website features a product catalog, service information, a contact form, and a secure PHP-based admin panel to manage inquiries.

## 🚀 Features

- **Responsive Design**: Fully optimized for mobile, tablet, and desktop viewing.
- **Product Catalog**: Dynamic product grid with search and category filtering functionalities.
- **Contact Form**: Integrated with Web3Forms for email delivery, and a fallback/storage system that saves submissions locally.
- **Admin Dashboard**: A secure PHP backend (`view_messages.php`) to view, manage, and export customer inquiries as CSV.
- **Basic Content Protection**: Scripts to disable right-clicking and common developer tools shortcuts to deter casual content scraping.
- **Interactive UI**: Smooth scrolling, counter animations, lazy-loaded elements, and interactive form pre-filling from product pages.

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, Vanilla JavaScript (ES6+)
- **Backend**: PHP 7.4+ (for Admin Panel & Security Logging)
- **Data Storage**: Local JSON (`contact_submissions.json`)
- **Third-Party APIs**: Web3Forms (for contact form email forwarding)

## 📂 Project Structure

```text
/workspaces/hlmindia-website/
├── index.html           # Home page
├── about.html           # About Us page
├── contact.html         # Contact Us page with form
├── products.html        # Product catalog and services
├── styles.css           # Global stylesheet
├── script.js            # Main frontend JavaScript logic
└── assets/              # Images and Backend files
    ├── view_messages.php          # Secure Admin Panel
    ├── contact_submissions.json   # Database for form submissions
    ├── security.log               # Access and brute-force protection logs
    └── ... (images & icons)
```

## ⚙️ Setup and Installation

Because the admin panel relies on PHP to parse and write JSON files, you must run this project on a PHP-enabled web server (like Apache, Nginx, or a local environment like XAMPP, MAMP, or the built-in PHP server).

1. **Clone the repository** (or download the files to your server directory).
2. **Set File Permissions**: Ensure that the web server has **write access** to the following files so the admin panel can update them:
   - `assets/contact_submissions.json`
   - `assets/security.log`
3. **Run a local server**:
   If you have PHP installed, you can quickly start a local server from the project root:
   ```bash
   php -S localhost:8000
   ```
4. Visit `http://localhost:8000` in your browser.

## 🔒 Admin Panel Access

The admin panel is located at `assets/view_messages.php`. 

- **Authentication**: The panel is protected by a hardcoded password and a 3-attempt brute-force lockout mechanism (15 minutes).
- **Changing the Password**: Open `assets/view_messages.php` and modify the `$admin_password` variable at the top of the file to secure your deployment.

## 📝 License

&copy; 2026 Hertz Logic Machinery India LLP. All rights reserved.