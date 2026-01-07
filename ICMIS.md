
---

# 🏗️ ICMIS - Integrated Construction Management Information System

## Complete Architecture & Functionality Documentation

---

## 📁 System Overview

**ICMIS** is a comprehensive **web-based construction management system** built with **PHP** and **MySQL**, designed specifically for managing construction projects end-to-end. The system follows a modular architecture with clear separation of concerns, featuring a modern UI built with **Tailwind CSS**.

---

## 🏛️ Core Architecture

### Directory Structure Philosophy

```
/icmis
├── /config          → Global Configuration Layer
├── /core            → System Core Logic (The "Brain")
├── /includes        → Shared UI Components
├── /assets          → Static Public Assets
├── /modules         → Business Logic Subsystems
├── index.php        → Entry Point (Login/Router)
└── dashboard.php    → Main System Dashboard
```

---

## ⚙️ Configuration Layer (config)

### config.php
The centralized configuration hub that establishes:

| Constant | Purpose |
|----------|---------|
| `BASE_PATH` | File system path for PHP includes (`require_once`) |
| `BASE_URL` | Web URL path for HTML assets (CSS, JS, links) |
| `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` | Database credentials |
| `session_start()` | Global session initialization |
| `error_reporting()` | Debug settings for development |

### database.php
Establishes a **MySQLi** connection object (`$conn`) that is included across all modules for database operations. Features:
- Prevents direct access without `BASE_PATH` definition
- Handles connection errors gracefully
- Provides a single source of truth for DB connectivity

---

## 🧠 Core System Logic (core)

### Context.php
Handles **project/phase context management** across the application. This is crucial for:
- Maintaining user's selected project across different modules
- Session-based state persistence
- Cross-module context awareness

---

## 🎨 Shared UI Components (includes)

### sidebar.php
The **navigation backbone** of the application featuring:
- Company branding and logo
- Module navigation links with icons
- Collapsible sub-menus for each module
- Active state highlighting based on current page
- Responsive design with smooth transitions

### header.php
The **top navigation bar** containing:
- Dynamic page title and section breadcrumbs
- Project context selector (dropdown)
- User profile information
- Global notification system integration
- Quick action buttons

### toast.php
A **notification component** for displaying:
- Success messages (green)
- Warning alerts (yellow)
- Error notifications (red)
- Auto-dismiss functionality with animations

### head_assets.php / head_assetsv2.php
Centralized asset loading for:
- Tailwind CSS framework
- Font Awesome icons
- Lucide icons
- Google Fonts (Inter, Arimo)
- Favicon configuration
- Common meta tags

---

## 🔐 Authentication Module (auth)

### Purpose
Handles all user authentication, authorization, and session management for the entire system.

### Key Files

#### login_process.php
**The Authentication Engine:**

1. **Input Validation**: Validates email and password fields
2. **Database Verification**: Queries `icmis_users` table for matching credentials
3. **Password Verification**: Uses `password_verify()` for bcrypt hash comparison
4. **Session Establishment**: Creates session variables upon successful login:
   - `$_SESSION['user_id']` - Unique user identifier
   - `$_SESSION['user_name']` - Display name
   - `$_SESSION['user_role']` - Permission level (Admin, Manager, Staff, Budget_Officer, Procurement_Officer)
5. **Error Handling**: Redirects with error messages for failed attempts

#### logout.php
Securely destroys user sessions and redirects to login page.

#### signup_process.php
Handles new user registration with:
- Email uniqueness validation
- Password hashing with bcrypt
- Role assignment
- Account creation confirmation

### User Roles & Permissions
| Role | Access Level |
|------|-------------|
| `Admin` | Full system access |
| `Manager` | Project oversight, approvals |
| `Staff` | Basic operations |
| `Budget_Officer` | Budget module full access |
| `Procurement_Officer` | Procurement module full access |

---

## 📊 Project Management Module (project)

### Purpose
The **foundation module** that manages all construction projects, their phases, and individual tasks. This is the central hub that other modules reference.

### Database Tables
- `icmis_projects` - Master project records
- `icmis_project_phases` - Project phase definitions
- `icmis_tasks` - Granular task assignments

---

### projects.php - Project Management

**Functionality:**

1. **Project Dashboard Overview**
   - Displays summary statistics (Total, Active, Completed, Upcoming projects)
   - Shows total budget allocation across all projects

2. **Project CRUD Operations**
   - **Create**: Add new construction projects with:
     - Project code (auto-generated: PRJ-YYYY-XXX)
     - Project name and description
     - Location
     - Start/End dates
     - Total budget allocation
     - Project manager assignment
   - **Read**: List all projects with filtering/search
   - **Update**: Edit project details
   - **Delete**: Remove projects (with dependency checks)

3. **Project Status Tracking**
   - Planning, In Progress, Active, On Hold, Completed, Cancelled
   - Completion percentage tracking
   - Visual status indicators with color coding

4. **Project Manager Assignment**
   - Links to `workforce_employees` for manager selection
   - Displays manager name in project listings

---

### phases.php - Phase Management

**Functionality:**

1. **Phase Definition**
   - Break projects into logical construction phases:
     - Phase 1: Mobilization (site prep, fencing)
     - Phase 2: Structural (foundation, framing)
     - Phase 3: MEPFS (Mechanical, Electrical, Plumbing, Fire, Sanitary)
     - Phase 4: Finishing (interior, exterior completion)

2. **Phase Tracking**
   - Start and end dates for each phase
   - Duration calculation (in days)
   - Status management (Not Started, In Progress, Completed)

3. **Phase Statistics**
   - Total phases across projects
   - Active phases count
   - Completed phases count
   - Upcoming phases

4. **Project Filter**
   - Filter phases by specific project
   - Cross-project phase overview

---

### tasks.php - Task Management

**Functionality:**

1. **Task Creation & Assignment**
   - Task name and description
   - Assignment to specific employees
   - Phase association
   - Priority levels (Low, Medium, High, Urgent)
   - Start and due dates

2. **Task Status Tracking**
   - Not Started, In Progress, Completed, On Hold
   - Overdue detection and highlighting

3. **Task Dashboard**
   - Total tasks overview
   - In-progress tasks count
   - Overdue tasks alert

4. **Filtering Capabilities**
   - Filter by project
   - Filter by phase
   - Filter by assignee
   - Search by task name

---

## 💰 Budget & Cost Control Module (budget)

### Purpose
Manages all financial aspects of construction projects including budget proposals, expense tracking, variance analysis, and financial reporting.

### Database Tables
- `budget_proposals` - Budget request documents
- `budget_line_items` - Itemized budget breakdown
- `budget_expenses` - Actual expenditure records
- `budget_generated_reports` - Report generation history

---

### dashboard.php - Budget Dashboard

**Functionality:**

1. **Financial Overview**
   - Total approved budget (from proposals)
   - Actual spending (from expenses)
   - Remaining budget calculation
   - Budget utilization percentage

2. **Intelligent Budget Alerts**
   - **Green (Good)**: Under 70% utilization
   - **Yellow (Warning)**: 70-85% utilization
   - **Red (High)**: Over 85% utilization

3. **Phase-Based Budget Visualization**
   - Per-phase budget allocation
   - Per-phase spending breakdown
   - Phase utilization percentages
   - Date ranges from master phase table

4. **Chart Visualization**
   - Monthly spending trends
   - Budget vs. Actual comparison
   - Category distribution (Materials, Labor, Equipment)

---

### proposals.php - Budget Proposals

**Functionality:**

1. **Proposal Creation**
   - Proposal code (auto-generated: BP-XXX)
   - Title and description
   - Project and phase association
   - Total amount calculation

2. **Line Item Management**
   - Category classification:
     - **MATERIAL**: Physical construction materials
     - **LABOR**: Worker wages and labor costs
     - **EQUIPMENT**: Machinery and equipment rental/purchase
   - Item name, quantity, unit cost, duration
   - Automatic subtotal calculation

3. **Approval Workflow**
   - Status progression: DRAFT → PENDING → APPROVED/REJECTED
   - Approval tracking with user attribution

4. **Proposal Actions**
   - View detailed breakdown
   - Edit proposals (draft/pending only)
   - Download as PDF
   - Delete proposals

---

### expenses.php - Expense Tracker

**Functionality:**

1. **Expense Recording**
   - Project and phase association
   - Category (Materials, Labor, Equipment)
   - Supplier linkage
   - Description and amount
   - Expense date
   - Status (Pending, Approved, Rejected)

2. **Phase-Based Expense View**
   - Expense breakdown by construction phase
   - Phase budget vs. actual spending
   - Real-time utilization calculations

3. **Expense Management**
   - Add new expenses
   - Edit existing records
   - Export to PDF
   - Delete with confirmation

4. **Budget Alert System**
   - Visual indicators when approaching budget limits
   - Color-coded status based on utilization

---

### reports.php - Financial Reports

**Functionality:**

1. **Report Templates**
   - **Budget Summary Report**: Overview of all budget allocations
   - **Expense Report**: Detailed expense listings
   - **Variance Report**: Budget vs. Actual analysis
   - **Phase Budget Report**: Per-phase financial status

2. **Report Generation**
   - Project-specific filtering
   - Date range selection
   - Real-time data compilation

3. **Export Options**
   - Print-optimized layouts
   - PDF generation with jsPDF
   - Professional formatting with headers/footers

4. **Report History**
   - Saved generated reports
   - Re-download capability
   - Audit trail for compliance

---

## 📦 Procurement & Inventory Module (procurement)

### Purpose
Manages the complete procurement lifecycle from supplier management to purchase orders, inventory tracking, and stock movements.

### Database Tables
- `procurement_suppliers` - Vendor/supplier database
- `procurement_purchase_orders` - PO headers
- `procurement_purchase_order_items` - PO line items
- `procurement_inventory` - Stock levels
- `procurement_stock_in` - Receiving records
- `procurement_stock_out` - Issuance records

---

### suppliers.php - Supplier Management

**Functionality:**

1. **Supplier Registry**
   - Maintain approved vendor list
   - Supplier information:
     - Company name
     - Contact person
     - Phone and email
     - Physical address
     - Active/Inactive status

2. **Supplier CRUD**
   - Add new suppliers with modal form
   - Edit supplier details
   - Deactivate/reactivate suppliers
   - Delete suppliers (with PO check)

3. **Supplier Validation**
   - Prevent deletion if associated with POs
   - Status-based filtering

---

### orders.php - Purchase Orders

**Functionality:**

1. **Purchase Order Creation**
   - Auto-generated PO reference (PO-YYYY-XXX)
   - Project and phase association
   - Supplier selection
   - Order title and date
   - Line items from inventory

2. **PO Line Items**
   - Item selection from inventory masterlist
   - Quantity and unit cost specification
   - Automatic total calculation

3. **PO Workflow**
   - Status progression: PENDING → APPROVED → COMPLETED/REJECTED
   - KPI dashboard (Total, Pending, Approved, Completed)

4. **PO Management**
   - View detailed order breakdown
   - Edit orders (pending only)
   - Delete with confirmation modal

---

### inventory.php - Inventory Masterlist

**Functionality:**

1. **Inventory Dashboard**
   - Total items count
   - Total inventory valuation (₱)
   - Low stock alerts

2. **Item Management**
   - Item name and description
   - Category classification
   - Quantity and unit of measure
   - Unit cost
   - Last updated timestamp

3. **Stock Level Monitoring**
   - Real-time quantity tracking
   - Low stock threshold warnings
   - Color-coded status indicators

4. **Print Functionality**
   - Print-optimized report layout
   - Project-specific inventory views

---

### stock_in.php - Stock Receiving

**Functionality:**

1. **PO-Based Receiving**
   - Select from approved purchase orders
   - Receive items against PO quantities
   - Partial receiving support

2. **Receipt Recording**
   - Date received
   - Quantity received
   - Link to original PO
   - Automatic inventory update

3. **Empty State Handling**
   - Guidance when no approved POs exist
   - Clear instructions for workflow

---

### stock_out.php - Stock Issuance

**Functionality:**

1. **Stock Issuance**
   - Issue items to personnel/sites
   - Project association
   - Issued-to tracking
   - Date of issuance

2. **Inventory Deduction**
   - Automatic stock level reduction
   - Prevents over-issuance
   - Audit trail maintenance

---

## 👷 Labor & Workforce Module (workforce)

### Purpose
Comprehensive human resource management for construction workforce including employee management, attendance tracking, payroll processing, and workforce assignments.

### Database Tables
- `workforce_employees` - Employee master records
- `workforce_job_titles` - Position definitions with rates
- `workforce_employee_groups` - Team definitions
- `workforce_group_memberships` - Team assignments
- `workforce_employee_skills` - Skill mapping
- `workforce_skills` - Skill masterlist
- `workforce_assignments` - Project assignments
- `workforce_attendance` - Daily attendance
- `workforce_leave_types` - Leave categories
- `workforce_leave_requests` - Leave applications
- `workforce_leave_balances` - Leave credits
- `workforce_payroll_periods` - Pay periods
- `workforce_payroll` - Payroll records
- `workforce_payroll_config` - Tax and deduction rates
- `workforce_notifications` - System notifications

---

### employees.php - Employee Management

**Functionality:**

1. **Employee Registry**
   - Employee code (auto-generated: EMP-YYYY-XXX)
   - Personal information (first name, last name)
   - Contact details (email, phone)
   - Job title assignment
   - Status tracking (Active, Inactive, Terminated)
   - Hire date

2. **Statistics Dashboard**
   - Total employees
   - Active employees
   - Inactive employees
   - New hires this month

3. **Employee CRUD**
   - Add employees with modal form
   - Edit employee details
   - Status changes
   - Job title assignment

4. **Employee Groups Tab**
   - Team formation (e.g., "Team Alpha - Structural")
   - Group leader assignment
   - Member management
   - Role within group

5. **Search & Filter**
   - Search by name, role, or ID
   - Filter by status
   - Result count display

---

### attendance.php - Attendance Management

**Functionality:**

1. **Daily Attendance Recording**
   - Date selection (defaults to today)
   - Time in/Time out entry
   - Status selection:
     - Present
     - Absent
     - Late
     - On Leave

2. **Attendance Statistics**
   - Total employees
   - Present count
   - Absent count
   - Late count

3. **Attendance Table**
   - Employee listing with project assignment
   - Editable time fields
   - Status dropdowns
   - Remarks field

4. **Bulk Operations**
   - Save all attendance at once
   - Date-based filtering

---

### assignments.php - Project Assignments

**Functionality:**

1. **Assignment Creation**
   - Employee to project mapping
   - Phase-specific assignments
   - Role definition for assignment
   - Task descriptions
   - Start and end dates

2. **Assignment Status**
   - Active, Completed, Cancelled
   - Visual status indicators

3. **Assignment Management**
   - View all assignments
   - Edit assignments
   - Reassign employees
   - Track assignment history

---

### payroll.php - Payroll Processing

**Functionality:**

1. **Period-Based Payroll**
   - Monthly period selection
   - Project-specific payroll

2. **Payroll Calculation**
   - Attendance-based computation:
     - Present days × Daily rate
     - Half-day considerations
     - Overtime calculations
   - Gross pay computation
   - Deductions (tax, contributions)
   - Net pay calculation

3. **Payroll Summary**
   - Total employees
   - Total gross pay
   - Total deductions
   - Total net pay

4. **Export Options**
   - Print payroll report
   - Export to PDF
   - Professional formatting

---

### reports.php - Workforce Reports

**Functionality:**

1. **Report Templates**
   - Employee Roster Report
   - Attendance Summary Report
   - Assignment Report
   - Payroll Summary Report

2. **Statistics Display**
   - Total employees assigned
   - Active employees
   - Total assignments
   - Attendance rate

3. **Report Generation**
   - Project-specific filtering
   - PDF export capability
   - Print optimization

---

## 📈 Centralized Reports Module (reports)

### Purpose
Provides a unified reporting center that aggregates data from all modules (Budget, Procurement, Project, Workforce) for comprehensive analysis.

### index.php - Reports Center

**Functionality:**

1. **Multi-Module Report Hub**
   - Budget Reports
   - Procurement Reports
   - Project Reports
   - Workforce Reports

2. **Project Context**
   - Select specific project or "All Projects"
   - Project details display (status, location, budget, completion)

3. **Report Categories**
   - Category tabs for organization
   - Template cards for each report type

4. **Recent Reports**
   - History of generated reports
   - Quick re-download
   - Audit compliance

5. **Print & Export**
   - Print-optimized CSS
   - PDF generation with jsPDF
   - Professional headers and footers

---

## 🔄 Cross-Module Integration

### Project Context System
Every module respects the **selected project context** stored in session:

```php
// Pattern used across modules
$selected_project_id = getProjectContext($conn);
// or
$_SESSION['current_project_id']
```

This ensures:
- Consistent data filtering across modules
- Seamless navigation without losing context
- User-specific view customization

### Data Relationships

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
    │       └── procurement_purchase_order_items
    │
    ├── procurement_stock_out
    │
    └── workforce_assignments
            └── workforce_attendance
```

### Supplier Integration
- `budget_expenses` can reference `procurement_suppliers`
- `procurement_purchase_orders` link to `procurement_suppliers`
- Centralized vendor management

### Employee-Task Linkage
- `icmis_tasks` → `assigned_to_employee_id` → `workforce_employees`
- `workforce_assignments` → Phase-specific role definitions
- `workforce_attendance` → Project-based tracking

---

## 🛡️ Security Implementation

### Authentication Checks
Every protected page includes:
```php
if (!isset($_SESSION['user_id'])) { 
    header("Location: " . BASE_URL . "index.php"); 
    exit(); 
}
```

### SQL Injection Prevention
- Prepared statements with parameterized queries
- Input sanitization with `htmlspecialchars()`
- Type casting for numeric values

### Session Management
- Centralized session initialization in config.php
- Session status checks before operations
- Proper session destruction on logout

---

## 🎨 UI/UX Framework

### Design System
- **Primary Color**: `#e9922c` (Orange - construction branding)
- **Typography**: Inter font family
- **Layout**: Sidebar + Header + Content area
- **Cards**: Rounded corners, subtle shadows
- **Tables**: Striped rows, hover effects
- **Modals**: Slide-in animations, backdrop blur

### Responsive Design
- Fixed sidebar (256px / ml-56)
- Content area adapts to viewport
- Grid layouts for stat cards
- Print-specific styles

---

## 📊 Technology Stack Summary

| Layer | Technology |
|-------|------------|
| **Backend** | PHP 8.x |
| **Database** | MySQL 8.x |
| **Frontend** | HTML5, Tailwind CSS 4 |
| **JavaScript** | Vanilla JS, Lucide Icons |
| **PDF Generation** | jsPDF + AutoTable |
| **Server** | Laragon (Apache) |

---