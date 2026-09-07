# Aether School OS — Domain Map

| Domain | Responsibility |
|---|---|
| Identity | users, credentials, 2FA, sessions, memberships, roles |
| Schools | organizations, schools, branding, locale, timezone, currency, settings |
| People | students, guardians, teachers, relationships |
| Admissions | periods, applications, review, conversion |
| Academics | years, semesters, grades, sections, subjects, offerings, assignments, enrollments, grading |
| Scheduling | rooms, periods, timetable entries, conflicts, publication |
| Attendance | sessions, records, finalization, correction |
| Assessment | assessments, categories, scores, exams, results, report cards |
| Learning | materials, assignments, quizzes, submissions, attempts |
| Finance | fee types, structures, assignments, invoices, payments, allocations, refunds, gateway transactions |
| Communication | messages, conversations, announcements, notifications |
| Documents | metadata, storage locators, classifications, links |
| Compliance | audits, redaction, retention |
| Analytics | read-only aggregates/read models |
| Integrations | provider adapters |
| Content | public pages, news, events, FAQs, staff profiles, leads |

## Database Conventions

- Money: `decimal(19,4)`
- Every school-owned table: `school_id`
- Foreign keys, indexes, unique constraints, check constraints
- Soft deletes where appropriate
- Audit fields on all domain tables

## State Machines

Admission: draft → submitted → under_review → approved → rejected → converted → withdrawn
Invoice: draft → issued → partially_paid → paid → voided
Payment: pending → paid → failed → refunded → partially_refunded → review
