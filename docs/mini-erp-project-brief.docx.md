# **Endterm Mini ERP System**

## *Microservices Architecture and Deployment*

# **1\. Project Overview**

Students are tasked with designing, developing, documenting, and deploying a mini Enterprise Resource Planning (ERP) system for a fictional business. The solution must be implemented using a microservices architecture and deployed to a publicly accessible environment.

This capstone project assesses three equally weighted competencies: business domain analysis, technical implementation, and professional documentation.

# **2\. Learning Outcomes**

Upon successful completion of this project, students will be able to:

1. Translate real-world business processes into well-defined software requirements.

2. Design and implement a distributed system using microservices principles.

3. Containerize and orchestrate multi-service applications for production environments.

4. Produce professional business and technical documentation.

5. Present, justify, and defend architectural and design decisions.

# **3\. Business Requirements**

Each team must develop a fictional business and build an ERP system tailored to its operations. The business should be specific enough that its processes, stakeholders, and data flows are clearly defined.

## **3.1 Suggested Industries**

Teams may select from the following or propose an alternative for instructor approval:

* Regional coffee shop chain or restaurant group

* Car rental or logistics company

* Pharmacy or healthcare clinic network

* Online bookstore or e-commerce retailer

* Fitness center, hotel, or membership-based service

## **3.2 Required Business Documentation**

The following components must be included in the business documentation deliverable:

* **Company Profile:** name, industry, size, locations, and mission statement.

* **Organizational Structure:** departments and roles that will interact with the system.

* **Core Business Processes:** a minimum of five (5) end-to-end processes documented with diagrams.

* **Pain Points:** specific operational problems the ERP is intended to resolve.

* **User Roles and Permissions:** a permissions matrix mapping roles to system capabilities.

*All technical decisions must be traceable to the documented business needs.*

# **4\. Technical Requirements**

## **4.1 Required Modules**

The system must implement a minimum of four (4) functional modules, each developed as an independent microservice, in addition to a required authentication service. Teams may select from the following:

* Inventory and Stock Management

* Sales and Order Management

* Purchasing and Supplier Management

* Customer Relationship Management (CRM)

* Human Resources and Employee Management

* Finance, Accounting, and Invoicing

* Reporting and Analytics

## **4.2 Architectural Requirements**

* **Microservices Architecture:** each module must be an independently deployable service with its own codebase and dedicated database. Shared databases across services are strictly prohibited.

* **API Gateway:** a single entry point must be implemented to route requests to the appropriate service.

* **Inter-Service Communication:** at least two services must communicate with one another using REST, gRPC, or a message broker (e.g., RabbitMQ, Kafka). The chosen method must be justified in the documentation.

* **Authentication and Authorization:** JWT, OAuth 2.0, or an equivalent standard must be used. All protected endpoints must enforce access control.

* **Containerization:** every service must be packaged as a Docker container.

* **Orchestration:** Docker Compose is the minimum requirement; Kubernetes deployment is considered a bonus.

* **Frontend Interface:** a functional user interface (web or mobile) that exercises the primary business flows.

* **Public Deployment:** the system must be accessible via a public URL on a cloud provider, VPS, or managed platform.

## **4.3 Technology Stack**

Teams are free to select any programming language or framework for each service (e.g., Node.js, Python, Java, Go, .NET, PHP). Polyglot architectures are permitted, provided each choice is justified in the technical documentation.

# **5\. Deliverables**

Teams must submit all of the following by the due date:

6. Source code hosted in a Git repository (link provided to the instructor).

7. Business documentation (PDF) covering all items specified in Section 3.2.

8. Technical documentation (PDF) including architecture diagrams, API specifications (Swagger/OpenAPI or Postman), data models, and justification of technology choices.

9. Deployment guide with instructions for running the system locally and notes on the production deployment.

10. Live deployment URL that remains accessible through the presentation date.

11. Demo video (5 to 10 minutes) walking through the main business processes in the user interface.

12. Final in-class presentation, followed by an architectural defense with the instructor.

# **6\. Evaluation Criteria**

The project will be graded out of 100 points according to the following rubric:

| Evaluation Criteria | Weight | Points |
| :---- | :---: | :---: |
| Business design and documentation | 15% | 15 |
| Architecture and service design | 20% | 20 |
| Implementation quality | 20% | 20 |
| Microservices best practices | 15% | 15 |
| Deployment | 10% | 10 |
| Technical documentation | 10% | 10 |
| Presentation and defense | 10% | 10 |
| **Total** | **100%** | **100** |

# **7\. Policies and Rules**

* **Team Composition:** \[3 to 4 students per team\].

* **Originality:** forking or repurposing existing ERP systems (e.g., Odoo, ERPNext, Dolibarr) is not permitted. These systems may be studied for reference only.

* **Use of AI Tools:** permitted, provided that students can explain and defend every portion of the codebase during evaluation.

* **Version Control:** commit history must reflect contributions from all team members. Concentration of commits under a single account will be flagged.

* **Academic Integrity:** submission of another team's work or any public repository as original work will result in a grade of zero.

# **8\. Recommendations for Success**

* Begin with the business domain. Teams that rush into implementation often build services that do not align with business needs.

* Keep service boundaries narrow and focused. Each microservice should have a single, well-defined responsibility.

* Do not share databases between services under any circumstances.

* Version your APIs from the beginning (e.g., /api/v1/).

* Implement logging across all services early in development.

* Deploy to the cloud within the first two weeks. Do not leave deployment until the final phase.

* Invest in clear architecture diagrams. A well-designed diagram communicates more effectively than lengthy prose.

# **9\. Submission Instructions**

All deliverables must be submitted through Google Classroom by the specified due date. The submission must include:

* Repository link (with access granted to the instructor).

* Live deployment URL.

* Business and technical documentation (PDF format).

* Demo video link.

* Team roster with an itemized breakdown of individual contributions.

*Late submissions will be handled in accordance with the course's standing late submission policy.*