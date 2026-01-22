# 🏗️ ICMIS - Integrated Construction Management Information System

<p align="center">
  <img src="assets/images/nobg_logo.png" alt="ICMIS Logo" width="150">
</p>

<p align="center">
  <strong>A comprehensive web-based construction management platform for end-to-end project lifecycle management</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.x">
  <img src="https://img.shields.io/badge/MySQL-8.x-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL 8.x">
  <img src="https://img.shields.io/badge/Tailwind%20CSS-v4-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white" alt="Tailwind CSS v4">
  <img src="https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat-square&logo=javascript&logoColor=black" alt="JavaScript">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="License">
</p>

---

## 📋 Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [System Architecture](#system-architecture)
- [Technology Stack](#technology-stack)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Module Documentation](#module-documentation)
- [Database Schema](#database-schema)
- [User Roles & Permissions](#user-roles--permissions)
- [Security Features](#security-features)
- [API Reference](#api-reference)
- [Contributing](#contributing)
- [License](#license)

---

## Overview

**ICMIS (Integrated Construction Management Information System)** is a full-featured, web-based construction project management platform designed to streamline and integrate all aspects of construction project execution. Built with modern PHP and a responsive frontend, ICMIS provides construction companies with a unified solution for managing projects, budgets, procurement, workforce, and reporting.

### What Makes ICMIS Different?

- **Unified Platform**: All construction management functions in one integrated system
- **Real-time Data**: Live project tracking with instant updates across modules
- **Phase-Based Architecture**: Organized around construction project phases for intuitive workflow
- **Comprehensive Audit Trail**: Complete activity logging for compliance and accountability
- **Role-Based Access**: Granular permissions for different user types
- **Modern UI/UX**: Clean, responsive interface built with Tailwind CSS

---

## Key Features

### 📊 Project Management
- **Project Lifecycle Tracking**: From planning to completion
- **Phase Management**: Break projects into logical construction phases
- **Task Assignment**: Assign and track tasks with priority levels
- **Gantt Chart Visualization**: Interactive timeline using Frappe Gantt
- **Status Monitoring**: Real-time project status with visual indicators

### 💰 Budget & Cost Control
- **Budget Proposals**: Create detailed budget requests with line items
- **Expense Tracking**: Record and categorize project expenses
- **Budget Alerts**: Visual indicators for budget utilization (Green/Yellow/Red)
- **Variance Analysis**: Compare approved budgets vs. actual spending
- **Payroll Integration**: Automatic labor expense generation from payroll

### 📦 Procurement & Inventory
- **Supplier Management**: Maintain approved vendor registry
- **Purchase Orders**: Create, approve, and track POs
- **Inventory Control**: Real-time stock levels with valuation
- **Stock In/Out**: Track receiving and issuance transactions
- **Low Stock Alerts**: Automatic notifications for reorder points

### 👷 Workforce Management
- **Employee Registry**: Comprehensive employee database
- **Job Title Management**: Predefined roles with default rates
- **Team/Group Formation**: Organize workers into crews
- **Attendance Tracking**: Daily time recording with status
- **Project Assignments**: Assign workers to projects/phases
- **Payroll Processing**: Calculate pay with Philippine government deductions

### 📈 Reporting & Analytics
- **Multi-Module Reports**: Cross-functional reporting capabilities
- **PDF Export**: Professional report generation with jsPDF
- **Report History**: Track previously generated reports
- **Print-Optimized Layouts**: Clean printing with proper formatting

### 🛡️ System Administration
- **Audit Trail**: Complete activity logging across all modules
- **User Management**: Role-based user accounts
- **System Monitoring**: Track user actions and system events

---

## System Architecture

### Directory Structure

```
/icmis
├── /config                     # Global Configuration
│   ├── config.php              # Base paths, DB credentials, session init
│   └── database.php            # Database connection handler
│
├── /core                       # Core System Logic
│   ├── Context.php             # Project/Phase context management
│   └── Logger.php              # Centralized audit trail system
│
├── /includes                   # Shared UI Components
│   ├── header.php              # Top navigation bar
│   ├── sidebar.php             # Side navigation menu
│   ├── toast.php               # Notification component
│   ├── head_assets.php         # Asset loader (CSS, JS, Fonts)
│   └── report_print_layout.php # Print-optimized wrapper
│
├── /assets                     # Static Public Assets
│   └── /images                 # Logos, favicons
│
├── /modules                    # Business Logic Subsystems
│   ├── /admin                  # System Administration
│   ├── /auth                   # Authentication & Authorization
│   ├── /project                # Project, Phase & Task Management
│   ├── /budget                 # Budget Proposals & Expense Tracking
│   ├── /procurement            # Suppliers, POs, Inventory & Stock
│   ├── /workforce              # Employees, Attendance & Payroll
│   └── /reports                # Centralized Multi-Module Reporting
│
├── index.php                   # Entry Point (Login Page)
├── dashboard.php               # Main System Dashboard
├── icmis_db.sql                # Complete Database Schema
└── README.md                   # This file
```

### Architecture Pattern

ICMIS follows a **modular MVC-inspired architecture**:

- **Config Layer**: Centralized configuration and database connectivity
- **Core Layer**: Shared business logic (Logger, Context)
- **Includes Layer**: Reusable UI components
- **Modules Layer**: Self-contained feature modules with their own:
  - View files (PHP pages)
  - API endpoints (AJAX handlers)
  - Components (Modal partials)
  - JavaScript files (Client-side logic)
  - CSS files (Module-specific styles)

---

## Technology Stack

| Layer | Technology | Purpose |
|-------|------------|---------|
| **Backend** | PHP 8.x | Server-side processing |
| **Database** | MySQL 8.x (InnoDB) | Data storage with FK support |
| **Frontend** | HTML5, Tailwind CSS v4 | Responsive UI framework |
| **JavaScript** | Vanilla JS (ES6+) | Client-side interactivity |
| **Charts** | Chart.js | Data visualization |
| **Gantt** | Frappe Gantt | Project timeline visualization |
| **Icons** | Lucide Icons, Font Awesome 6 | UI iconography |
| **PDF Generation** | jsPDF + AutoTable | Report export |
| **Server** | Apache/Nginx (Laragon) | Web server |

---

## System Requirements

### Server Requirements

| Requirement | Minimum | Recommended |
|-------------|---------|-------------|
| PHP | 8.0+ | 8.4+ |
| MySQL | 8.0+ | 8.4+ |
| Web Server | Apache 2.4+ or Nginx 1.18+ | Latest stable |
| Memory | 256MB | 512MB+ |
| Storage | 100MB (application) | 500MB+ (with data) |

### PHP Extensions Required

- `mysqli` - MySQL database connectivity
- `json` - JSON processing
- `session` - Session management
- `mbstring` - Multibyte string support
- `date` - Date/time functions

### Browser Compatibility

| Browser | Minimum Version |
|---------|-----------------|
| Chrome | 90+ |
| Firefox | 88+ |
| Safari | 14+ |
| Edge | 90+ |

---

## Installation

### Prerequisites

1. **Install Laragon** (recommended) or any LAMP/WAMP/MAMP stack
2. Ensure PHP 8.x and MySQL 8.x are installed
3. Have a web browser ready

### Step-by-Step Installation

#### 1. Clone or Download the Repository

```bash
# Clone the repository
git clone https://github.com/your-org/icmis.git

# Or download and extract to your web root
# For Laragon: C:\laragon\www\icmis
```

#### 2. Create the Database

```sql
-- Connect to MySQL and create the database
CREATE DATABASE icmis_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### 3. Import the Database Schema

```bash
# Using command line
mysql -u root -p icmis_db < icmis_db.sql

# Or use phpMyAdmin:
# 1. Select icmis_db database
# 2. Go to Import tab
# 3. Choose icmis_db.sql file
# 4. Click Go
```

#### 4. Configure the Application

Edit `config/config.php`:

```php
<?php
// File System Path
define('BASE_PATH', realpath(dirname(__FILE__) . '/../'));

// Web URL Path - Update if your URL is different
define('BASE_URL', 'http://localhost/icmis/');

// Database Credentials - Update with your credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // Add your MySQL password if set
define('DB_NAME', 'icmis_db');
```

#### 5. Set Proper Permissions (Linux/Mac)

```bash
# Set directory permissions
chmod -R 755 /path/to/icmis
chmod -R 777 /path/to/icmis/assets/uploads  # If using file uploads
```

#### 6. Access the Application

Open your browser and navigate to:
```
http://localhost/icmis/
```

### Default Login Credentials

After importing the database:

| Field | Value |
|-------|-------|
| Email | `john.doe@icmis.com` |
| Password | `password` |
| Role | Admin |

> ⚠️ **Security Notice**: Change the default password immediately after first login!

---

## Configuration

### Environment Configuration

The main configuration file is `config/config.php`:

```php
<?php
// 1. File System Path (for PHP includes)
define('BASE_PATH', realpath(dirname(__FILE__) . '/../'));

// 2. Web URL Path (for HTML assets)
define('BASE_URL', 'http://localhost/icmis/');

// 3. Database Credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'icmis_db');

// 4. Timezone
date_default_timezone_set('Asia/Manila');

// 5. Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Production Configuration

For production environments, update the following:

```php
<?php
// Disable error display
error_reporting(0);
ini_set('display_errors', 0);

// Use HTTPS
define('BASE_URL', 'https://your-domain.com/icmis/');

// Secure session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
```

---

## Module Documentation

### Authentication Module (`/modules/auth/`)

Handles user authentication, registration, and session management.

**Key Files:**
- `api/login_process.php` - Processes login requests
- `api/logout.php` - Handles logout and session destruction
- `api/signup_process.php` - Processes user registration

**Features:**
- Secure password hashing with bcrypt
- Session-based authentication
- Automatic audit logging of login/logout events
- Email uniqueness validation

---

### Project Module (`/modules/project/`)

Core project management functionality.

**Key Files:**
- `projects.php` - Project CRUD interface
- `phases.php` - Phase management
- `tasks.php` - Task assignment and tracking
- `gantt.php` - Gantt chart visualization

**Features:**
- Auto-generated project codes (PRJ-YYYY-XXX)
- Project status tracking (Planning, In Progress, Active, On Hold, Completed, Cancelled)
- Cascade delete with warning for related data
- Interactive Gantt chart with day/week/month views

---

### Budget Module (`/modules/budget/`)

Financial planning and expense tracking.

**Key Files:**
- `dashboard.php` - Budget overview with phase cards
- `proposals.php` - Budget proposal management
- `expenses.php` - Expense recording
- `payroll_expenses.php` - Payroll-linked expenses

**Features:**
- Budget proposals with line items (Material, Labor, Equipment)
- Auto-generated proposal codes (BP-YYYY-XXX)
- Approval workflow (Draft → Pending → Approved/Rejected)
- Budget utilization alerts with color coding
- Phase-based expense breakdown

---

### Procurement Module (`/modules/procurement/`)

Supply chain and inventory management.

**Key Files:**
- `suppliers.php` - Supplier registry
- `orders.php` - Purchase order management
- `inventory.php` - Inventory masterlist
- `stock_in.php` - Stock receiving
- `stock_out.php` - Stock issuance

**Features:**
- Supplier management with contact details
- PO creation with line items
- Auto-generated PO references (PO-YYYY-XXX)
- PO approval workflow
- Automatic inventory updates on stock in/out
- Integration with budget module (expense creation from POs)

---

### Workforce Module (`/modules/workforce/`)

Human resource management for construction workers.

**Key Files:**
- `dashboard.php` - Workforce overview with charts
- `employees.php` - Employee registry
- `employee_groups.php` - Team management
- `attendance.php` - Daily attendance tracking
- `assignments.php` - Project assignments
- `payroll.php` - Payroll processing

**Features:**
- Employee profiles with personal and employment details
- Auto-generated employee codes (EMP-YYYY-XXX)
- Job title management with default rates
- Team/crew formation and management
- Daily attendance recording
- Individual and group assignments
- Payroll calculation with Philippine government deductions:
  - SSS (4.5%, capped at ₱1,350)
  - PhilHealth (2.5%)
  - Pag-IBIG (2%, capped at ₱200)
- PDF payroll and payslip export

---

### Reports Module (`/modules/reports/`)

Centralized reporting hub.

**Key Files:**
- `index.php` - Report center with templates
- `print_report.php` - Print-optimized report renderer

**Features:**
- Multi-module report generation
- Report categories (Budget, Procurement, Project, Workforce)
- Report history tracking
- PDF export with jsPDF
- Print-optimized layouts

---

### Admin Module (`/modules/admin/`)

System administration tools.

**Key Files:**
- `audit_logs.php` - Audit trail viewer

**Features:**
- Filterable audit log display
- Filter by module, action type, date range
- Search functionality
- AJAX-powered pagination
- Detailed log entry viewing

---

## Database Schema

### Core Tables

| Table | Purpose |
|-------|---------|
| `icmis_users` | User accounts and authentication |
| `icmis_projects` | Project master records |
| `icmis_project_phases` | Project phase definitions |
| `icmis_tasks` | Task assignments |
| `icmis_audit_logs` | System audit trail |
| `icmis_generated_reports` | Report generation history |

### Budget Tables

| Table | Purpose |
|-------|---------|
| `budget_proposals` | Budget request headers |
| `budget_line_items` | Budget proposal details |
| `budget_expenses` | Expense records |
| `budget_generated_reports` | Budget report history |

### Procurement Tables

| Table | Purpose |
|-------|---------|
| `procurement_suppliers` | Vendor registry |
| `procurement_purchase_orders` | PO headers |
| `procurement_purchase_order_items` | PO line items |
| `procurement_inventory` | Stock levels |
| `procurement_stock_in` | Receiving records |
| `procurement_stock_out` | Issuance records |

### Workforce Tables

| Table | Purpose |
|-------|---------|
| `workforce_employees` | Employee master records |
| `workforce_job_titles` | Position definitions with rates |
| `workforce_employee_groups` | Team definitions |
| `workforce_group_memberships` | Team member assignments |
| `workforce_assignments` | Project assignments |
| `workforce_attendance` | Daily attendance records |
| `workforce_payroll_periods` | Pay period definitions |
| `workforce_payroll` | Payroll calculation records |
| `workforce_generated_reports` | Workforce report history |

### Entity Relationship Overview

```
icmis_projects (Central Hub)
    │
    ├── icmis_project_phases
    │       └── icmis_tasks
    │
    ├── budget_proposals
    │       └── budget_line_items
    │
    ├── budget_expenses
    │
    ├── procurement_purchase_orders
    │       ├── procurement_purchase_order_items
    │       └── procurement_stock_in
    │
    ├── procurement_inventory
    │       └── procurement_stock_out
    │
    ├── workforce_assignments
    │
    └── workforce_attendance
```

### Foreign Key Cascade Rules

All foreign keys are configured with appropriate `ON DELETE` actions:

| Relationship | Cascade Action |
|--------------|----------------|
| Project → Phases | CASCADE |
| Project → Tasks | CASCADE |
| Project → Budget Proposals | CASCADE |
| Project → Expenses | CASCADE |
| Project → Purchase Orders | CASCADE |
| Project → Assignments | CASCADE |
| Phase → Tasks | SET NULL |
| Proposal → Line Items | CASCADE |
| PO → PO Items | CASCADE |
| PO → Stock In | CASCADE |

---

## User Roles & Permissions

### Available Roles

| Role | Description | Access Level |
|------|-------------|--------------|
| `Admin` | System administrator | Full access to all modules including audit logs |
| `Manager` | Project manager | Project oversight, approvals, full module access |
| `Staff` | General staff | Basic operational access to assigned modules |
| `Budget_Officer` | Budget specialist | Full access to Budget module |
| `Procurement_Officer` | Procurement specialist | Full access to Procurement module |

### Permission Matrix

| Module | Admin | Manager | Staff | Budget Officer | Procurement Officer |
|--------|-------|---------|-------|----------------|---------------------|
| Dashboard | ✅ | ✅ | ✅ | ✅ | ✅ |
| Projects | ✅ | ✅ | View Only | View Only | View Only |
| Budget | ✅ | ✅ | View Only | ✅ | View Only |
| Procurement | ✅ | ✅ | View Only | View Only | ✅ |
| Workforce | ✅ | ✅ | Limited | View Only | View Only |
| Reports | ✅ | ✅ | Limited | ✅ | ✅ |
| Audit Logs | ✅ | ❌ | ❌ | ❌ | ❌ |

---

## Security Features

### Authentication Security

- **Password Hashing**: bcrypt with automatic salt
- **Session Management**: Secure session handling with status checks
- **Session Destruction**: Complete cleanup on logout

### SQL Injection Prevention

```php
// Prepared statements with parameterized queries
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
```

### Input Sanitization

```php
// Output encoding to prevent XSS
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```

### Access Control

```php
// Session check on every protected page
if (!isset($_SESSION['user_id'])) { 
    header("Location: " . BASE_URL . "index.php"); 
    exit(); 
}
```

### Audit Trail

All sensitive operations are logged via the centralized Logger class:

```php
Logger::log('CREATE', 'Project', 'Created new project: Project Name', $project_id);
```

Logged information includes:
- User ID and name
- Action type (CREATE, UPDATE, DELETE, LOGIN, LOGOUT, VIEW, EXPORT, APPROVE, REJECT)
- Module name
- Action details
- Record ID
- IP address
- User agent
- Timestamp

---

## API Reference

### Authentication API

#### Login
- **Endpoint**: `modules/auth/api/login_process.php`
- **Method**: POST
- **Parameters**: `email`, `password`
- **Response**: Redirect to dashboard or login page with error

#### Logout
- **Endpoint**: `modules/auth/api/logout.php`
- **Method**: GET
- **Response**: Redirect to login page

### Project API

#### List Projects
- **Endpoint**: `modules/project/api/projects.php`
- **Method**: GET
- **Response**: JSON array of projects

#### Create/Update Project
- **Endpoint**: `modules/project/api/projects.php`
- **Method**: POST
- **Parameters**: `project_name`, `description`, `location`, `start_date`, `end_date`, `total_budget`, `status`

#### Delete Project
- **Endpoint**: `modules/project/api/projects.php?action=delete&id={project_id}`
- **Method**: GET/POST

### Procurement API

#### Fetch Suppliers (Paginated)
- **Endpoint**: `modules/procurement/php/fetch_suppliers.php`
- **Method**: GET
- **Parameters**: `page`, `per_page`
- **Response**: JSON with items, total, page, per_page

#### Save Supplier
- **Endpoint**: `modules/procurement/php/save_supplier.php`
- **Method**: POST
- **Parameters**: `supplier_name`, `contact_person`, `contact_number`, `email`, `address`, `status`

### Workforce API

#### Fetch Employees
- **Endpoint**: `modules/workforce/api/employees.php`
- **Method**: GET
- **Response**: JSON array of employees

#### Save Attendance
- **Endpoint**: `modules/workforce/api/attendance.php`
- **Method**: POST
- **Parameters**: Array of attendance records

---

## Troubleshooting

### Common Issues

#### Database Connection Error
```
Connection failed: Access denied for user 'root'@'localhost'
```
**Solution**: Check database credentials in `config/config.php`

#### Page Not Found (404)
**Solution**: Verify `BASE_URL` matches your server URL in `config/config.php`

#### Session Issues
**Solution**: Ensure PHP sessions are properly configured and the session directory is writable

#### Foreign Key Constraint Errors
```
Cannot delete or update a parent row: a foreign key constraint fails
```
**Solution**: The database uses cascade deletes. This error indicates a constraint issue. Check the database schema.

### Debug Mode

Enable debug mode in `config/config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

> ⚠️ **Warning**: Disable debug mode in production!

---

## Contributing

### Development Setup

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Make your changes
4. Test thoroughly
5. Commit with clear messages: `git commit -m "Add: feature description"`
6. Push to your fork: `git push origin feature/your-feature`
7. Create a Pull Request

### Coding Standards

- Follow PSR-12 for PHP code style
- Use meaningful variable and function names
- Add comments for complex logic
- Maintain the existing directory structure
- Include audit logging for new CRUD operations

### Reporting Issues

When reporting issues, please include:
- PHP version
- MySQL version
- Browser and version
- Steps to reproduce
- Expected vs actual behavior
- Error messages (if any)

---

## Changelog

### Version 1.0 (January 2026)
- Initial release
- Complete project management module
- Budget proposal and expense tracking
- Procurement and inventory management
- Workforce management with payroll
- Centralized reporting system
- Comprehensive audit trail
- Role-based access control

---

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## Support

For support and questions:
- 📧 Email: support@icmis.com
- 📖 Documentation: [ICMIS_Technical_User_Manual.md](ICMIS_Technical_User_Manual.md)
- 🐛 Issues: GitHub Issues

---

## Acknowledgments

- **Tailwind CSS** - Utility-first CSS framework
- **Frappe Gantt** - Interactive Gantt chart library
- **Chart.js** - JavaScript charting library
- **jsPDF** - PDF generation library
- **Lucide Icons** - Beautiful open-source icons
- **Font Awesome** - Icon library

---

<p align="center">
  <strong>Built with ❤️ for the Construction Industry</strong>
</p>

<p align="center">
  <sub>ICMIS - Integrated Construction Management Information System © 2026</sub>
</p>
