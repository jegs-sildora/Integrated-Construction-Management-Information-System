# 🏗️ ICMIS - Integrated Construction Management Information System

## Project Overview
ICMIS is a comprehensive construction management platform designed for end-to-end project lifecycle management. It is currently in a **transitional phase**, migrating from a **modular PHP/MySQL monolith** to a **distributed Microservices Architecture** using PHP 8.x, PostgreSQL, and Docker.

- **Primary Goal**: Centralize construction project tracking, budgeting, procurement, and workforce management.
- **Current State**: Hybrid system containing both legacy `modules/` (Monolith) and new `services/` (Microservices).
- **Architecture Philosophy**: Domain-Driven Design (DDD) with strict database isolation per service in the new architecture.

## 🛠️ Technology Stack
- **Backend**: PHP 8.4+ (Native)
- **Database**: 
  - **Legacy**: MySQL 8.x (InnoDB)
  - **Microservices**: PostgreSQL 16+
- **Frontend**: HTML5, Tailwind CSS v4, Vanilla JS (ES6+)
- **Visualization**: Chart.js, Frappe Gantt
- **DevOps**: Docker, Docker Compose, Laragon (Legacy support)
- **Deployment**: Render.com (Target platform)

## 📁 Directory Structure
- `/config`: Global configuration and legacy DB connection (`database.php` now contains a Mock for migration).
- `/core`: Shared system logic (Context management, Audit Logger).
- `/includes`: Shared UI components (Sidebar, Header, Toasts).
- `/modules`: **Legacy Monolith Modules** (Admin, Auth, Project, Budget, Procurement, Workforce, Reports).
- `/services`: **New Microservices** (Gateway, Auth, Project, Budget, Procurement, Workforce, Reports).
- `/db`: Database initialization scripts (`init/`) and seeds (`seed/`).
- `/docs`: Detailed project documentation, PRDs, and implementation plans.
- `/assets`: Static assets (Images, Icons).

## 🚀 Building and Running
The project uses Docker Compose for local development.

### Start the Environment
```bash
docker-compose up -d
```
This starts:
- `frontend`: The main UI (Port 8080)
- `gateway`: API Gateway (Port 8000)
- `auth-service`, `project-service`, etc.: Backend microservices
- `db_auth`, `db_project`, etc.: PostgreSQL instances

### Database Setup
Databases are automatically initialized via Docker entrypoint scripts in `/db/init/*.sql`.
- **Legacy Schema**: `icmis_db.sql` (MySQL)
- **Service Schemas**: Located in `db/init/` (PostgreSQL)

## ⚖️ Development Conventions
- **Migration Strategy**: New features MUST be implemented in `services/`. Legacy `modules/` are only for maintenance and reference.
- **Database Access**: 
  - **NEVER** share databases between services.
  - Use `core/ApiHelper.php` (or equivalent in services) to communicate via the API Gateway.
- **Audit Logging**: Use the centralized `Logger` class for all sensitive operations.
- **Coding Style**: Adhere to **PSR-12** for PHP.
- **UI/UX**: 
  - Use **Tailwind CSS v4** utility classes.
  - Icons: Prefer **Lucide Icons**.
  - Interactive elements should use the `toast.php` component for feedback.
- **Security**: 
  - Always use prepared statements for SQL.
  - Use `password_hash()` (bcrypt) for credentials.
  - JWT is used for service-to-service and client-to-service auth in the new architecture.

## 🔑 Key Files
- `docs/icmis-prd.md`: The "Source of Truth" for system requirements.
- `icmis_architecture.txt`: Detailed breakdown of file responsibilities.
- `docker-compose.yml`: Local environment orchestration.
- `config/config.php`: Global constants and path definitions.
- `core/Logger.php`: Audit trail mechanism.
