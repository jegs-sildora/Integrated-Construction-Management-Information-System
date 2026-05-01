# 🚀 Running ICMIS (Microservices Version)

This guide provides step-by-step instructions on how to set up and run the **Integrated Construction Management Information System (ICMIS)** in its new microservices architecture.

---

## 📋 Prerequisites

Before you begin, ensure you have the following installed on your machine:
1.  **Docker & Docker Compose**: To run the microservices and PostgreSQL databases.
2.  **PHP 8.2+**: For the monolithic frontend (running via Laragon, XAMPP, or local `php -S`).
3.  **Git**: To manage the source code.

---

## 🛠️ Step 1: Start the Microservices

The backend services and databases are orchestrated using Docker Compose.

1.  Open your terminal in the project root directory.
2.  Run the following command to build and start all 11 containers:
    ```bash
    docker-compose up -d --build
    ```
3.  Verify that all containers are running:
    ```bash
    docker ps
    ```
    You should see the following containers:
    - `gateway` (Port 8000)
    - `auth-service`, `project-service`, `budget-service`, `procurement-service`, `workforce-service`
    - `db_auth`, `db_project`, `db_budget`, `db_procurement`, `db_workforce`

---

## ⚙️ Step 2: Configure the Frontend

The monolithic frontend acts as a client to the API Gateway. You need to ensure it knows where to find the Gateway.

1.  Open `config/config.php`.
2.  Ensure `BASE_URL` matches your local server path (e.g., `http://localhost/icmis/`).
3.  Ensure `GATEWAY_URL` is set to point to the Docker Gateway:
    ```php
    define('GATEWAY_URL', 'http://localhost:8000/api/v1/');
    ```

---

## 🗄️ Step 3: Database Initialization

The PostgreSQL databases are automatically initialized when the Docker containers are first created, using the SQL scripts in `db/init/`.

*   **Auth DB**: Contains the initial user account.
*   **Project DB**: Prepared for construction project data.
*   **Budget, Procurement, & Workforce DBs**: Ready for operational data.

---

## 🌐 Step 4: Access the System

Once everything is running, you can access the system through your web browser.

*   **Frontend Interface**: `http://localhost/icmis/` (or your configured Laragon URL).
*   **API Gateway**: `http://localhost:8000/api/v1/` (Handles all microservice routing).

### 🔑 Default Credentials
You can log in using the pre-seeded admin account:
- **Email**: `john.doe@icmis.com`
- **Password**: `admin123`

---

## 🛑 Stopping the System

To shut down the microservices and stop the containers, run:
```bash
docker-compose down
```

To also remove the database volumes (warning: this will delete all data), run:
```bash
docker-compose down -v
```

---

## 🔍 Troubleshooting

*   **Connection Error**: If the frontend cannot connect to the Gateway, ensure that Docker is running and the `GATEWAY_URL` in `config.php` is correct.
*   **Database Issues**: If the database fails to initialize, check the logs using `docker-compose logs db_auth` (or any other DB service name).
*   **CORS Issues**: The Gateway is configured to handle internal routing; if you access it directly from a different domain, ensure your browser allows cross-origin requests or use the provided PHP Bridge.
