# 🚀 ICMIS - Microservices Implementation Phases

#### Phase 1: Environment & Database Segregation
**Goal:** Establish the local containerized environment and break apart the monolithic database.
1. **Docker Setup:** Create a `docker-compose.yml` file in the root directory to orchestrate the local environment.
2. **Database Splitting:** Provision 5 isolated PostgreSQL containers (`db_auth`, `db_project`, `db_budget`, `db_procurement`, `db_workforce`).
3. **Schema Migration:** Refactor the existing `icmis_db.sql` (MySQL) into 5 separate PostgreSQL schemas. Remove any hard foreign-key constraints that cross service boundaries.
4. **Directory Structure:** Create separate root folders for each service (e.g., `/services/gateway`, `/services/auth`, `/services/project`), each containing its own lightweight PHP `Dockerfile`.

#### Phase 2: Auth Service & API Gateway
**Goal:** Secure the perimeter, establish routing, and implement stateless authentication.
1. **Auth Service (`/services/auth`):** Migrate user data and roles to `db_auth`. Implement JWT (JSON Web Token) generation for successful logins and create a `/validate` endpoint.
2. **API Gateway (`/services/gateway`):** Build a native PHP routing script (`index.php`) that acts as the single entry point. It will intercept requests, validate the JWT internally via the Auth Service, and use `cURL` to forward requests to the target microservice.
3. **Frontend Updates:** Refactor the frontend login logic to request and store the JWT and attach it as a Bearer token to all subsequent API requests.

#### Phase 3: Core Domain Extraction (Project Service)
**Goal:** Extract the primary business domain that all other services rely on.
1. **Project Service (`/services/project`):** Migrate Projects, Phases, and Tasks logic to use `db_project`.
2. **REST Endpoints:** Build native PHP REST API endpoints for all project operations.
3. **Gateway Routing:** Configure the API Gateway to route all `/api/project/*` requests to the internal Project Service container.
4. **Frontend Integration:** Update the Project Management UI to consume the new REST endpoints.

#### Phase 4: Dependent Services (Budget & Procurement)
**Goal:** Extract financial and supply chain logic, and implement inter-service communication.
1. **Budget Service (`/services/budget`):** Migrate Proposals and Expenses to `db_budget`. 
   - *Inter-service REST:* When creating an expense, the Budget Service must make an internal cURL request to the Project Service to verify that the target `project_id` and `phase_id` actually exist.
2. **Procurement Service (`/services/procurement`):** Migrate Suppliers, POs, and Inventory to `db_procurement`.
3. **Gateway & Frontend:** Route `/api/budget/*` and `/api/procurement/*` through the gateway and connect the respective UI components.

#### Phase 5: Workforce Service & Local Testing
**Goal:** Extract HR/Payroll logic and finalize the local system.
1. **Workforce Service (`/services/workforce`):** Migrate Employees, Attendance, and Payroll to `db_workforce`. 
2. **Audit Logging:** Refactor the centralized `Logger.php`. Each service should now write its own audit logs to its local database to maintain strict isolation.
3. **End-to-End Local Test:** Run `docker-compose up -d`. Verify the entire user flow works seamlessly through the API Gateway.

#### Phase 6: Production Deployment (Render.com)
**Goal:** Deploy the fully containerized system to the cloud.
1. **Managed Databases:** Provision 5 free managed PostgreSQL instances on Render and run the schema migrations for each.
2. **Web Services:** Create 6 separate Web Services on Render linked to your GitHub repository (Gateway, Auth, Project, Budget, Procurement, Workforce).
3. **Environment Configuration:** Set the `DATABASE_URL` environment variable for each respective service. Configure the Gateway with the internal Render URLs of the other services.
4. **Go-Live:** Perform final testing using the public Gateway URL.