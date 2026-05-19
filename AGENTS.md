# Repository Guidelines

## Project Structure & Module Organization

ICMIS is a PHP 8 construction management system in a hybrid migration state. Legacy UI entrypoints live at the repository root, especially `index.php` and `dashboard.php`. Shared configuration and helpers are in `config/`, `core/`, and `includes/`. Feature UI pages and bridge endpoints live under `modules/<domain>/`, while gateway-backed domain services live under `services/<domain>/` for `auth`, `project`, `budget`, `procurement`, `workforce`, `reports`, and `admin`. Static assets are in `assets/`; database initialization and seed SQL are in `db/`; deployment and architecture notes are in `docs/`.

## Build, Test, and Development Commands

- `docker-compose up -d --build`: build and start the frontend, gateway, services, BrowserSync, and PostgreSQL.
- `docker-compose logs <service-name>`: inspect a specific service, for example `docker-compose logs gateway`.
- `docker-compose down`: stop the local stack.
- `docker-compose down -v`: stop the stack and remove database volumes.
- `php test_full_local.php`: run the local smoke flow against `http://localhost:8000` after Docker services are healthy.
- `php test_phase1.php` through `php test_phase5.php`: run phase smoke tests, mostly intended for gateway/API validation.

There is currently no repository-level `composer.json`, `package.json`, `phpunit.xml`, or lint runner.

## Coding Style & Naming Conventions

Follow PSR-12 for PHP: four-space indentation, clear function names, and readable control flow. Keep filenames and endpoint names consistent with existing module patterns, such as `modules/project/api/projects.php` and `services/project/api/v1/*.php`. Prefer service-side changes in `services/` first, then adapt `modules/*/api` bridge files only when the frontend contract requires it. Do not add new direct legacy database feature logic in `config/database.php`.

## Testing Guidelines

Use smoke tests for behavioral verification until a formal test framework is added. Start the Docker stack before local tests, then run `php test_full_local.php` for an end-to-end check. For production Render checks, use the phase scripts only when external calls are intended. Name new ad hoc tests with a clear `test_<scope>.php` pattern and document required URLs or credentials at the top of the file.

## Commit & Pull Request Guidelines

Recent history uses Conventional Commit-style prefixes, for example `fix: resolve asset 404s` and `feat: add migration scripts for audit logging`. Keep commits focused and imperative. Pull requests should describe the changed domain, list verification commands, note database or environment changes, and include screenshots for visible UI updates.

## Security & Configuration Tips

Copy `.env.example` to `.env` for local secrets and never commit credentials. Preserve JWT/session handling through the gateway and `core/ApiHelper.php`. Log sensitive CRUD and auth actions with the existing `Logger` classes.
