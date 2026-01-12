# ICMIS Technical User Manual
## Integrated Construction Management Information System

---

**Document Version:** 1.0  
**Last Updated:** January 13, 2026  
**System Version:** ICMIS v1.0  
**Target Audience:** System Administrators, Project Managers, Staff Users

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Authentication Module](#2-authentication-module)
3. [System Dashboard](#3-system-dashboard)
4. [Project Management Module](#4-project-management-module)
5. [Budget & Cost Control Module](#5-budget--cost-control-module)
6. [Procurement & Inventory Module](#6-procurement--inventory-module)
7. [Workforce Management Module](#7-workforce-management-module)
8. [Reports Module](#8-reports-module)
9. [Admin Module (Audit Logs)](#9-admin-module-audit-logs)
10. [Appendix: Database Schema Reference](#10-appendix-database-schema-reference)

---

## 1. System Overview

**ICMIS (Integrated Construction Management Information System)** is a comprehensive web-based platform designed for end-to-end construction project management. The system integrates project planning, budget control, procurement, workforce management, and reporting into a unified platform.

### System Architecture

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.x |
| Database | MySQL 8.x (InnoDB) |
| Frontend | HTML5, Tailwind CSS v4, JavaScript |
| Icons | Lucide Icons, Font Awesome 6 |
| Charts | Chart.js, Frappe Gantt |
| PDF Generation | jsPDF + AutoTable |

### User Roles & Access Levels

| Role | Description |
|------|-------------|
| `Admin` | Full system access, including audit log viewing |
| `Manager` | Project oversight and approval capabilities |
| `Staff` | Basic operational access |
| `Budget_Officer` | Full access to Budget module |
| `Procurement_Officer` | Full access to Procurement module |

---

## 2. Authentication Module

**Module Overview:** Handles user authentication, session management, and account creation for secure system access.

### 2.1 Login Workflow

**Step-by-Step Instructions:**

1. Navigate to the system entry point (`index.php`).
2. On the **LOGIN** tab, enter your credentials:
   - In the **Work Email** field, type your registered email address.
   - In the **Password** field, enter your password.
3. Click the **LOGIN** button.
4. Upon successful authentication, you will be redirected to the **Dashboard**.

**Data Logic Callouts:**

| Action | Data Flow |
|--------|-----------|
| **Email Input** | *User manual input* |
| **Password Input** | *User manual input* |
| **Credential Verification** | Fetches user record from *`icmis_users`* table (columns: `email`, `password`, `user_id`, `full_name`, `role`) |
| **Password Comparison** | Uses `password_verify()` to compare input with bcrypt hash stored in *`icmis_users.password`* |
| **Session Creation** | Stores `user_id`, `user_name`, `user_role` in PHP session variables |
| **Audit Logging** | Inserts LOGIN action record into *`icmis_audit_logs`* table (columns: `user_id`, `user_name`, `action`, `module`, `details`, `ip_address`, `user_agent`, `created_at`) |

---

### 2.2 Sign Up Workflow

**Step-by-Step Instructions:**

1. On the login page, click the **SIGN UP** tab.
2. Fill in the registration form:
   - Enter your **Full Name**.
   - Enter a valid **Work Email** address.
   - Create a secure **Password**.
3. Click the **SIGN UP** button.
4. Upon successful registration, you will be redirected to the login page with a success message.

**Data Logic Callouts:**

| Action | Data Flow |
|--------|-----------|
| **Full Name Input** | *User manual input* |
| **Email Input** | *User manual input* |
| **Password Input** | *User manual input* |
| **Email Uniqueness Check** | Queries *`icmis_users`* table to verify email doesn't already exist |
| **Password Hashing** | Applies `password_hash()` with bcrypt algorithm |
| **Account Creation** | Inserts new record into *`icmis_users`* table (columns: `full_name`, `email`, `password`, `role`, `created_at`) with default role 'Admin' |

---

### 2.3 Logout Workflow

**Step-by-Step Instructions:**

1. Click your **User Profile** icon in the top navigation bar.
2. Select **Logout** from the dropdown menu.
3. You will be redirected to the login page.

**Data Logic Callouts:**

| Action | Data Flow |
|--------|-----------|
| **Logout Event** | Inserts LOGOUT action record into *`icmis_audit_logs`* table |
| **Session Destruction** | Clears all PHP session variables and destroys the session |
| **Cookie Removal** | Removes session cookie from browser |

---

## 3. System Dashboard

**Module Overview:** Provides a centralized overview of system-wide statistics and quick navigation to all modules after login.

**File Location:** `dashboard.php`

### 3.1 Viewing the Dashboard

**Step-by-Step Instructions:**

1. After successful login, you are automatically directed to the **Dashboard**.
2. View the **Project Selector** dropdown in the header to select an active project context.
3. Review summary cards displaying key metrics.

**Data Logic Callouts:**

| Display Element | Data Source |
|----------------|-------------|
| **Project Selector Dropdown** | Fetches all projects from *`icmis_projects`* table |
| **User Profile Display** | Fetches from session variables (`user_name`, `user_role`) |
| **Project Context** | Stored in session and managed by *`core/Context.php`* |

---

## 4. Project Management Module

**Module Overview:** Manages construction projects, their phases, and individual tasks with visual timeline tracking via Gantt charts.

**File Locations:**
- Projects: `modules/project/projects.php`
- Phases: `modules/project/phases.php`
- Tasks: `modules/project/tasks.php`
- Gantt Chart: `modules/project/gantt.php`

---

### 4.1 Projects Management

#### 4.1.1 Viewing All Projects

**Step-by-Step Instructions:**

1. Navigate to **Project** → **Projects** in the sidebar.
2. View the statistics dashboard showing:
   - **Total Projects** count
   - **Active Projects** count
   - **Completed Projects** count
   - **Total Budget** allocation
3. Browse the projects list in the table below.

**Data Logic Callouts:**

| Display Element | Data Source |
|----------------|-------------|
| **Projects List** | Fetches all records from *`icmis_projects`* table with LEFT JOIN to *`workforce_employees`* for manager name |
| **Project Manager Name** | Joins *`icmis_projects.project_manager_id`* → *`workforce_employees.employee_id`* |
| **Statistics Cards** | Aggregates from *`icmis_projects`* table (COUNT, SUM operations) |

---

#### 4.1.2 Creating a New Project

**Step-by-Step Instructions:**

1. Click the **Add Project** button.
2. In the modal form, fill in the following fields:
   - **Project Code** — Auto-generated (format: `PRJ-YYYY-XXX`), can be edited.
   - **Project Name** — Enter the project title (*required*).
   - **Description** — Provide project details.
   - **Location** — Enter the project site location.
   - **Project Manager** — Select from the dropdown.
   - **Start Date** — Select the project start date.
   - **End Date** — Select the expected completion date.
   - **Total Budget** — Enter the allocated budget amount.
   - **Status** — Select from: Planning, In Progress, Active, On Hold, Completed, Cancelled.
3. Click **Save Project**.

**Data Logic Callouts:**

| Field | Data Flow |
|-------|-----------|
| **Project Code** | Auto-generated by querying *`icmis_projects`* for latest code with current year, incrementing sequence number |
| **Project Manager Dropdown** | Fetches from *`workforce_employees`* table (active employees) |
| **Form Submission** | Inserts new record into *`icmis_projects`* table (columns: `project_code`, `project_name`, `description`, `location`, `project_manager_id`, `start_date`, `end_date`, `total_budget`, `status`) |
| **Audit Trail** | Inserts CREATE action into *`icmis_audit_logs`* with details: "Created new project: {name} ({code})" |

---

#### 4.1.3 Editing a Project

**Step-by-Step Instructions:**

1. In the projects table, locate the project to edit.
2. Click the **Edit** (pencil) icon in the Actions column.
3. Modify the desired fields in the modal form.
4. Click **Save Project**.

**Data Logic Callouts:**

| Action | Data Flow |
|--------|-----------|
| **Load Existing Data** | Fetches project record from *`icmis_projects`* WHERE `project_id` = selected ID |
| **Form Submission** | Updates record in *`icmis_projects`* table using UPDATE statement |
| **Audit Trail** | Inserts UPDATE action into *`icmis_audit_logs`* |

---

#### 4.1.4 Deleting a Project

**Step-by-Step Instructions:**

1. Click the **Delete** (trash) icon for the target project.
2. Review the **cascade warning** — deleting a project will also delete:
   - All phases and tasks
   - Budget proposals and expenses
   - Purchase orders and inventory records
   - Workforce assignments and attendance
3. Click **Confirm Delete** to proceed.

**Data Logic Callouts:**

| Action | Data Flow |
|--------|-----------|
| **Cascade Deletion** | Deletes related records from: *`icmis_tasks`*, *`icmis_project_phases`*, *`budget_expenses`*, *`budget_proposals`*, *`budget_generated_reports`*, *`procurement_purchase_orders`* |
| **Project Deletion** | Deletes record from *`icmis_projects`* WHERE `project_id` = selected ID |
| **Audit Trail** | Inserts DELETE action into *`icmis_audit_logs`* with project name and code |

---

### 4.2 Phases Management

#### 4.2.1 Viewing Project Phases

**Step-by-Step Instructions:**

1. Navigate to **Project** → **Phases** in the sidebar.
2. Select a project from the **Project Selector** dropdown in the header.
3. View the phases list for the selected project.

**Data Logic Callouts:**

| Display Element | Data Source |
|----------------|-------------|
| **Phases List** | Fetches from *`icmis_project_phases`* WHERE `project_id` = selected project |
| **Duration Calculation** | Computed from `start_date` and `end_date` columns |

---

#### 4.2.2 Creating a New Phase

**Step-by-Step Instructions:**

1. Click the **Add Phase** button.
2. Fill in the phase form:
   - **Phase Name** — e.g., "Phase 1: Mobilization" (*required*).
   - **Project** — Select the parent project.
   - **Description** — Enter phase details.
   - **Start Date** — Select phase start.
   - **End Date** — Select phase completion target.
   - **Status** — Select: Not Started, In Progress, Completed.
3. Click **Save Phase**.

**Data Logic Callouts:**

| Field | Data Flow |
|-------|-----------|
| **Project Dropdown** | Fetches from *`icmis_projects`* table |
| **Duration** | Auto-calculated from `start_date` and `end_date` difference in days |
| **Form Submission** | Inserts into *`icmis_project_phases`* table (columns: `project_id`, `phase_name`, `description`, `start_date`, `end_date`, `duration`, `status`) |

---

### 4.3 Tasks Management

#### 4.3.1 Creating a New Task

**Step-by-Step Instructions:**

1. Navigate to **Project** → **Tasks**.
2. Click the **Add Task** button.
3. Complete the task form:
   - **Task Name** — Enter task title (*required*).
   - **Project** — Select the parent project.
   - **Phase** — Select the associated phase (optional).
   - **Assigned To** — Select an employee from the dropdown.
   - **Description** — Enter task details.
   - **Start Date** / **Due Date** — Set timeline.
   - **Priority** — Select: Low, Medium, High, Urgent.
   - **Status** — Select: Not Started, In Progress, Completed, On Hold.
4. Click **Save Task**.

**Data Logic Callouts:**

| Field | Data Flow |
|-------|-----------|
| **Project Dropdown** | Fetches from *`icmis_projects`* table |
| **Phase Dropdown** | Fetches from *`icmis_project_phases`* WHERE `project_id` = selected project |
| **Assigned To Dropdown** | Fetches from *`workforce_employees`* table |
| **Form Submission** | Inserts into *`icmis_tasks`* table (columns: `task_name`, `project_id`, `phase_id`, `description`, `assigned_to_employee_id`, `start_date`, `due_date`, `priority`, `status`) |

---

### 4.4 Gantt Chart Visualization

**Step-by-Step Instructions:**

1. Navigate to **Project** → **Gantt Chart**.
2. View the interactive timeline displaying phases and tasks.
3. Use view mode toggles: **Day**, **Week** (default), **Month**.
4. Click on any task bar to view its details.
5. Drag task bars to adjust dates (if permissions allow).

**Data Logic Callouts:**

| Display Element | Data Source |
|----------------|-------------|
| **Gantt Tasks** | Fetches from *`icmis_project_phases`* and *`icmis_tasks`* for selected project |
| **Timeline Data** | Uses `start_date`, `end_date` columns from both tables |

---

## 5. Budget & Cost Control Module

**Module Overview:** Manages all financial aspects including budget proposals, expense tracking, and financial reporting for construction projects.

**File Locations:**
- Dashboard: `modules/budget/dashboard.php`
- Proposals: `modules/budget/proposals.php`
- Expenses: `modules/budget/expenses.php`
- Payroll Expenses: `modules/budget/payroll_expenses.php`

---

### 5.1 Budget Dashboard

**Step-by-Step Instructions:**

1. Navigate to **Budget** → **Dashboard** in the sidebar.
2. Ensure a project is selected in the **Project Selector**.
3. Review the financial overview cards:
   - **Total Approved Budget** (from approved proposals)
   - **Actual Spending** (from expenses)
   - **Remaining Budget**
   - **Active Phases Count**
4. View **Phase Budget Cards** showing per-phase allocation and utilization.
5. Click any phase card to open the **Phase Receipt Modal** with detailed breakdown.

**Data Logic Callouts:**

| Display Element | Data Source |
|----------------|-------------|
| **Total Approved Budget** | SUM of `total_amount` from *`budget_proposals`* WHERE `status` = 'APPROVED' AND `project_id` = selected |
| **Actual Spending** | SUM of `amount` from *`budget_expenses`* WHERE `project_id` = selected AND `status` = 'APPROVED' |
| **Phase Cards** | Fetches from *`icmis_project_phases`* with aggregated amounts from *`budget_proposals`* and *`budget_expenses`* |
| **Budget Alerts** | Calculated: Green (<70%), Yellow (70-85%), Red (>85%) utilization |

---

### 5.2 Budget Proposals

#### 5.2.1 Creating a Budget Proposal

**Step-by-Step Instructions:**

1. Navigate to **Budget** → **Proposals**.
2. Click the **Create Proposal** button.
3. Complete the proposal form:
   - **Proposal Title** — Enter a descriptive title (*required*).
   - **Project** — Auto-selected from context.
   - **Target Phase** — Select the associated phase.
   - **Scope Description** — Describe the proposal purpose.
4. Add **Line Items** by clicking **Add Item**:
   - **Category** — Select: MATERIAL, LABOR, EQUIPMENT.
   - **Item Name** — Enter the item description.
   - **Quantity** — Enter the required quantity.
   - **Unit Cost** — Enter the cost per unit.
   - **Duration** — Enter duration multiplier (if applicable).
   - *Subtotal auto-calculates: Quantity × Unit Cost × Duration*
5. Review the **Total Amount** at the bottom.
6. Set **Status**: DRAFT, PENDING, APPROVED, or REJECTED.
7. Click **Submit Proposal**.

**Data Logic Callouts:**

| Field | Data Flow |
|-------|-----------|
| **Proposal Code** | Auto-generated: `BP-{YEAR}-{XXXX}` by counting existing proposals for current year in *`budget_proposals`* |
| **Phase Dropdown** | Fetches from *`icmis_project_phases`* WHERE `project_id` = selected project |
| **Form Submission** | Inserts into *`budget_proposals`* table (columns: `project_id`, `phase_id`, `code`, `title`, `description`, `total_amount`, `status`, `created_by`, `created_at`) |
| **Line Items** | Inserts into *`budget_line_items`* table (columns: `proposal_id`, `category`, `item_name`, `quantity`, `unit_cost`, `duration`, `subtotal`) |
| **Audit Trail** | Inserts CREATE action into *`icmis_audit_logs`* with proposal title and amount |

---

### 5.3 Expense Tracking

#### 5.3.1 Recording an Expense

**Step-by-Step Instructions:**

1. Navigate to **Budget** → **Expenses**.
2. Click the **Add Expense** button.
3. Complete the expense form:
   - **Project** — Select the project.
   - **Phase** — Select the associated phase.
   - **Category** — Select: MATERIALS, LABOR, EQUIPMENT.
   - **Supplier** — Select from registered suppliers (optional).
   - **Description** — Describe the expense.
   - **Amount** — Enter the expense amount.
   - **Expense Date** — Select the date.
   - **Status** — Select: PENDING, APPROVED, REJECTED.
4. Click **Save Expense**.

**Data Logic Callouts:**

| Field | Data Flow |
|-------|-----------|
| **Project Dropdown** | Fetches from *`icmis_projects`* table |
| **Phase Dropdown** | Fetches from *`icmis_project_phases`* WHERE `project_id` = selected |
| **Supplier Dropdown** | Fetches from *`procurement_suppliers`* table |
| **Form Submission** | Inserts into *`budget_expenses`* table (columns: `project_id`, `phase_id`, `supplier_id`, `category`, `description`, `amount`, `expense_date`, `status`, `created_by`) |

---

### 5.4 Payroll-Linked Expenses

**Module Overview:** Automatically creates LABOR category expenses from processed payroll records.

**Step-by-Step Instructions:**

1. Navigate to **Budget** → **Payroll Expenses**.
2. View expenses generated from locked payroll periods.
3. These entries are automatically created when payroll is processed.

**Data Logic Callouts:**

| Action | Data Flow |
|--------|-----------|
| **Automatic Expense Creation** | When payroll is locked, inserts LABOR expense into *`budget_expenses`* with amount from *`workforce_payroll`* totals |
| **Linkage** | References `project_id` from *`workforce_assignments`* and period from *`workforce_payroll_periods`* |

---

## 6. Procurement & Inventory Module

**Module Overview:** Manages the complete procurement lifecycle from supplier management through purchase orders to inventory tracking and stock movements.

**File Locations:**
- Suppliers: `modules/procurement/suppliers.php`
- Purchase Orders: `modules/procurement/orders.php`
- Inventory: `modules/procurement/inventory.php`
- Stock In: `modules/procurement/stock_in.php`
- Stock Out: `modules/procurement/stock_out.php`

---

### 6.1 Supplier Management

#### 6.1.1 Adding a New Supplier

**Step-by-Step Instructions:**

1. Navigate to **Procurement** → **Suppliers**.
2. Click the **Add Supplier** button.
3. Complete the supplier form:
   - **Supplier Name** — Enter company/vendor name (*required*).
   - **Contact Person** — Enter primary contact name.
   - **Contact Number** — Enter phone number.
   - **Email** — Enter email address.
   - **Address** — Enter complete address.
   - **Status** — Select: Active or Inactive.
4. Click **Save Supplier**.

**Data Logic Callouts:**

| Field | Data Flow |
|-------|-----------|
| **All Fields** | *User manual input* |
| **Form Submission** | Inserts into *`procurement_suppliers`* table (columns: `supplier_name`, `contact_person`, `contact_number`, `email`, `address`, `status`) |

---

### 6.2 Purchase Orders

#### 6.2.1 Creating a Purchase Order

**Step-by-Step Instructions:**

1. Navigate to **Procurement** → **Purchase Orders**.
2. Click the **Create Order** button.
3. Complete the PO form:
   - **Project** — Select the project (*required*).
   - **Phase** — Select the target phase.
   - **Supplier** — Select from registered suppliers (*required*).
   - **Order Title** — Enter a descriptive title.
4. Add **Line Items**:
   - **Item Name** — Enter or select from inventory.
   - **Quantity** — Enter the order quantity.
   - **Unit Cost** — Enter the price per unit.
   - *Total auto-calculates: Quantity × Unit Cost*
5. Review the **Grand Total**.
6. Click **Submit Order**.

**Data Logic Callouts:**

| Field | Data Flow |
|-------|-----------|
| **PO Reference** | Auto-generated: `PO-{YEAR}-{XXXX}` by counting existing POs for current year in *`procurement_purchase_orders`* |
| **Supplier Dropdown** | Fetches from *`procurement_suppliers`* WHERE `status` = 'Active' |
| **Phase Dropdown** | Fetches from *`icmis_project_phases`* WHERE `project_id` = selected |
| **Item Suggestions** | Fetches from *`procurement_inventory`* for auto-complete |
| **Header Submission** | Inserts into *`procurement_purchase_orders`* (columns: `po_reference`, `project_id`, `phase_id`, `supplier_id`, `order_title`, `order_date`, `total_amount`, `status`, `created_by_user_id`) |
| **Line Items Submission** | Inserts into *`procurement_purchase_order_items`* (columns: `po_id`, `item_name`, `quantity`, `unit_cost`, `total_cost`) |
| **Audit Trail** | Inserts CREATE action into *`icmis_audit_logs`*: "Purchase Order Created: {PO-ref} - {title} (₱{amount})" |

---

#### 6.2.2 Approving a Purchase Order

**Step-by-Step Instructions:**

1. In the PO list, locate the order with **PENDING** status.
2. Click the **Edit** icon or open the PO details.
3. Change the **Status** to **APPROVED**.
4. Click **Save**.

**Data Logic Callouts:**

| Action | Data Flow |
|--------|-----------|
| **Status Update** | Updates `status` column in *`procurement_purchase_orders`* |
| **Audit Trail** | Inserts UPDATE action into *`icmis_audit_logs`*: "Purchase Order Updated: {title} - Status: APPROVED (₱{amount})" |

---

### 6.3 Inventory Management

#### 6.3.1 Viewing Inventory

**Step-by-Step Instructions:**

1. Navigate to **Procurement** → **Inventory**.
2. View the inventory dashboard:
   - **Total Items** count
   - **Total Valuation** (₱)
   - **Low Stock Alerts**
3. Browse the inventory masterlist table.

**Data Logic Callouts:**

| Display Element | Data Source |
|----------------|-------------|
| **Inventory List** | Fetches from *`procurement_inventory`* table with optional project filter |
| **Total Valuation** | SUM of (`quantity` × `unit_cost`) from *`procurement_inventory`* |

---

### 6.4 Stock Receiving (Stock In)

**Step-by-Step Instructions:**

1. Navigate to **Procurement** → **Stock In**.
2. Select an **Approved PO** from the dropdown.
3. For each line item, enter the **Quantity Received**.
4. Click **Save Stock In**.

**Data Logic Callouts:**

| Field | Data Flow |
|-------|-----------|
| **Approved PO Dropdown** | Fetches from *`procurement_purchase_orders`* WHERE `status` IN ('APPROVED', 'COMPLETED') |
| **PO Items List** | Fetches from *`procurement_purchase_order_items`* WHERE `po_id` = selected PO |
| **Stock In Record** | Inserts into *`procurement_stock_in`* (columns: `po_id`, `item_id`, `quantity_received`, `unit_cost`, `total_cost`, `date_received`) |
| **Inventory Update** | Updates *`procurement_inventory`*: `quantity` = `quantity` + received quantity; creates new inventory record if item doesn't exist |
| **PO Status Check** | If total received ≥ total ordered, updates *`procurement_purchase_orders`*.`status` to 'COMPLETED' |
| **Expense Auto-Creation** | On PO completion, inserts MATERIALS expense into *`budget_expenses`* with PO total amount |
| **Audit Trail** | Inserts CREATE action: "Stock Received for PO #{id} - Items processed successfully" |

---

### 6.5 Stock Issuance (Stock Out)

**Step-by-Step Instructions:**

1. Navigate to **Procurement** → **Stock Out**.
2. Click **Issue Stock**.
3. Complete the issuance form:
   - **Item** — Select from inventory items.
   - **Quantity** — Enter quantity to issue.
   - **Issue To** — Select the receiving employee.
   - **Project** — Select the associated project (optional).
4. Click **Issue Stock**.

**Data Logic Callouts:**

| Field | Data Flow |
|-------|-----------|
| **Item Dropdown** | Fetches from *`procurement_inventory`* WHERE `quantity` > 0 |
| **Employee Dropdown** | Fetches from *`workforce_employees`* |
| **Stock Validation** | Checks *`procurement_inventory`*.`quantity` to prevent over-issuance |
| **Inventory Deduction** | Updates *`procurement_inventory`*: `quantity` = `quantity` - issued quantity |
| **Stock Out Record** | Inserts into *`procurement_stock_out`* (columns: `item_id`, `quantity`, `issued_to_employee_id`, `project_id`, `date_issued`) |
| **Audit Trail** | Inserts CREATE action: "Stock Issued: {qty} units to Employee #{id}" |

---

## 7. Workforce Management Module

**Module Overview:** Comprehensive human resource management for construction workforce including employee records, team management, attendance tracking, project assignments, and payroll processing.

**File Locations:**
- Dashboard: `modules/workforce/dashboard.php`
- Employees: `modules/workforce/employees.php`
- Groups: `modules/workforce/employee_groups.php`
- Attendance: `modules/workforce/attendance.php`
- Assignments: `modules/workforce/assignments.php`
- Payroll: `modules/workforce/payroll.php`

---

### 7.1 Workforce Dashboard

**Step-by-Step Instructions:**

1. Navigate to **Workforce** → **Dashboard**.
2. View statistics overview:
   - **Total Employees** count
   - **Active Employees** count
   - **On Leave** count
   - **Today's Attendance Rate**
3. View the **Personnel Status Chart** (bar chart).
4. Use **Quick Actions** for common tasks.

**Data Logic Callouts:**

| Display Element | Data Source |
|----------------|-------------|
| **Employee Statistics** | Aggregates from *`workforce_employees`* grouped by `status` column |
| **Today's Attendance** | Counts from *`workforce_attendance`* WHERE `attendance_date` = TODAY |
| **Recent Activity** | Fetches latest records from *`icmis_audit_logs`* WHERE `module` = 'Workforce' |

---

### 7.2 Employee Management

#### 7.2.1 Adding a New Employee

**Step-by-Step Instructions:**

1. Navigate to **Workforce** → **Employees**.
2. Click the **Add Employee** button.
3. Complete the employee form across tabs:

   **Personal Information:**
   - **First Name** / **Last Name** / **Suffix** (*required*)
   - **Gender** — Select: Male, Female, Other, Prefer not to say
   - **Birthday** — Select date
   - **Email** — Enter email address
   - **Phone** — Enter contact number
   - **Address** — Enter home address

   **Employment Details:**
   - **Employee Code** — Auto-generated: `EMP-{YEAR}-{XXX}`
   - **Job Title** — Select from predefined titles
   - **Employment Type** — Select: Regular, Contractual, Project-based, Probationary
   - **Payment Type** — Select: Daily, Monthly
   - **Daily Rate** / **Monthly Salary** — Auto-populated from job title defaults
   - **Hire Date** — Select employment start date
   - **Status** — Select: Active, Inactive, Terminated

   **Banking & Emergency:**
   - **Bank Name** — Enter bank name
   - **Bank Account** — Enter account number
   - **Emergency Contact Name** — Enter name
   - **Emergency Contact Phone** — Enter phone

4. Click **Save Employee**.

**Data Logic Callouts:**

| Field | Data Flow |
|-------|-----------|
| **Employee Code** | Auto-generated by querying max `employee_id` from *`workforce_employees`* and formatting as `EMP-{YEAR}-{XXX}` |
| **Job Title Dropdown** | Fetches from *`workforce_job_titles`* (columns: `job_title_id`, `title_name`, `department`) |
| **Rate Auto-Population** | Fetches `default_daily_rate` and `default_monthly_salary` from *`workforce_job_titles`* when job title is selected |
| **Form Submission** | Inserts into *`workforce_employees`* (columns: `employee_code`, `first_name`, `last_name`, `suffix`, `gender`, `birthday`, `email`, `phone`, `address`, `job_title_id`, `employment_type`, `payment_type`, `daily_rate`, `monthly_salary`, `bank_name`, `bank_account`, `emergency_contact_name`, `emergency_contact_phone`, `hire_date`, `status`) |
| **Audit Trail** | Inserts CREATE action: "Employee Created: {first_name} {last_name} ({employee_code})" |

---

### 7.3 Employee Groups (Teams)

#### 7.3.1 Creating an Employee Group

**Step-by-Step Instructions:**

1. Navigate to **Workforce** → **Employee Groups**.
2. Click the **Create Group** button.
3. Complete the group form:
   - **Group Name** — Enter team name (*required*), e.g., "Civil Works Team Alpha".
   - **Group Leader** — Select an employee as team leader.
   - **Description** — Enter group purpose/notes.
   - **Members** — Select employees to add to the group.
4. Click **Save Group**.

**Data Logic Callouts:**

| Field | Data Flow |
|-------|-----------|
| **Group Code** | Auto-generated: `GRP-{YEAR}-{XXX}` |
| **Leader Dropdown** | Fetches from *`workforce_employees`* WHERE `status` = 'Active' |
| **Members Selection** | Multi-select from *`workforce_employees`* |
| **Group Creation** | Inserts into *`workforce_employee_groups`* (columns: `group_code`, `group_name`, `group_leader_id`, `description`) |
| **Membership Records** | Inserts into *`workforce_group_memberships`* for each selected member (columns: `employee_id`, `group_id`, `role_in_group`, `joined_date`) |

---

### 7.4 Attendance Management

#### 7.4.1 Recording Daily Attendance

**Step-by-Step Instructions:**

1. Navigate to **Workforce** → **Attendance**.
2. Select the **Date** using the date picker.
3. Select the **Project** context (optional).
4. For each employee in the list, enter:
   - **Time In** — Select arrival time.
   - **Time Out** — Select departure time.
   - **Status** — Select: Present, Absent, Late, On Leave.
   - **Remarks** — Enter any notes (optional).
5. Click **Save All Attendance**.

**Data Logic Callouts:**

| Field | Data Flow |
|-------|-----------|
| **Date Selector** | *User input*; defaults to current date |
| **Employee List** | Fetches all active employees from *`workforce_employees`* with LEFT JOIN to *`workforce_attendance`* for pre-filled data |
| **Existing Attendance Check** | Queries *`workforce_attendance`* WHERE `employee_id` AND `attendance_date` match |
| **New Attendance** | Inserts into *`workforce_attendance`* (columns: `employee_id`, `project_id`, `attendance_date`, `time_in`, `time_out`, `status`, `remarks`) |
| **Update Attendance** | Updates existing record in *`workforce_attendance`* if already exists for employee+date |

---

### 7.5 Project Assignments

#### 7.5.1 Creating an Assignment

**Step-by-Step Instructions:**

1. Navigate to **Workforce** → **Assignments**.
2. Click **Add Assignment**.
3. Select **Assignment Mode**:
   - **Individual** — Assign single employee.
   - **Group** — Assign entire team.
4. Complete the assignment form:
   - **Employee** (Individual) or **Group** — Select from dropdown.
   - **Project** — Select the target project (*required*).
   - **Phase** — Select the specific phase (optional).
   - **Role** — Enter role/responsibility for this assignment.
   - **Start Date** / **End Date** — Set assignment duration.
5. Click **Save Assignment**.

**Data Logic Callouts:**

| Field | Data Flow |
|-------|-----------|
| **Employee Dropdown** | Fetches from *`workforce_employees`* WHERE `status` = 'Active' |
| **Group Dropdown** | Fetches from *`workforce_employee_groups`* |
| **Project Dropdown** | Fetches from *`icmis_projects`* |
| **Phase Dropdown** | Fetches from *`icmis_project_phases`* WHERE `project_id` = selected |
| **Individual Assignment** | Inserts single record into *`workforce_assignments`* (columns: `employee_id`, `project_id`, `phase_id`, `role`, `task_description`, `start_date`, `end_date`, `status`) |
| **Group Assignment** | Fetches member list from *`workforce_group_memberships`* WHERE `group_id` = selected; inserts one *`workforce_assignments`* record per member |

---

### 7.6 Payroll Processing

#### 7.6.1 Viewing Payroll

**Step-by-Step Instructions:**

1. Navigate to **Workforce** → **Payroll**.
2. Select the **Month** and **Period** (1st half: 1-15, 2nd half: 16-end).
3. View the payroll calculation table showing:
   - Employee name and role
   - Hours worked / Days worked
   - Basic pay
   - Government deductions (SSS, PhilHealth, Pag-IBIG)
   - Gross pay and Net pay

**Data Logic Callouts:**

| Display Element | Data Source |
|----------------|-------------|
| **Employee List** | Fetches from *`workforce_assignments`* WHERE `project_id` = selected AND `status` = 'Active', joined with *`workforce_employees`* and *`workforce_job_titles`* |
| **Hours/Days Worked** | Calculated from *`workforce_attendance`* records within the period date range |
| **Daily Rate** | From *`workforce_employees.daily_rate`* or *`workforce_job_titles.default_daily_rate`* |
| **Gross Pay** | Calculated: `daily_rate` × days worked (or `monthly_salary` / 2 for monthly employees) |
| **Government Deductions** | Calculated using Philippine 2025 contribution rates: SSS (4.5% capped at ₱1,350), PhilHealth (2.5%), Pag-IBIG (2% capped at ₱200) |
| **Net Pay** | Calculated: `gross_pay` - total deductions |

---

#### 7.6.2 Locking (Processing) Payroll

**Step-by-Step Instructions:**

1. After reviewing the payroll calculations, click **Lock Payroll Period**.
2. Confirm the action in the dialog.
3. The payroll period is now closed and cannot be modified.

**Data Logic Callouts:**

| Action | Data Flow |
|--------|-----------|
| **Period Creation** | Inserts into *`workforce_payroll_periods`* (columns: `start_date`, `end_date`, `pay_date`, `status`) with `status` = 'Closed' |
| **Payroll Records** | Inserts into *`workforce_payroll`* for each employee (columns: `employee_id`, `period_id`, `hours_worked`, `gross_pay`, `net_pay`, `status`) |
| **Audit Trail** | Inserts CREATE action: "Payroll Locked: Period {dates} for Project #{id} ({count} employees processed)" |

---

#### 7.6.3 Exporting Payroll PDF

**Step-by-Step Instructions:**

1. Click **Download PDF** or **Print** button.
2. The system generates a professional payroll report.

**Data Logic Callouts:**

| Action | Data Flow |
|--------|-----------|
| **PDF Generation** | Fetches locked payroll data from *`workforce_payroll`* joined with *`workforce_payroll_periods`* and *`workforce_employees`* |
| **Audit Trail** | Inserts EXPORT action: "Payroll report viewed/exported" |

---

#### 7.6.4 Printing Individual Payslip

**Step-by-Step Instructions:**

1. In the payroll table, locate the employee row.
2. Click the **Print Payslip** icon in the Actions column.
3. A print-ready payslip opens.

**Data Logic Callouts:**

| Action | Data Flow |
|--------|-----------|
| **Payslip Data** | Fetches from *`workforce_payroll`* WHERE `employee_id` AND `period_id` match, with employee details from *`workforce_employees`* |
| **Audit Trail** | Inserts EXPORT action: "Payslip printed for {employee_name}" |

---

## 8. Reports Module

**Module Overview:** Provides a centralized reporting center that aggregates data from all modules for comprehensive analysis and export.

**File Location:** `modules/reports/index.php`

---

### 8.1 Generating Reports

**Step-by-Step Instructions:**

1. Navigate to **Reports** in the sidebar.
2. Select a **Project** from the project selector (or "All Projects").
3. Browse available report templates by category:
   - **Budget Reports**: Budget Summary, Expense Log, Cash Flow
   - **Procurement Reports**: Inventory Status, PO Summary
   - **Project Reports**: Project Status, Timeline
   - **Workforce Reports**: Employee Roster, Attendance Summary, Payroll History
4. Click the **Generate** button on the desired report card.
5. Review the report preview.
6. Click **Print** or **Download PDF** to export.

**Data Logic Callouts:**

| Report Type | Data Source |
|-------------|-------------|
| **Budget Summary** | Aggregates from *`budget_expenses`* grouped by `category` |
| **Expense Log** | Fetches from *`budget_expenses`* joined with *`procurement_suppliers`* |
| **Inventory Status** | Fetches from *`procurement_inventory`* |
| **Employee Roster** | Fetches from *`workforce_employees`* joined with *`workforce_job_titles`* |
| **Attendance Summary** | Aggregates from *`workforce_attendance`* grouped by employee and date range |
| **Report History** | Inserts into *`icmis_generated_reports`* (columns: `report_name`, `category`, `project_id`, `project_name`, `generated_by`, `created_at`) |
| **Audit Trail** | Inserts EXPORT action into *`icmis_audit_logs`*: "Generated Report: {report_name} for {project_name}" |

---

### 8.2 Viewing Report History

**Step-by-Step Instructions:**

1. On the Reports page, scroll to the **Recent Reports** section.
2. View previously generated reports.
3. Click any report to re-download.

**Data Logic Callouts:**

| Display Element | Data Source |
|----------------|-------------|
| **Report History List** | Fetches from *`icmis_generated_reports`* ORDER BY `created_at` DESC |

---

## 9. Admin Module (Audit Logs)

**Module Overview:** Provides system administration tools including a comprehensive audit trail viewer for tracking all user actions across the system.

**File Location:** `modules/admin/audit_logs.php`

---

### 9.1 Viewing Audit Logs

**Step-by-Step Instructions:**

1. Navigate to **Admin** → **Audit Logs** (Admin role required).
2. Use the filter controls:
   - **Module Filter** — Select: Project, Budget, Procurement, Workforce, Auth, Reports, or All.
   - **Action Filter** — Select: CREATE, UPDATE, DELETE, LOGIN, LOGOUT, VIEW, EXPORT, APPROVE, REJECT.
   - **Search** — Enter text to search in details/descriptions.
   - **Date Range** — Set start and end dates.
3. View the filtered log entries in the table.
4. Click any row to view full details in a modal.

**Data Logic Callouts:**

| Display Element | Data Source |
|----------------|-------------|
| **Audit Log Entries** | Fetches from *`icmis_audit_logs`* with dynamic WHERE clauses based on filter selections |
| **Columns Displayed** | `log_id`, `user_name`, `action`, `module`, `details`, `record_id`, `ip_address`, `created_at` |
| **Pagination** | 10 records per page, AJAX-powered navigation |

---

### 9.2 Audit Log Entry Details

Each audit log entry contains:

| Field | Description |
|-------|-------------|
| `log_id` | Unique identifier |
| `user_id` | Reference to *`icmis_users`* |
| `user_name` | Cached display name |
| `action` | Action type (CREATE, UPDATE, DELETE, etc.) |
| `module` | System module (Project, Budget, Procurement, etc.) |
| `details` | Human-readable description |
| `record_id` | ID of affected record |
| `ip_address` | Client IP address |
| `user_agent` | Browser information |
| `created_at` | Timestamp |

---

## 10. Appendix: Database Schema Reference

### Core Tables

| Table Name | Purpose | Key Columns |
|------------|---------|-------------|
| *`icmis_users`* | User accounts | `user_id`, `full_name`, `email`, `password`, `role` |
| *`icmis_projects`* | Project records | `project_id`, `project_code`, `project_name`, `status`, `total_budget` |
| *`icmis_project_phases`* | Phase definitions | `phase_id`, `project_id`, `phase_name`, `start_date`, `end_date` |
| *`icmis_tasks`* | Task assignments | `task_id`, `project_id`, `phase_id`, `task_name`, `assigned_to_employee_id` |
| *`icmis_audit_logs`* | Audit trail | `log_id`, `user_id`, `action`, `module`, `details`, `created_at` |

### Budget Tables

| Table Name | Purpose | Key Columns |
|------------|---------|-------------|
| *`budget_proposals`* | Budget requests | `proposal_id`, `project_id`, `phase_id`, `code`, `total_amount`, `status` |
| *`budget_line_items`* | Proposal details | `line_item_id`, `proposal_id`, `category`, `item_name`, `quantity`, `unit_cost` |
| *`budget_expenses`* | Expense records | `expense_id`, `project_id`, `phase_id`, `category`, `amount`, `status` |

### Procurement Tables

| Table Name | Purpose | Key Columns |
|------------|---------|-------------|
| *`procurement_suppliers`* | Vendor registry | `supplier_id`, `supplier_name`, `contact_person`, `status` |
| *`procurement_purchase_orders`* | PO headers | `po_id`, `po_reference`, `project_id`, `supplier_id`, `total_amount`, `status` |
| *`procurement_purchase_order_items`* | PO line items | `po_item_id`, `po_id`, `item_name`, `quantity`, `unit_cost` |
| *`procurement_inventory`* | Stock levels | `item_id`, `item_name`, `quantity`, `unit_cost`, `project_id` |
| *`procurement_stock_in`* | Receiving records | `stock_in_id`, `po_id`, `item_id`, `quantity_received` |
| *`procurement_stock_out`* | Issuance records | `stock_out_id`, `item_id`, `quantity`, `issued_to_employee_id` |

### Workforce Tables

| Table Name | Purpose | Key Columns |
|------------|---------|-------------|
| *`workforce_employees`* | Employee master | `employee_id`, `employee_code`, `first_name`, `last_name`, `job_title_id`, `daily_rate` |
| *`workforce_job_titles`* | Position definitions | `job_title_id`, `title_name`, `department`, `default_daily_rate` |
| *`workforce_employee_groups`* | Team definitions | `group_id`, `group_code`, `group_name`, `group_leader_id` |
| *`workforce_group_memberships`* | Team assignments | `membership_id`, `employee_id`, `group_id`, `role_in_group` |
| *`workforce_assignments`* | Project assignments | `assignment_id`, `employee_id`, `project_id`, `phase_id`, `role`, `status` |
| *`workforce_attendance`* | Daily attendance | `attendance_id`, `employee_id`, `project_id`, `attendance_date`, `status` |
| *`workforce_payroll_periods`* | Pay periods | `period_id`, `start_date`, `end_date`, `status` |
| *`workforce_payroll`* | Payroll records | `payroll_id`, `employee_id`, `period_id`, `gross_pay`, `net_pay` |

---

### Foreign Key Relationships

| Child Table | Foreign Key | Parent Table | Cascade Rule |
|-------------|-------------|--------------|--------------|
| *`icmis_project_phases`* | `project_id` | *`icmis_projects`* | ON DELETE CASCADE |
| *`icmis_tasks`* | `project_id` | *`icmis_projects`* | ON DELETE CASCADE |
| *`icmis_tasks`* | `phase_id` | *`icmis_project_phases`* | ON DELETE SET NULL |
| *`budget_proposals`* | `project_id` | *`icmis_projects`* | ON DELETE CASCADE |
| *`budget_proposals`* | `phase_id` | *`icmis_project_phases`* | ON DELETE CASCADE |
| *`budget_line_items`* | `proposal_id` | *`budget_proposals`* | ON DELETE CASCADE |
| *`budget_expenses`* | `project_id` | *`icmis_projects`* | ON DELETE CASCADE |
| *`procurement_purchase_orders`* | `project_id` | *`icmis_projects`* | ON DELETE CASCADE |
| *`procurement_stock_in`* | `po_id` | *`procurement_purchase_orders`* | ON DELETE CASCADE |
| *`procurement_inventory`* | `project_id` | *`icmis_projects`* | ON DELETE CASCADE |
| *`workforce_assignments`* | `project_id` | *`icmis_projects`* | ON DELETE CASCADE |
| *`workforce_attendance`* | `project_id` | *`icmis_projects`* | ON DELETE CASCADE |

---

## Document Information

| Item | Value |
|------|-------|
| **Document Title** | ICMIS Technical User Manual |
| **Version** | 1.0 |
| **Created Date** | January 13, 2026 |
| **Author** | System Documentation Team |
| **System** | Integrated Construction Management Information System |

---

*End of Document*
