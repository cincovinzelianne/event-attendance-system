# Migration Plan: Event Attendance System -> Laravel + Node.js + MySQL

## 1. Target Architecture (Decide First)

1. Use **Laravel** for core backend (auth, admin/student modules, REST APIs, DB migrations, queues).
2. Use **Node.js** for one of these roles (pick one and stay consistent):
   - Real-time services (WebSocket, live attendance updates, notifications).
   - Frontend server/tooling (Vite-based SPA with React/Vue).
   - Background microservices (QR processing, integrations).
3. Keep **MySQL** as the primary database.
4. Run everything in one repo with clear boundaries:
   - `/apps/laravel-api`
   - `/apps/node-realtime` (or `/apps/web` if frontend)
   - `/infra` (Docker, deployment, CI/CD)

---

## 2. Project Audit and Feature Inventory

1. Create a migration inventory sheet (`docs/migration-inventory.xlsx` or markdown table).
2. Group existing files by domain:
   - Auth: `login.php`, `signin.php`, `signup.php`, `admin_signin.php`, `admin_signup.php`, `signout.php`
   - Student/Admin dashboards: `dashboard.php`, `admin_dashboard.php`, `analytics.php`
   - Events/attendance: `admin_create_event.php`, `admin_attendance.php`, `admin_attendance_dashboard.php`
   - QR: `qr_code.php`, `qr_scanner.php`, `admin_qr_scanner.php`, `force_generate_qr.php`
   - Notifications: `notifications.php`, `event_notification_status.php`, SQL notification scripts
   - Database scripts: all `*.sql`, `setup_database.php`, `fix_*.php`, `verify_*.php`
3. For each file, capture:
   - Current endpoint/page purpose
   - Input/output
   - DB tables touched
   - Dependencies (includes/config/assets)
   - Priority (`P0 critical`, `P1 important`, `P2 later`)
4. Freeze feature changes in old PHP app except critical fixes.

---

## 3. Environment and Tooling Setup

1. Install prerequisites:
   - PHP 8.2+
   - Composer
   - Node.js 20+
   - MySQL 8+
   - Docker + Docker Compose (recommended)
2. Create monorepo structure and initialize projects:
   - `laravel new apps/laravel-api`
   - `npm create vite@latest apps/web` (if SPA) or `npm init -y apps/node-realtime`
3. Add root-level dev tooling:
   - `.editorconfig`, `.env.example`, `README.md`
   - Optional: Turbo/Nx for monorepo scripts
4. Set up local services (`mysql`, `redis`, `mailhog`) via Docker Compose.

---

## 4. Database Migration Strategy (MySQL)

1. Reverse engineer current schema from existing SQL and live DB.
2. Create ERD and canonical schema in `docs/erd.md`.
3. Build Laravel migrations for all tables:
   - `users/admins/students`
   - `events`
   - `attendance`
   - `courses/departments/sections`
   - `notifications`
   - `qr` metadata fields
4. Preserve old IDs where possible to simplify data migration.
5. Create Laravel seeders/factories for test/dev data.
6. Write one-time data migration scripts:
   - Export old data (`mysqldump`/CSV)
   - Transform (fix nulls, normalize enums, resolve duplicates)
   - Import into new schema
7. Run validation checks:
   - Row counts by table
   - Referential integrity
   - Sample business-critical queries comparison

---

## 5. Laravel Core Buildout

1. Configure Laravel app:
   - DB, cache, queue, mail, timezone, storage
2. Implement authentication/authorization:
   - Laravel Breeze/Jetstream/Sanctum (API token auth)
   - Roles: `admin`, `student`
   - Policies/Gates for access control
3. Create domain modules (Controllers + Services + Repositories):
   - User/Profile
   - Events
   - Attendance
   - QR Code lifecycle
   - Notifications
4. Add API versioning (`/api/v1/...`).
5. Add request validation and API resources/transformers.
6. Add robust error handling and standardized response shape.

---

## 6. Node.js Scope Implementation

1. Choose Node responsibility clearly:
   - If real-time: implement Socket.IO service and Redis pub/sub.
   - If frontend: build SPA UI consuming Laravel APIs.
2. Integrate with Laravel:
   - Laravel emits events (attendance scanned, event started, notification created)
   - Node consumes events and broadcasts to clients
3. Add security between services:
   - Signed internal tokens
   - Rate limiting and CORS rules
4. Add health endpoints and structured logs.

---

## 7. Feature-by-Feature Migration Order

1. **Auth + user management** (P0)
2. **Event management** (P0)
3. **Attendance capture/locking** (P0)
4. **QR generation + scanner flow** (P0)
5. **Notifications** (P1)
6. **Analytics/reporting** (P1)
7. **Diagnostics/admin tools** (P2)

For each feature:
1. Document old behavior and edge cases.
2. Implement Laravel API + Node/UI counterpart.
3. Write tests (unit + feature/integration).
4. Run side-by-side verification with old app.
5. Mark feature as migrated in inventory.

---

## 8. QR and Scanner Migration Details

1. Standardize QR payload format (signed token + event/user IDs).
2. Rebuild QR generation in Laravel (or dedicated Node worker if needed).
3. Replace legacy QR scripts with API endpoints:
   - Generate QR
   - Regenerate missing QR
   - Verify uniqueness
4. Build scanner endpoint with anti-duplicate logic and attendance lock checks.
5. Add idempotency keys to prevent double attendance records.

---

## 9. Notifications and Background Jobs

1. Move notification creation to Laravel jobs/queues.
2. Use Node real-time service for live push (if selected scope).
3. Persist notification states in MySQL.
4. Add retry strategy + dead-letter handling for failed jobs.

---

## 10. Testing Strategy

1. Laravel tests:
   - Feature tests for all APIs
   - Unit tests for core services
2. Node tests:
   - Unit tests + integration tests for sockets/events
3. E2E tests (Playwright/Cypress):
   - Student login -> scan QR -> attendance reflected on admin dashboard
4. Data verification tests after migration:
   - Counts, constraints, consistency
5. Load/performance tests for peak attendance windows.

---

## 11. Security and Compliance

1. Enforce password hashing and secure session/token storage.
2. Add CSRF/CORS protections per app type.
3. Validate/sanitize all inputs.
4. Add audit logs for admin actions (event edits, manual attendance changes).
5. Ensure secrets are in env vars, never in source.

---

## 12. Deployment and CI/CD

1. Set up environments:
   - `dev`, `staging`, `production`
2. Build CI pipeline:
   - Lint -> test -> build -> migrate checks
3. Deployment sequence:
   - Deploy Laravel
   - Run DB migrations
   - Deploy Node service
   - Smoke test critical paths
4. Add rollback strategy:
   - DB backups
   - Tagged releases
   - Rollback scripts

---

## 13. Cutover Plan (Low Risk)

1. Run new stack in staging with production-like data.
2. Perform UAT with admins and selected students.
3. Do phased release:
   - Phase A: internal users only
   - Phase B: limited student cohort
   - Phase C: full rollout
4. Keep old PHP app read-only during final cutover window.
5. Switch traffic to new app.
6. Monitor logs, errors, DB load, and attendance success rate.

---

## 14. Post-Migration Cleanup

1. Archive legacy PHP scripts and SQL hotfix files.
2. Remove duplicate logic and temporary migration utilities.
3. Update documentation:
   - Architecture diagram
   - API docs (OpenAPI/Swagger)
   - Operations runbook
4. Conduct postmortem and backlog improvements.

---

## 15. Suggested Timeline (Example: 8-12 Weeks)

1. Week 1-2: Audit + schema design + environment setup
2. Week 3-4: Laravel auth/users/events + DB migration scripts
3. Week 5-6: Attendance + QR + core tests
4. Week 7-8: Node integration (real-time/UI) + notifications
5. Week 9: E2E tests + performance + security hardening
6. Week 10: Staging UAT + bug fixes
7. Week 11-12: Phased production rollout + cleanup

---

## 16. Definition of Done

1. All P0/P1 features mapped and working in new stack.
2. Data migration validated with signed-off reconciliation report.
3. Test suite green in CI with acceptable coverage.
4. Legacy app retired or archived.
5. Team handover complete with updated docs and runbooks.
