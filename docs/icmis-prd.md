# 🏗️ ICMIS - Product Requirements Document (PRD)

## 1. Project Overview
**RKJ Construction** is a regional construction and contracting company specializing in medium-to-large scale infrastructure and residential projects. As the company scales, the need for an integrated, modular, and cloud-native Enterprise Resource Planning (ERP) system has become critical to maintain operational efficiency and financial transparency.

The **Integrated Construction Management Information System (ICMIS)** will be transformed from a monolithic application into a distributed **Microservices Architecture** to ensure high availability, independent scalability, and strictly isolated data management across business units.

---

## 2. Business Documentation

### 2.1 Company Profile
- **Name:** RKJ Construction
- **Industry:** Construction & Infrastructure
- **Size:** Medium (200+ employees, multiple concurrent project sites)
- **Mission Statement:** To deliver high-quality construction projects with integrity, transparency, and technical excellence through innovative management systems.

### 2.2 Organizational Structure
- **Administration:** System oversight, audit compliance, and user management.
- **Project Management:** Planning, site oversight, and phase-based execution.
- **Budget & Finance:** Cost control, expense auditing, and financial reporting.
- **Procurement:** Supplier relationships, purchasing, and inventory control.
- **Workforce/HR:** Labor management, attendance tracking, and payroll processing.

### 2.3 Core Business Processes
1. **Project Lifecycle Management:** Defining project scopes, breaking them into phases (e.g., Mobilization, Structural, MEPFS, Finishing), and tracking task completion.
2. **Budgeting & Cost Control:** Creating budget proposals for project phases and tracking actual expenses against approved limits.
3. **Procurement Workflow:** Managing a registry of approved suppliers, issuing Purchase Orders (POs), and receiving/issuing materials.
4. **Workforce Attendance & Payroll:** Tracking daily worker attendance on-site and processing payroll with standard Philippine government deductions.
5. **Integrated Reporting:** Consolidating data from all departments for stakeholder review and decision-making.

### 2.4 Pain Points
- **Siloed Data:** Difficulty in getting a real-time view of inventory across multiple sites.
- **Manual Auditing:** Time-consuming manual verification of expenses against budget proposals.
- **Payroll Delays:** Complex manual calculations for worker attendance across different projects.
- **Scaling Issues:** The existing monolithic system struggles with concurrent updates from multiple remote sites.

### 2.5 User Roles & Permissions
| Role | Capabilities |
|------|--------------|
| **Admin** | Full system access, audit trail viewing, and user management. |
| **Manager** | Project oversight, phase approvals, and high-level reporting. |
| **Budget Officer** | Full access to the Budget module; can approve proposals and track expenses. |
| **Procurement Officer** | Management of suppliers, POs, and inventory stock levels. |
| **Staff** | Basic entry of attendance, tasks, and inventory transactions. |

---

## 3. Technical Requirements

### 3.1 Microservices Architecture
The system is decomposed into five (5) independent functional modules, each acting as a standalone microservice:
1. **Auth Service:** Centralized JWT-based authentication and user authorization.
2. **Project Service:** Manages the core project/phase/task domain.
3. **Budget Service:** Handles financial proposals and actual expenditure tracking.
4. **Procurement Service:** Manages suppliers, purchase orders, and inventory.
5. **Workforce Service:** Manages employees, attendance, and payroll logic.

### 3.2 Architectural Requirements
- **Independent Deployment:** Each service has its own codebase and is packaged as a Docker container.
- **Database Architecture:**
  - **Enterprise Design:** Isolated data management with dedicated databases per service.
  - **MVP/Capstone Implementation:** Optimized for cost and simplicity on free-tier platforms using a **Shared Database Microservices** pattern. All services connect to a single PostgreSQL instance while maintaining logical domain boundaries.
- **API Gateway:** A **Native PHP Gateway** serves as the single entry point, routing requests to the appropriate backend microservice via cURL.
- **Inter-Service Communication:** Synchronous **REST APIs** are used for data exchange between services (e.g., Workforce Service querying Project Service for valid phase IDs).
- **Authentication:** JWT (JSON Web Tokens) are used for secure, stateless communication between the frontend, gateway, and backend services.

### 3.3 Technology Stack
| Component | Technology | Rationale |
|-----------|------------|-----------|
| **Backend Services** | Native PHP 8.x | High developer familiarity and minimal overhead for micro-logic. |
| **Databases** | PostgreSQL 16+ | Industry standard for relational data; superior handling of concurrent transactions. |
| **API Gateway** | Native PHP | Lightweight routing logic without the overhead of heavy frameworks. |
| **Containerization** | Docker | Ensures environment consistency across local and production. |
| **Orchestration** | Docker Compose | Local orchestration of multi-service networking. |
| **Frontend** | Native PHP/JS | Responsive UI consuming the microservices via the Gateway. |
| **Cloud Platform** | Render.com | Managed hosting with native Docker support and PostgreSQL instances. |

---

## 4. Database Design (PostgreSQL)
Each service manages its own set of tables within the shared database. Cross-service data is still handled via API requests, ensuring architectural integrity.

- **`auth_tables`**: Users, roles, permissions, audit logs.
- **`project_tables`**: Projects, phases, tasks.
- **`budget_tables`**: Proposals, line items, expenses.
- **`procurement_tables`**: Suppliers, purchase orders, inventory, stock movements.
- **`workforce_tables`**: Employees, groups, assignments, attendance, payroll records.

---

## 5. Deployment Strategy

### 5.1 Local Development (Docker Compose)
A `docker-compose.yml` will orchestrate the following:
- `gateway-service` (Port 80)
- `auth-service` (Internal)
- `project-service` (Internal)
- `budget-service` (Internal)
- `procurement-service` (Internal)
- `workforce-service` (Internal)
- 5x PostgreSQL containers (one for each service)

### 5.2 Production Deployment (Render)
1. **Web Services:** Each PHP microservice is deployed as a Render Web Service using its respective `Dockerfile`.
2. **Managed Database:** One central PostgreSQL instance is provisioned.
3. **Environment Variables:**
   - `DATABASE_URL`: Shared connection string for the central DB, used by all services.
   - `JWT_SECRET`: Shared secret for token validation across the ecosystem.
   - `SERVICE_URLS`: Internal Render hostnames for inter-service communication via the Gateway.

---

## 6. Verification Plan
- **Service Isolation:** Verify that a database failure in the `Budget Service` does not prevent the `Project Service` from operating.
- **Gateway Routing:** Confirm that the Native PHP Gateway correctly routes `/api/v1/auth/*` and `/api/v1/project/*` to the correct internal endpoints.
- **Inter-service REST:** Verify that the `Budget Service` can successfully retrieve project data from the `Project Service` via internal REST calls.
- **Render Deployment:** Successful live deployment with all 5 services communicating via internal Render networking.
