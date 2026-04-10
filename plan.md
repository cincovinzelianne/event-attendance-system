# Migration Plan: Event Attendance System -> Laravel (PHP 8.4) + Node.js 22 + Tailwind CSS + MySQL

## 1. Target Architecture (Decide First)

1. Use **Laravel (PHP 8.4)** for core backend and web app modules (auth, admin/student modules, REST APIs, DB migrations, queues).
2. Use **Node.js 22** for frontend tooling only (Vite build pipeline and asset tooling for the Laravel web app).
3. Use **Tailwind CSS** as the UI styling framework.
4. Keep **MySQL** as the primary database.
5. Scope this migration as a **web-based platform only** (no mobile app and no desktop app).
6. Keep everything in one repo with clear boundaries:
   - `/apps/laravel-web`
   - `/docs`
   - `/scripts`
   - `/database`

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
   - PHP 8.4+
   - Composer 2+
   - Node.js 22+
   - npm 10+
   - MySQL 8+
2. Create monorepo structure and initialize projects:
   - `laravel new apps/laravel-web`
   - Configure Vite and Tailwind CSS inside the Laravel app
3. Add root-level dev tooling:
   - `.editorconfig`, `.env.example`, `README.md`
   - Optional: Turbo/Nx for monorepo scripts
4. Set up local environment without Docker:
   - Run MySQL locally and create dedicated databases/users per environment
   - Configure Laravel `.env` values for local DB connection and app URL

---

## 4. File and Folder Organization (Web-Only Platform)

1. Organize the repository for maintainability and clear ownership:
   - `/apps/laravel-web` -> Main Laravel web application
   - `/docs` -> Architecture, migration inventory, ERD, runbooks
   - `/scripts` -> One-time migration/import/verification scripts
   - `/database` -> SQL snapshots, legacy SQL references, data mapping notes
2. Inside `/apps/laravel-web`, keep Laravel conventions and separate domains:
   - `/app/Http/Controllers/Admin`
   - `/app/Http/Controllers/Student`
   - `/app/Services`
   - `/app/Repositories`
   - `/resources/views/admin`
   - `/resources/views/student`
   - `/resources/css` (Tailwind entry/styles)
   - `/resources/js` (Vite-managed JS)
3. Move legacy PHP files into an archive area after migration (read-only):
   - `/legacy/php-pages`
   - `/legacy/sql-hotfixes`
4. Keep all deliverables web-focused:
   - Responsive browser UI only
   - No native Android/iOS codebase
   - No desktop client packaging

---

## 5. Database Migration Strategy (MySQL)

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

## 6. Laravel Core Buildout

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

## 7. Node.js + Tailwind Web Implementation

1. Use Node.js 22 strictly for frontend asset tooling (Vite build/dev server) within Laravel.
2. Set up Tailwind CSS and define shared design tokens/components for admin and student web pages.
3. Build responsive, browser-based pages that consume Laravel routes/APIs.
4. Keep deployment simple: Laravel app + compiled frontend assets + MySQL (no separate Node runtime service required in production unless later justified).

---

## 8. Feature-by-Feature Migration Order

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

## 9. QR and Scanner Migration Details

1. Standardize QR payload format (signed token + event/user IDs).
2. Rebuild QR generation in Laravel (or dedicated Node worker if needed).
3. Replace legacy QR scripts with API endpoints:
   - Generate QR
   - Regenerate missing QR
   - Verify uniqueness
4. Build scanner endpoint with anti-duplicate logic and attendance lock checks.
5. Add idempotency keys to prevent double attendance records.

---

## 10. Notifications and Background Jobs

1. Move notification creation to Laravel jobs/queues.
2. For web real-time UX, prefer Laravel-native options first (broadcasting/polling) before introducing extra services.
3. Persist notification states in MySQL.
4. Add retry strategy + dead-letter handling for failed jobs.

---

## 11. Testing Strategy

1. Laravel tests:
   - Feature tests for all APIs
   - Unit tests for core services
2. Frontend tooling/UI tests:
   - Unit tests for JS components/helpers when needed
   - Browser-focused integration tests for key pages
3. E2E tests (Playwright/Cypress):
   - Student login -> scan QR -> attendance reflected on admin dashboard
4. Data verification tests after migration:
   - Counts, constraints, consistency
5. Load/performance tests for peak attendance windows.

---

## 12. Security and Compliance

1. Enforce password hashing and secure session/token storage.
2. Add CSRF/CORS protections per app type.
3. Validate/sanitize all inputs.
4. Add audit logs for admin actions (event edits, manual attendance changes).
5. Ensure secrets are in env vars, never in source.

---

## 13. Deployment and CI/CD

1. Set up environments:
   - `dev`, `staging`, `production`
2. Build CI pipeline:
   - Lint -> test -> build -> migrate checks
3. Deployment sequence:
   - Deploy Laravel
   - Run DB migrations
   - Build and publish frontend assets
   - Smoke test critical paths
4. Add rollback strategy:
   - DB backups
   - Tagged releases
   - Rollback scripts

---

## 14. Cutover Plan (Low Risk)

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

## 15. Post-Migration Cleanup

1. Archive legacy PHP scripts and SQL hotfix files.
2. Remove duplicate logic and temporary migration utilities.
3. Update documentation:
   - Architecture diagram
   - API docs (OpenAPI/Swagger)
   - Operations runbook
4. Conduct postmortem and backlog improvements.

---

## 16. Suggested Timeline (Example: 8-12 Weeks)

1. Week 1-2: Audit + schema design + environment setup
2. Week 3-4: Laravel auth/users/events + DB migration scripts
3. Week 5-6: Attendance + QR + core tests
4. Week 7-8: Tailwind-based UI migration + notifications
5. Week 9: E2E tests + performance + security hardening
6. Week 10: Staging UAT + bug fixes
7. Week 11-12: Phased production rollout + cleanup

---

## 17. Definition of Done

1. All P0/P1 features mapped and working in new stack.
2. Data migration validated with signed-off reconciliation report.
3. Test suite green in CI with acceptable coverage.
4. Legacy app retired or archived.
5. Team handover complete with updated docs and runbooks.
6. Final platform is fully web-based with no mobile or desktop client dependency.
