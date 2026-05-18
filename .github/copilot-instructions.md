# Copilot Instructions for ICMIS

## Build, test, and lint commands

- Start stack: `docker-compose up -d --build`
- Check running services: `docker ps`
- Logs: `docker-compose logs <service-name>`
- Stop stack: `docker-compose down`
- Stop and remove DB volumes: `docker-compose down -v`

ICMIS app code (`modules/`, `services/`) currently has no repository-level automated test/lint runner (`composer.json`, `package.json`, `phpunit.xml`, `phpcs`, `phpstan` are not present).  
Single-test command: **N/A** until a test framework is added.

## High-level architecture

ICMIS is a hybrid system in migration:

- UI/legacy compatibility lives in repo root + `modules/` (pages and AJAX bridge endpoints).
- Domain logic lives in microservices under `services/` (`auth`, `project`, `budget`, `procurement`, `workforce`, `reports`), each with isolated PostgreSQL access via its own `Database.php`.
- Gateway routing is centralized in `services/gateway/index.php` (`/api/v1/{service}/{endpoint}`), including JWT validation through `auth/api/v1/validate.php`.
- Main runtime path: `UI -> modules/*/api bridge -> core/ApiHelper.php -> gateway -> services/*/api/v1/*.php -> service DB`.
- Login flow: `index.php` (CSRF token) -> `modules/auth/api/login_process.php` -> `auth/login`; JWT is stored in session and auto-sent by `ApiHelper`.

## Key conventions

- Implement new behavior in `services/` first; only use `modules/` for compatibility and UI bridge updates.
- Treat `config/database.php` as migration compatibility only (`MockMysqli`); avoid new direct legacy DB feature logic and use gateway/API calls instead.
- Keep `modules/*/api/*.php` response contracts stable for existing frontend JS. If service payloads change, adapt bridge files (example: manager-name enrichment in `modules/project/api/projects.php`).
- Preserve `project_id` context across pages and module links via `core/ProjectContext.php`, module `project_context.php` wrappers, and sidebar URL propagation.
- Log sensitive actions through centralized logging:
  - Frontend/bridge-side: `core/Logger.php`
  - Service-side CRUD/auth actions: `services/*/Logger.php` and `Logger::*` calls in API handlers
- Keep service boundaries strict: no cross-service DB sharing; cross-domain data must flow through gateway/API calls.
