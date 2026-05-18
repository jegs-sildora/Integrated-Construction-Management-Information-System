# Migration Guidelines for ICMIS

## Legacy vs. Microservice Mapping

| Legacy Feature | Monolith Path | Microservice Path | Database |
|----------------|---------------|-------------------|----------|
| API Logic | `modules/{name}/api/*.php` | `services/{name}/api/v1/*.php` | PostgreSQL |
| UI Views | `modules/{name}/*.php` | `index.php` (via Gateway) | N/A |
| Components | `modules/{name}/components/` | `includes/` (shared) | N/A |

## Migration Steps

1. **Service Initialization**: Ensure `services/{name}/Dockerfile` and `services/{name}/Database.php` exist.
2. **Database Porting**: 
    - Convert MySQL schema in `icmis_db.sql` to PostgreSQL in `db/init/{name}.sql`.
    - Update `Database.php` in the service to use `PgSql` connection.
3. **API Porting**:
    - Move logic from `modules/{name}/api/*.php` to `services/{name}/api/v1/*.php`.
    - Wrap responses in standard JSON format.
    - Implement authentication checks using `JwtUtils`.
4. **Gateway Registration**:
    - Ensure the API Gateway (`services/gateway`) routes requests to the new service.
5. **UI Update**:
    - Update frontend PHP files to call the API Gateway instead of legacy include paths.
    - Use `core/ApiHelper.php` for service communication.

## Parity Checklist

- [ ] All API endpoints ported to `v1`.
- [ ] Database interactions moved to PostgreSQL.
- [ ] Docker configuration verified.
- [ ] Inter-service communication via Gateway.
- [ ] Audit logging via `Logger.php`.
