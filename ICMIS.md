---

# 🏗️ ICMIS - Integrated Construction Management Information System

## Complete Architecture & Functionality Documentation

---

## 📁 System Overview

**ICMIS** is a comprehensive **web-based construction management system** built with **PHP** and **MySQL**, designed specifically for managing construction projects end-to-end. The system follows a modular architecture with clear separation of concerns, featuring a modern UI built with **Tailwind CSS v4**.

---

## 🏛️ Core Architecture

### Directory Structure Philosophy

```
/icmis
├── /config          → Global Configuration Layer
├── /core            → System Core Logic (Logger, Context)
├── /includes        → Shared UI Components
├── /assets          → Static Public Assets
├── /modules         → Business Logic Subsystems
│   ├── /admin       → System Administration & Audit Logs
│   ├── /auth        → Authentication & Authorization
│   ├── /project     → Project, Phase & Task Management
│   ├── /budget      → Budget Proposals & Expense Tracking
│   ├── /procurement → Suppliers, POs, Inventory & Stock
│   ├── /workforce   → Employees, Attendance & Payroll
│   └── /reports     → Centralized Multi-Module Reporting
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
Handles **project/phase context management** across the application:
- Maintains user's selected project across different modules
- Session-based state persistence
- Cross-module context awareness
- Helper functions: `getProjectContext()`, `setProjectContext()`

### Logger.php
**Centralized Audit Trail System** for recording user actions across all modules:

```php
// Usage Example
require_once BASE_PATH . '/core/Logger.php';
Logger::log('CREATE', 'Project', 'Created new project: Project Alpha', $project_id);
```

**Features:**
- Static helper class (no instantiation needed)
- Automatic user detection from session
- IP address and user agent logging
- Module-based categorization
- PHP 8.4+ compatibility (avoids deprecated `mysqli::ping()`)

**Supported Actions:**
| Action | Description |
|--------|-------------|
| `CREATE` | New record creation |
| `UPDATE` | Record modification |
| `DELETE` | Record removal |
| `LOGIN` | User authentication |
| `LOGOUT` | Session termination |
| `VIEW` | Record access |
| `EXPORT` | Report generation |
| `APPROVE` | Workflow approval |
| `REJECT` | Workflow rejection |

---

## 🎨 Shared UI Components (includes)

### sidebar.php
The **navigation backbone** of the application featuring:
- Company branding and logo
- Module navigation links with Lucide icons
- Collapsible sub-menus for each module
- Active state highlighting based on current page
- Responsive design with smooth transitions

### header.php
The **top navigation bar** containing:
- Dynamic page title and section breadcrumbs
- Project context selector (dropdown)
- User profile information with avatar
- Global notification system integration
- Quick action buttons

### toast.php
A **notification component** for displaying:
- Success messages (green)
- Warning alerts (yellow)
- Error notifications (red)
- Auto-dismiss functionality with slide animations

### head_assets.php / head_assetsv2.php
Centralized asset loading for:
- **Tailwind CSS v4** framework (via CDN)
- **Lucide** icons
- **Font Awesome 6** icons
- **Google Fonts** (Inter, Montserrat, Poppins)
- Favicon configuration
- Custom CSS variables and animations:
  - `animate-fade-in` - Page load animation
  - `animate-slide-in` / `animate-slide-out` - Modal transitions
  - `transition-all duration-300` - Smooth state changes

### report_print_layout.php
Print-optimized wrapper for generated reports with:
- Clean header/footer formatting
- Print media queries
- Professional document styling

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
   - `$_SESSION['user_role']` - Permission level
5. **Audit Logging**: Records login via `Logger::login()`
6. **Error Handling**: Redirects with error messages for failed attempts

#### logout.php
Securely destroys user sessions, logs the action, and redirects to login page.

#### signup_process.php
Handles new user registration with:
- Email uniqueness validation
- Password hashing with bcrypt
- Role assignment
- Account creation confirmation

### User Roles & Permissions
| Role | Access Level |
|------|-------------|
| `Admin` | Full system access, audit log viewing |
| `Manager` | Project oversight, approvals |
| `Staff` | Basic operations |
| `Budget_Officer` | Budget module full access |
| `Procurement_Officer` | Procurement module full access |

---

## 🛠️ Admin Module (admin)

### Purpose
System administration tools including comprehensive audit trail viewing.

### audit_logs.php - Audit Trail Viewer

**Functionality:**

1. **Filterable Log Display**
   - Filter by module (Project, Budget, Procurement, Workforce, Auth, Reports)
   - Filter by action type (CREATE, UPDATE, DELETE, LOGIN, etc.)
   - Search by details/description
   - Date range filtering

2. **AJAX-Powered Interface**
   - Debounced filter auto-submission (no Apply button needed)
   - AJAX pagination without page reload
   - Loading overlay during fetch operations
   - Real-time result count badge

3. **Clickable Row Details**
   - Click any row to view full payload in modal
   - JSON-formatted payload display
   - Timestamp and user attribution

4. **Pagination**
   - 10 records per page
   - AJAX-powered page navigation
   - Total record count display

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
   - **Delete**: Remove projects with **cascading deletion** of all related data

3. **Cascade Delete Warning**
   When deleting a project, user is warned:
   > "This will permanently delete all phases, tasks, budget proposals, expenses, purchase orders, inventory, workforce assignments, and attendance records associated with this project."

4. **Project Status Tracking**
   - Planning, In Progress, Active, On Hold, Completed, Cancelled
   - Completion percentage tracking
   - Visual status indicators with color coding

5. **Audit Integration**
   - All CRUD operations logged with descriptive messages
   - Delete logs include project name and code: `"Deleted project: Project Name (PRJ-2026-001)"`

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

3. **Drag-and-Drop Status Updates**
   - Quick status change via API

---

### gantt.php - Gantt Chart Visualization

**Functionality:**

1. **Interactive Gantt Chart**
   - Powered by **Frappe Gantt** library
   - Visual timeline of phases and tasks
   - Dependency visualization

2. **View Modes**
   - Day, Week (default), Month view toggles
   - Zoom in/out functionality

3. **Task Interaction**
   - Click tasks to view details
   - Visual progress indicators

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

1. **Financial Overview Cards**
   - Total approved budget (from proposals)
   - Actual spending (from expenses)
   - Remaining budget calculation
   - Active phases count

2. **Intelligent Budget Alerts**
   - **Green (Good)**: Under 70% utilization
   - **Yellow (Warning)**: 70-85% utilization
   - **Red (High)**: Over 85% utilization

3. **Phase Budget Cards**
   - Per-phase budget allocation
   - Per-phase spending breakdown
   - Phase utilization percentages
   - Clickable cards open detailed receipt modal

4. **Phase Receipt Modal**
   - Professional receipt-style layout
   - Budget proposals list for phase
   - Expense line items
   - PDF download capability

---

### proposals.php - Budget Proposals

**Functionality:**

1. **Proposal Creation**
   - Proposal code (auto-generated: BP-YYYY-XXX)
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

---

### expenses.php - Expense Tracker

**Functionality:**

1. **Phase-Based Expense View**
   - Expense breakdown by construction phase
   - Phase budget vs. actual spending
   - Real-time utilization calculations

2. **Expense Recording**
   - Project and phase association
   - Category (Materials, Labor, Equipment)
   - Supplier linkage
   - Description and amount
   - Status (Pending, Approved, Rejected)

3. **Budget Alert System**
   - Visual indicators when approaching budget limits
   - Color-coded status based on utilization

---

### payroll_expenses.php - Payroll-Linked Expenses

**Functionality:**

1. **Automatic Expense Generation**
   - Links processed payroll to budget expenses
   - Creates LABOR category expenses
   - Maintains payroll-expense relationship

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
   - Supplier information (name, contact, email, address)
   - Active/Inactive status management

2. **AJAX Pagination**
   - 10 suppliers per page
   - Client-side pagination controls
   - Real-time page navigation

3. **Supplier CRUD**
   - Add new suppliers with modal form
   - Edit supplier details
   - Delete suppliers (with PO dependency check)

---

### orders.php - Purchase Orders

**Functionality:**

1. **Purchase Order Creation**
   - Auto-generated PO reference (PO-YYYY-XXX)
   - Project and phase association
   - Supplier selection
   - Line items from inventory masterlist

2. **PO Workflow**
   - Status progression: PENDING → APPROVED → COMPLETED/REJECTED
   - KPI dashboard (Total, Pending, Approved, Completed)

3. **PO to Budget Integration**
   - Approved/Completed POs can create budget expenses
   - Links procurement to financial tracking

---

### inventory.php - Inventory Masterlist

**Functionality:**

1. **Inventory Dashboard**
   - Total items count
   - Total inventory valuation (₱)
   - Low stock alerts

2. **Item Management**
   - Item name, category, quantity, unit
   - Unit cost tracking
   - Project/phase association

---

### stock_in.php - Stock Receiving

**Functionality:**

1. **PO-Based Receiving**
   - Select from approved purchase orders
   - Receive items against PO quantities
   - Partial receiving support
   - Automatic inventory quantity update

---

### stock_out.php - Stock Issuance

**Functionality:**

1. **Stock Issuance**
   - Issue items to personnel/sites
   - Project association
   - Automatic stock level deduction
   - Prevents over-issuance

---

## 👷 Workforce Module (workforce)

### Purpose
Comprehensive human resource management for construction workforce including employee management, attendance tracking, payroll processing, and workforce assignments.

### Database Tables
- `workforce_employees` - Employee master records
- `workforce_job_titles` - Position definitions with rates
- `workforce_employee_groups` - Team definitions
- `workforce_group_memberships` - Team assignments
- `workforce_assignments` - Project assignments
- `workforce_attendance` - Daily attendance
- `workforce_payroll_periods` - Pay periods
- `workforce_payroll` - Payroll records

---

### dashboard.php - Workforce Dashboard

**Functionality:**

1. **Statistics Overview**
   - Total employees
   - Active employees
   - On leave count
   - Today's attendance rate

2. **Personnel Status Chart**
   - Bar chart showing Active/On Leave/Inactive counts
   - Powered by Chart.js

3. **Quick Actions**
   - Add Employee shortcut
   - Log Attendance shortcut
   - Process Payroll shortcut

4. **Recent Activity Feed**
   - Latest workforce-related audit logs
   - Action type badges (CREATE, UPDATE, DELETE)
   - User attribution

---

### employees.php - Employee Management

**Functionality:**

1. **Employee Registry**
   - Employee code (auto-generated: EMP-YYYY-XXX)
   - Personal information (name, contact, address)
   - Job title assignment with rate inheritance
   - Employment type (Regular, Contractual, Project-Based)
   - Payment type (Daily, Monthly)
   - Status tracking (Active, Inactive, Terminated)

2. **Statistics Dashboard**
   - Total employees
   - Active employees
   - New hires this month

3. **Search & Filter**
   - Search by name, role, or ID
   - Filter by status

---

### employee_groups.php - Team Management

**Functionality:**

1. **Group Formation**
   - Create teams (e.g., "Civil Works Team Alpha")
   - Auto-generated group codes
   - Group leader assignment
   - Description and purpose

2. **Group Statistics**
   - Total groups
   - Active groups
   - Total members across groups

---

### attendance.php - Attendance Management

**Functionality:**

1. **Daily Attendance Recording**
   - Date selection with calendar
   - Project-specific attendance
   - Time in/Time out entry
   - Status selection (Present, Absent, Late, On Leave)
   - Remarks field

2. **Bulk Save**
   - Save all attendance records at once
   - Real-time validation

---

### assignments.php - Project Assignments

**Functionality:**

1. **Assignment Creation**
   - Employee to project mapping
   - Phase-specific assignments
   - Role definition for assignment
   - Start and end dates
   - Task descriptions

2. **Assignment Status**
   - Active, Completed, Cancelled
   - Visual status indicators

---

### payroll.php - Payroll Processing

**Functionality:**

1. **Period-Based Payroll**
   - Create payroll periods (start/end dates)
   - Project-specific payroll

2. **Payroll Calculation**
   - Attendance-based computation
   - Daily rate × Days worked
   - Overtime calculations
   - Deductions (SSS, PhilHealth, Pag-IBIG, Tax)
   - Net pay calculation

3. **Payroll Actions**
   - Calculate payroll for period
   - Approve/Process payroll
   - Export to PDF

---

## 📈 Centralized Reports Module (reports)

### Purpose
Provides a unified reporting center that aggregates data from all modules for comprehensive analysis.

### index.php - Reports Center

**Functionality:**

1. **Multi-Module Report Hub**
   - Budget Reports (Summary, Variance, Phase)
   - Procurement Reports (Inventory, PO Status)
   - Project Reports (Status, Timeline)
   - Workforce Reports (Roster, Attendance, Payroll)

2. **Project Context**
   - Select specific project or "All Projects"
   - Project details display

3. **Report Categories**
   - Category tabs for organization
   - Template cards for each report type

4. **Recent Reports History**
   - Previously generated reports
   - Quick re-download
   - Audit compliance

5. **Export Options**
   - Print-optimized layouts
   - PDF generation with jsPDF
   - Professional headers/footers

---

## 🔄 Database Schema & Relationships

### Foreign Key Cascade Configuration

All foreign keys are configured with appropriate `ON DELETE` actions:

| Table | Foreign Key | References | On Delete |
|-------|-------------|------------|-----------|
| `icmis_project_phases` | `fk_phase_project` | `icmis_projects` | CASCADE |
| `icmis_tasks` | `fk_task_project` | `icmis_projects` | CASCADE |
| `icmis_tasks` | `fk_task_phase` | `icmis_project_phases` | SET NULL |
| `budget_proposals` | `fk_prop_project` | `icmis_projects` | CASCADE |
| `budget_proposals` | `fk_prop_phase` | `icmis_project_phases` | CASCADE |
| `budget_expenses` | `fk_exp_project` | `icmis_projects` | CASCADE |
| `budget_expenses` | `fk_exp_phase` | `icmis_project_phases` | CASCADE |
| `budget_line_items` | `fk_line_prop` | `budget_proposals` | CASCADE |
| `procurement_purchase_orders` | `fk_po_project` | `icmis_projects` | CASCADE |
| `procurement_purchase_orders` | `fk_po_phase` | `icmis_project_phases` | CASCADE |
| `procurement_inventory` | `fk_inv_project` | `icmis_projects` | CASCADE |
| `procurement_stock_in` | `fk_stockin_po` | `procurement_purchase_orders` | CASCADE |
| `procurement_stock_out` | `fk_stockout_proj` | `icmis_projects` | CASCADE |
| `workforce_assignments` | `fk_assign_proj` | `icmis_projects` | CASCADE |
| `workforce_assignments` | `fk_assign_phase` | `icmis_project_phases` | CASCADE |
| `workforce_attendance` | `fk_att_proj` | `icmis_projects` | CASCADE |

### Data Relationships Diagram

```
icmis_projects (Central Hub)
    │
    ├── icmis_project_phases ──────────────────┐
    │       └── icmis_tasks                    │
    │                                          │
    ├── budget_proposals ──────────────────────┤
    │       └── budget_line_items              │
    │                                          │
    ├── budget_expenses ───────────────────────┤
    │       └── (links to suppliers)           │
    │                                          │
    ├── procurement_purchase_orders ───────────┤
    │       ├── procurement_purchase_order_items
    │       └── procurement_stock_in           │
    │                                          │
    ├── procurement_inventory                  │
    │       └── procurement_stock_out          │
    │                                          │
    ├── workforce_assignments ─────────────────┤
    │                                          │
    └── workforce_attendance ──────────────────┘
```

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

### Audit Trail
- All sensitive operations logged via Logger class
- User attribution with timestamps
- IP address tracking
- Tamper-evident log storage

---

## 🎨 UI/UX Framework

### Design System
- **Primary Color**: `#e9922c` (Orange - construction branding)
- **Typography**: Inter font family (Google Fonts)
- **Layout**: Fixed sidebar (256px) + Header + Content area
- **Cards**: Rounded corners (`rounded-xl`), subtle shadows
- **Tables**: Striped rows, hover effects
- **Modals**: Slide-in animations, backdrop blur

### Animation Classes
```css
.animate-fade-in    /* Page load fade-in (0.2s) */
.animate-slide-in   /* Modal slide from right (0.4s) */
.animate-slide-out  /* Modal slide to right (0.4s) */
.transition-all duration-300  /* Smooth state changes */
```

### Responsive Design
- Fixed sidebar with content offset (`ml-56`)
- Grid layouts for stat cards
- Print-specific styles (`@media print`)

---

## 📊 Technology Stack Summary

| Layer | Technology |
|-------|------------|
| **Backend** | PHP 8.x |
| **Database** | MySQL 8.x with InnoDB (FK support) |
| **Frontend** | HTML5, Tailwind CSS v4 |
| **JavaScript** | Vanilla JS, Chart.js, Frappe Gantt |
| **Icons** | Lucide Icons, Font Awesome 6 |
| **PDF Generation** | jsPDF + AutoTable |
| **Server** | Laragon (Apache/Nginx) |

---

## 🔧 Key Implementation Patterns

### Safe JSON Response Parsing
Client-side fetch calls use safe JSON parsing to handle server errors gracefully:
```javascript
async function parseJSONResponse(res) {
    const text = await res.text();
    try {
        return JSON.parse(text);
    } catch (e) {
        console.error('Invalid JSON response:', text);
        throw new Error('Server returned invalid response');
    }
}
```

### Project Context Pattern
Every module that operates on project-specific data uses the context pattern:
```php
include __DIR__ . '/project_context.php';
$conn = getModuleConnection();
$selected_project_id = getProjectContext($conn);
```

### AJAX Pagination Pattern
Tables with large datasets use AJAX pagination:
```javascript
function fetchPage(page) {
    fetch(`api/endpoint.php?page=${page}&per_page=10`)
        .then(res => res.json())
        .then(data => {
            renderTable(data.items);
            renderPagination(data.total, data.page, data.per_page);
        });
}
```

---
