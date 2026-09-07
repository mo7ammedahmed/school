# Aether School OS — Architecture

## Stack

- PHP 8.4+
- Laravel 13
- Inertia.js 3
- React 19
- TypeScript strict
- Tailwind CSS 4
- Vite
- MySQL 8+ / SQLite
- Redis
- S3-compatible storage

## Tenancy

Organization → School. Every school-owned table has `school_id`. No Branch, no branch_id, no classes table.

## Domain Structure

```
app/Domain/
├── Identity/      (users, credentials, 2FA, sessions, memberships, roles)
├── Schools/       (organizations, schools, branding, settings)
├── People/        (students, guardians, teachers, relationships)
├── Admissions/    (periods, applications, review, conversion)
├── Academics/     (years, semesters, grades, sections, subjects, offerings, enrollments, grading)
├── Scheduling/    (rooms, periods, timetable entries, conflicts, publication)
├── Attendance/    (sessions, records, finalization, correction)
├── Assessment/    (assessments, categories, scores, exams, results, report cards)
├── Learning/      (materials, assignments, quizzes, submissions, attempts)
├── Finance/       (fee types, structures, assignments, invoices, payments, allocations, refunds)
├── Communication/ (messages, conversations, announcements, notifications)
├── Documents/     (metadata, storage locators, classifications, links)
├── Compliance/    (audits, redaction, retention)
├── Analytics/     (read-only aggregates/read models)
├── Integrations/  (provider adapters)
└── Content/       (public pages, news, events, FAQs, staff profiles, leads)
```

## Frontend

- Public website: premium institutional, deep emerald + gold accent, Source Serif 4 / Amiri
- Dashboard: corporate, IBM Plex Sans Arabic
- RTL-first with logical CSS properties
- Bilingual Arabic/English
