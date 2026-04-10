# ERD and Canonical Schema Notes

This document is the source of truth for the target MySQL schema used by Laravel migrations.

## Core Entities

- users
- students
- admins
- events
- attendance
- courses
- departments
- sections
- notifications
- qr metadata fields

## Relationship Draft

- One `course` has many `students`
- One `department` has many `students`
- One `event` has many `attendance` records
- One `student` has many `attendance` records
- One `student` has many `notifications`

## Migration Rules

- Preserve legacy IDs where possible.
- Apply explicit foreign keys and indexes.
- Use consistent timestamps (`created_at`, `updated_at`).
- Add soft deletes only where business workflow requires recovery.

## Next Actions

1. Reverse engineer current schema from SQL files and live DB.
2. Draw final ERD with cardinality notes.
3. Translate each table into Laravel migration files.
4. Validate row-count and referential integrity checks post-import.
