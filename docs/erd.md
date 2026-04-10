# ERD and Canonical Schema Notes

This document is the source of truth for the target MySQL schema used by Laravel migrations.

## Canonical Tables

### Identity and Access

- `users`
	- Core auth table for both admin and student accounts.
	- `role` column (`admin` or `student`) replaces separate legacy auth tables.
- `student_profiles`
	- Extends `users` for student-specific data (`student_id`, `course`, `year_level`, QR fields).

### Academic Structure

- `courses`
	- Stores department, course code/name, major, and allowed year levels.

### Event and Attendance

- `events`
	- Core event records, schedule, location, and attendance lock state.
- `event_departments`
	- Department filters for event eligibility.
- `attendances`
	- Student check-ins with anti-duplicate unique key (`event_id`, `user_id`).

### Notifications and Operations

- `notifications`
	- Per-user persisted notifications with read status.
- `activity_logs`
	- Auditable action trail equivalent to legacy analytics usage.
- `jobs` / `failed_jobs`
	- Queue infrastructure for async notification fan-out and retries.

## Cardinality

- `users (1) -> (0..1) student_profiles`
- `courses (1) -> (0..n) student_profiles`
- `users (1) -> (0..n) events` through `events.created_by`
- `events (1) -> (0..n) event_departments`
- `events (1) -> (0..n) attendances`
- `users (1) -> (0..n) attendances`
- `users (1) -> (0..n) notifications`
- `users (1) -> (0..n) activity_logs`

## Legacy-to-New Mapping

- `students` + `admins` -> `users` (+ `student_profiles` for student-only fields)
- `attendance` -> `attendances`
- `analytics` -> `activity_logs`
- `student_notifications` -> `notifications`
- `event_departments` -> `event_departments` (kept with normalized FK)
- `courses` -> `courses` (kept with JSON year-level support)

## Migration Rules

- Preserve legacy IDs when importing historical records.
- Enforce foreign keys and indexed lookup columns.
- Use Laravel timestamps for all canonical tables.
- Keep queue-driven operations idempotent and retry-safe.

## Status

- Canonical schema defined.
- Laravel migrations created for mapped legacy/core tables.
