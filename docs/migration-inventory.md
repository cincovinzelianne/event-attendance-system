# Migration Inventory

Use this table to map each legacy file/feature to its Laravel equivalent and migration status.

| Legacy File/Feature | Domain | Purpose | Inputs/Outputs | DB Tables | Priority | New Laravel Target | Status |
|---|---|---|---|---|---|---|---|
| login.php / signin.php / signup.php | Auth | Student authentication pages | Form input / session redirect | students, users | P0 | routes/web.php + Auth controllers + views | In Progress |
| admin_signin.php / admin_signup.php | Auth | Admin authentication pages | Form input / session redirect | admins, users | P0 | Admin auth controllers + views | Not Started |
| dashboard.php / admin_dashboard.php | Dashboard | Main dashboards | Session / rendered dashboard data | attendance, events, notifications | P0 | Dashboard controllers + Blade views | Not Started |
| admin_create_event.php | Events | Event creation | Form input / event record | events | P0 | EventController + request validation | Not Started |
| admin_attendance.php / admin_attendance_dashboard.php | Attendance | Attendance capture/reporting | Scanner input / reports | attendance, events, students | P0 | AttendanceController + services | Not Started |
| qr_code.php / qr_scanner.php / admin_qr_scanner.php | QR | QR generation and scan flow | Token payload / attendance write | qr fields, attendance, events | P0 | QR + Scanner controllers/services | Not Started |
| notifications.php / event_notification_status.php | Notifications | Notification list/status updates | User actions / status updates | notifications | P1 | Notification module + jobs | Not Started |
| analytics.php | Analytics | Attendance analytics and reporting | Date/filter inputs / charts | attendance, events, students | P1 | AnalyticsController + views | Not Started |
| setup_database.php + SQL fix/verify scripts | Diagnostics | DB setup/repair/diagnostics | Admin trigger / DB checks | multiple tables | P2 | Artisan commands in scripts/ | Not Started |

## Status Legend

- `Not Started`
- `In Progress`
- `Completed`
- `Blocked`
