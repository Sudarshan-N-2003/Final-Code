# QPDS – VTU Question Paper Delivery System
## Hostinger Deployment Guide

---

## 📁 Project Structure

```
qpds/
├── index.php               ← Login page (entry point)
├── .htaccess               ← Security config
├── includes/
│   ├── config.php          ← DB credentials (EDIT THIS)
│   ├── database.php        ← PDO database class
│   ├── auth.php            ← Auth & session management
│   └── layout.php          ← Shared dashboard layout
├── auth/
│   ├── login.php           ← Login handler (AJAX)
│   └── logout.php
├── modules/
│   ├── admin/
│   │   ├── dashboard.php
│   │   ├── users.php       ← Full user management
│   │   ├── subjects.php
│   │   ├── departments.php
│   │   ├── papers.php
│   │   ├── reports.php
│   │   └── settings.php
│   ├── principal/
│   │   ├── dashboard.php
│   │   ├── users.php       ← Add HOD/Staff
│   │   ├── papers.php
│   │   └── approve.php
│   ├── hod/
│   │   ├── dashboard.php
│   │   ├── staff.php       ← Dept-restricted staff add
│   │   ├── questions.php   ← Approve questions
│   │   ├── subjects.php
│   │   └── copo.php
│   ├── staff/
│   │   ├── dashboard.php
│   │   ├── add_question.php
│   │   ├── questions.php
│   │   └── co_report.php
│   └── qp_generator/
│       ├── QPGenerator.php ← Core engine
│       ├── generate.php    ← Generator UI
│       ├── view.php        ← Paper preview
│       └── print.php       ← Print-ready output
├── api/
│   ├── subjects.php
│   ├── units.php
│   ├── cos.php
│   └── qb_stats.php
├── sql/
│   └── qpds_schema.sql     ← Import this to MySQL
└── uploads/                ← College logos etc.
```

---

## 🚀 Hostinger Deployment Steps

### Step 1: Create MySQL Database
1. Login to Hostinger hPanel
2. Go to **Databases → MySQL Databases**
3. Create new database: `qpds_vtu`
4. Create a DB user and assign ALL privileges
5. Note the: database name, username, password

### Step 2: Import SQL Schema
1. Go to **phpMyAdmin** in hPanel
2. Select your database
3. Click **Import** tab
4. Upload `sql/qpds_schema.sql`
5. Click **Go**

### Step 3: Configure Application
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');        // Usually localhost on Hostinger
define('DB_NAME', 'yourusername_qpds_vtu');  // Hostinger prefixes DB names!
define('DB_USER', 'yourusername_dbuser');
define('DB_PASS', 'your_db_password');
define('SITE_URL', 'https://yourdomain.com/qpds');
```
> ⚠️ On Hostinger shared hosting, database names are prefixed with your cPanel username.
> Example: if username is `u123456789`, DB name is `u123456789_qpds_vtu`

### Step 4: Upload Files
**Option A – File Manager:**
1. Go to hPanel → File Manager
2. Navigate to `public_html/`
3. Create folder `qpds/`
4. Upload all files

**Option B – FTP (FileZilla):**
- Host: `ftp.yourdomain.com`
- Use your Hostinger FTP credentials
- Upload to `public_html/qpds/`

### Step 5: Set File Permissions
Via File Manager or SSH:
```bash
chmod 755 public_html/qpds/
chmod 644 public_html/qpds/*.php
chmod 777 public_html/qpds/uploads/
```

### Step 6: First Login
URL: `https://yourdomain.com/qpds/`

Default admin credentials:
- **Username:** `admin`
- **Password:** `Admin@123`

> ⚠️ **CHANGE THE PASSWORD IMMEDIATELY after first login!**

---

## 🔐 Role Hierarchy & Permissions

| Role       | Can Add Users | Department Scope | Generate Paper | Print Paper |
|------------|---------------|-----------------|----------------|-------------|
| Admin      | All roles     | All depts        | ✅             | ✅          |
| Principal  | HOD + Staff   | All depts        | ✅             | ✅          |
| HOD        | Staff only    | Own dept ONLY    | ❌             | ❌          |
| Staff      | None          | Own dept only    | ❌             | ❌          |

**Key rule:** CS HOD can ONLY add CS staff. EC HOD can ONLY add EC staff.

---

## 📋 VTU Paper Rules (Pre-configured)

### CIE (30 Marks, 90 min)
- Part A: 5 questions × 2 marks = 10 (all compulsory)
- Part B: 3 out of 5 questions × 5 marks = 15 marks
- Covers Units 1–3 (or as configured)

### SEE (100 Marks, 3 hours)
- Module 1: Q1 OR Q2 (20 marks)
- Module 2: Q3 OR Q4 (20 marks)
- Module 3: Q5 OR Q6 (20 marks)
- Module 4: Q7 OR Q8 (20 marks)
- Module 5: Q9 OR Q10 (20 marks)

---

## 📝 Workflow

1. **Admin** sets up college info, departments, subjects, CO/PO
2. **Staff** adds questions (tagged with CO, Bloom's level, marks)
3. **HOD** approves questions from their department staff
4. **Principal/Admin** generates paper → system auto-selects questions with CO diversity
5. **Shuffling** creates Set A, B, C, D variants automatically
6. **Print** generates VTU-format paper with CO-PO table, watermark, metadata

---

## 🛠️ Troubleshooting

**Login not working?**
- Check DB credentials in `config.php`
- Ensure `qpds_vtu` DB was created and schema imported
- Check PHP version ≥ 7.4 (Hostinger supports PHP 8.x)

**Sessions not persisting?**
- Ensure `session.save_path` is writable
- Check `.htaccess` is uploaded correctly

**Papers not generating?**
- Need minimum questions in bank per question type
- CIE needs: 5+ two-mark questions AND 5+ five-mark questions per subject
- SEE needs: 2+ ten-mark questions per module (unit)

---

## 📞 Default Credentials (Change after setup!)
| Role      | Username    | Password   |
|-----------|-------------|------------|
| Admin     | `admin`     | `Admin@123`|

Add remaining users via Admin Panel after login.
