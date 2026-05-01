# Execution Plan: ICMIS Product Requirements Document (PRD)

## Objective
Create the `icmis-prd.md` document detailing the transformation of the monolithic ICMIS system into a Native PHP Microservices architecture aligned with the mini-ERP capstone project brief, specifically utilizing Docker, PostgreSQL (separated databases), and deployed to Render.

## Background & Motivation
The current ICMIS is a functional, monolithic PHP application. The Capstone assignment requires a microservices architecture with isolated databases and cloud deployment. This PRD acts as the architectural blueprint to satisfy those academic requirements while retaining the core business logic of the application.

## Scope & Impact
We will create a comprehensive PRD reflecting the following strategic decisions:
- **Company Profile:** RKJ Construction
- **Tech Stack:** Native PHP 8.x for services, PostgreSQL for databases, Docker for containerization, Render for cloud deployment.
- **Inter-Service Communication:** Synchronous REST APIs (via PHP cURL).
- **API Gateway:** Native PHP API Gateway.

## Implementation Steps
1. Create the PRD file inside the documentation directory: `C:\laragon\www\icmis\docs\icmis-prd.md`.
2. Draft the contents structured precisely as requested by the project brief:
   - **1. Business Documentation:** Define RKJ Construction, its organizational structure, core processes, pain points, and user roles.
   - **2. Technical Requirements:** Define the 5 Microservices (Auth, Project, Budget, Procurement, Workforce), the API Gateway (Native PHP), and Inter-Service Communication (REST).
   - **3. Database Architecture:** Specify the separated PostgreSQL instances per service to strictly enforce the "no shared databases" rule.
   - **4. Deployment Strategy:** Document the Docker Compose setup for local development and the Render web services + managed PostgreSQL approach for production.

## Alternatives Considered
- *Laravel/Frameworks:* Rejected to maintain the "native PHP" requirement requested by the user.
- *Message Brokers (RabbitMQ):* Rejected in favor of REST APIs due to the complexity of running daemon workers in native PHP environments.
- *Nginx API Gateway:* Rejected in favor of a Native PHP Gateway for simplicity and consistency.

## Verification
- Ensure the resulting PRD strictly adheres to the requirements in `@docs/mini-erp-project-brief.docx.md` and `@docs/BSIT_Lab_Manual_Laravel_Render_Basic.docx.md`.
- Ensure the tech stack aligns perfectly with the user's constraints (Native PHP, Docker, PostgreSQL, Render).