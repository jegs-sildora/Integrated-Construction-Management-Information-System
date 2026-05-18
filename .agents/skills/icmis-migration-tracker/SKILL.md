---
name: icmis-migration-tracker
description: Track and manage the migration of ICMIS modules from the legacy PHP monolith to microservices. Use when the user asks about migration progress, parity gaps, or which module to migrate next.
---

# ICMIS Migration Tracker

This skill provides an automated way to audit and track the migration progress from the legacy `modules/` (monolith) to the new `services/` (microservices) architecture.

## Workflow

1. **Audit Phase**: Run the `migration_audit.php` script to get a real-time snapshot of the migration state.
2. **Analysis Phase**: Compare the `parity` report to identify which API endpoints are missing in the new services.
3. **Planning Phase**: Refer to `references/migration-guidelines.md` for the standard porting procedure.
4. **Implementation Phase**: Use the findings to port logic from `modules/` to `services/`.

## Bundled Resources

### Scripts

- **migration_audit.php**: Scans the repository and outputs a JSON report of the migration state.
  - Usage: `php .agents/skills/icmis-migration-tracker/scripts/migration_audit.php`

### References

- **migration-guidelines.md**: Comprehensive guide on mapping legacy features to microservices and the standard migration checklist.

## Audit Interpretation

The audit script classifies modules into three states:

- **Pending**: Only exists in `modules/` or has no ported API files.
- **In Progress**: Service exists in `services/`, but some legacy API files have not been ported to `v1`.
- **Completed**: All legacy API files have matching counterparts in the service's `v1` API.

**Priority Recommendation:**
- Target modules with high "legacy_api_files" but low "service_api_files".
- Prioritize `auth` and `project` as they are core to system stability.
- `admin` is currently the only module completely missing its service counterpart.
