[![Review Assignment Due Date](https://classroom.github.com/assets/deadline-readme-button-22041afd0340ce965d47ae6ef1cefeee28c7c493a6346c4f15d667ab976d596c.svg)](https://classroom.github.com/a/JXuiURJJ)
# PHP Home Project Template

A minimal, reusable PHP project template for small home projects, learning, or prototyping. Includes **reusable header, footer, navigation, database connection, `.env` support, user queries, and a contact form**.  

This template is designed to help you quickly start a PHP project with a clean structure, modern best practices, and modular code.

---

## Features

- Reusable **header, footer, and navigation**
- Dynamic page titles for each page
- `.env` support for storing database credentials
- Database connection using **PDO**
- `user_queries.php` for reusable user-related queries
- Simple **contact form** with server-side validation
- External CSS (`styles.css`) and JS (`app.js`) with deferred loading
- Lightweight and framework-free
- Modular and easy to extend

---

## Folder Structure
```
project/
├── config/
│ └── env.php # Function to read .env files
├── database/
│ ├── db.php # PDO database connection
│ └── user_queries.php # Functions for user-related queries
├── includes/
│ ├── header.php # Page header with navigation
│ ├── nav.php # Navigation menu
│ └── footer.php # Page footer
├── public/
│ ├── index.php # Home page
│ ├── about.php # About page
│ ├── contact.php # Contact form page
│ ├── login.php # Login page (optional)
│ ├── register.php # Registration page (optional)
│ ├── css/
│ │ └── styles.css # All styles
│ └── js/
│ └── app.js # JavaScript (deferred)
├── .env.example # Example environment file
└── README.md # Project documentation

```
---

## Requirements

- PHP 7.4+  
- MySQL or MariaDB  
- Web server (Apache, Nginx, or PHP built-in server)  
- Optional: Composer (if you want to add packages in the future)

---

## Getting Started

### 1. Clone the Template

Use the template

### 2. Configure the Environment
Copy .env.example to .env and update the database credentials:

DB_USER=root
DB_PASS=root

Keep .env out of version control. Never commit it with real credentials.

USE .gitignore