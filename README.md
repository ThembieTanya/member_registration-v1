# member_registration-v1
Member Registration System - A comprehensive member registration and management system built with PHP and MySQL for member registration.

## ✨ Features

- **Multi-Role System**: Admin, Editor, Viewer with area-based restrictions
- **Member Management**: Complete CRUD operations with spouse and children details
- **Area-Based Access**: Editors restricted to assigned zones (A, B, C, D, E)
- **Data Export**: PDF reports and CSV export with advanced filtering
- **Activity Logging**: Full audit trail of all user actions
- **User Management**: Admin can create and manage users with role assignments
- **Printable Forms**: HTML, print-ready, and text versions available

## 🛠️ Tech Stack

- PHP 8.x
- MySQL
- FPDF/TCPDF for PDF generation
- HTML5, CSS3, JavaScript
- XAMPP

## 📦 Installation

1. Clone the repository:
```bash
git clone https://github.com/ThembieTanya/member-registration.git

2. Import database.sql to phpMyAdmin

3. Configure config.php with your database credentials

4. Access the system:
http://localhost/member_registration/login.php

👤 Default Credentials
**Role	Username	    Password**
Admin	  admin	        admin123
Editor	hopley_editor	admin123
Viewer	viewer	      viewer123

**🔒 Security**
a. Password hashing with bcrypt
b. PDO prepared statements
c. Role-based access control
d. Session management
e. Input validation

**📄 License**
All rights reserved. For educational and non profit-making use only.


---

You can customize it further based on your specific implementation!
@ThembieTanya2026 
