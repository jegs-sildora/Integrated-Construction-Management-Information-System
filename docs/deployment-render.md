# 🚀 Deployment Guide: ICMIS on Render (Free Tier)

This guide explains how to deploy the microservices-based ICMIS application to Render using a **Shared Database Architecture** to stay within the Free Tier limits.

---

## 1. Local Development (Docker)

To run the system locally, ensure you have **Docker Desktop** installed.

1. Clone the repository.
2. Open a terminal in the root directory.
3. Start the environment:
   ```bash
   docker-compose up -d
   ```
4. Access the system at:
  **Frontend:** [http://localhost:8080](http://localhost:8080)
  **API Gateway:** [http://localhost:8000](http://localhost:8000)

---

## 2. Production Deployment (Render.com)

### Phase 1: Provision the Shared Database
Create **ONE** database instance that all microservices will share.

1. Go to **New +** -> **PostgreSQL**.
2. **Name:** `icmis-db-main`
3. **Region:** `Oregon (US West)` (⚠️ Crucial: All services must be in the same region).
4. **PostgreSQL Version:** `16`.
5. Click **Create Database**.
6. Once ready, copy the **Internal Database URL**.
  *Example:* `postgresql://user:pass@dpg-xxx-a/icmis_db`

### Phase 2: Deploy Backend Microservices
Repeat these steps for each folder in `/services` (**except** `gateway`).

1. Go to **New +** -> **Web Service**.
2. Connect your GitHub repository.
3. Configure the service:
  **Name:** `icmis-{service-name}` (e.g., `icmis-auth`, `icmis-project`).
  **Runtime:** `Docker`.
  **Region:** `Oregon (US West)`.
  **Root Directory:** `services/{name}` (e.g., `services/auth`).
  **Dockerfile Path:** `.` (Uses the Dockerfile inside that folder).
  **Instance Type:** `Free`.
4. Add **Environment Variable**:
  **Key:** `
   DATABASE_URL`
  **Value:** (Paste the **Internal Database URL** from Phase 1).
5. Click **Create Web Service**.
6. **Note:** Once created, copy the **Internal Hostname** for each (e.g., `http://icmis-auth`
).

### Phase 3: Deploy the API Gateway
1. Go to **New +** -> **Web Service**.
2. **Name:** `icmis-gateway`.
3. **Runtime:** `Docker`.
4. **Root Directory:** `services/gateway`.
5. Add **Environment Variables** for all services:
  `
   AUTH_SERVICE_URL`: `http://icmis-auth`

  `PROJECT_SERVICE_URL`: `http://icmis-project`
  `BUDGET_SERVICE_URL`: `http://icmis-budget`
  `PROCUREMENT_SERVICE_URL`: `http://icmis-procurement`
  `WORKFORCE_SERVICE_URL`: `http://icmis-workforce`
  `REPORTS_SERVICE_URL`: `http://icmis-reports`
  `ADMIN_SERVICE_URL`: `http://icmis-admin`
6. Click **Create**.
7. Copy the **Public URL** (e.g., `https://icmis-gateway.onrender.com`).

### Phase 4: Deploy the Frontend (Main UI)
1. Go to **New +** -> **Web Service**.
2. **Name:** `icmis-frontend`.
3. **Runtime:** `Docker`.
4. **Root Directory:** (Leave **EMPTY**).
5. **Dockerfile Path:** `./services/gateway/Dockerfile`.
6. Add **Environment Variable**:
  **Key:** `GATEWAY_HOST`
  **Value:** (Paste the **Public URL** of the gateway from Phase 3).
7. Click **Create**.

---

## 3. Database Initialization (One-Time Setup)
Since you are using a managed database on Render, you must run the SQL scripts manually once.

1. Connect to your Render DB using a tool like **DBeaver** or **pgAdmin** using the **External Database URL**.
2. Execute the scripts in `db/init/` and `db/seed/` in order.

## Phase 2: Microservices Deployment Checklist

### Deployment Settings for All Services:Language/Runtime: DockerRegion: Oregon (US West)Dockerfile Path: .Instance Type: Free

### 1. Auth ServiceName: icmis-authRoot Directory: services/authEnvironment Variables:
DATABASE_URL: postgresql://icmis_db_main_user:EsOtnq65XnAEFjhBubkvJlzfaFqTGv7b@dpg-d85ord0js32c73all1eg-a/icmis_db_main
JWT_SECRET: [Random-Secret-String]

### 2. Project ServiceName: icmis-projectRoot Directory: services/projectEnvironment Variables:
DATABASE_URL: postgresql://icmis_db_main_user:EsOtnq65XnAEFjhBubkvJlzfaFqTGv7b@dpg-d85ord0js32c73all1eg-a/icmis_db_main
AUTH_SERVICE_URL: http://icmis-auth
WORKFORCE_SERVICE_URL: http://icmis-workforce

### 3. Budget ServiceName: icmis-budgetRoot Directory: services/budgetEnvironment Variables:
DATABASE_URL: postgresql://icmis_db_main_user:EsOtnq65XnAEFjhBubkvJlzfaFqTGv7b@dpg-d85ord0js32c73all1eg-a/icmis_db_main
AUTH_SERVICE_URL: http://icmis-auth
PROJECT_SERVICE_URL: http://icmis-project

### 4. Procurement ServiceName: icmis-procurementRoot Directory: services/procurementEnvironment Variables:
DATABASE_URL: postgresql://icmis_db_main_user:EsOtnq65XnAEFjhBubkvJlzfaFqTGv7b@dpg-d85ord0js32c73all1eg-a/icmis_db_main
AUTH_SERVICE_URL: http://icmis-auth

### 5. Workforce ServiceName: icmis-workforceRoot Directory: services/workforceEnvironment Variables:
DATABASE_URL: postgresql://icmis_db_main_user:EsOtnq65XnAEFjhBubkvJlzfaFqTGv7b@dpg-d85ord0js32c73all1eg-a/icmis_db_main
AUTH_SERVICE_URL: http://icmis-auth
PROJECT_SERVICE_URL: http://icmis-project

### 6. Reports ServiceName: icmis-reportsRoot Directory: services/reportsEnvironment Variables:
DATABASE_URL: postgresql://icmis_db_main_user:EsOtnq65XnAEFjhBubkvJlzfaFqTGv7b@dpg-d85ord0js32c73all1eg-a/icmis_db_main
AUTH_SERVICE_URL: http://icmis-auth
PROJECT_SERVICE_URL: http://icmis-project
BUDGET_SERVICE_URL: http://icmis-budget
PROCUREMENT_SERVICE_URL: http://icmis-procurement
WORKFORCE_SERVICE_URL: http://icmis-workforce
REPORTS_SERVICE_URL: http://icmis-reports
ADMIN_SERVICE_URL: http://icmis-admin

### 7. Admin ServiceName: icmis-adminRoot Directory: services/admin Environment Variables:
DATABASE_URL: postgresql://icmis_db_main_user:EsOtnq65XnAEFjhBubkvJlzfaFqTGv7b@dpg-d85ord0js32c73all1eg-a/icmis_db_main


PROJECT_SERVICE_URL: http://icmis-project
AUTH_SERVICE_URL: http://icmis-auth

