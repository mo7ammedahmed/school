# Aether School OS

Aether School OS is a modern, domain-driven school management platform for educational institutions that need a single system to manage operations, academic workflows, admissions, communication, and finance. Built on Laravel 13 with an Inertia + React + TypeScript frontend, it is designed for schools that operate across bilingual and multi-user workflows, including Arabic and English support and RTL-first experiences.

This project goes beyond a starter Laravel application. It contains a substantial school infrastructure foundation and operational modules spanning both the back office and the public-facing school experience.

## Project overview

Aether School OS aims to support the full lifecycle of a school:

- onboarding and school setup
- academic structure and curriculum management
- student, teacher, guardian, and enrollment operations
- admissions and applicant journeys
- scheduling, attendance, and assessment workflows
- finance, invoicing, and payment management
- communication, content, documents, and settings

The system is built around an organization-to-school tenancy model so that each school has isolated operational data while remaining under a shared platform structure.

## Why this project matters

The application is designed to solve the real administrative needs of schools, especially where a single platform must unify operations that are often spread across disconnected tools.

Key strengths include:

- multi-school / multi-organization architecture
- school-scoped data ownership through `school_id`
- role-based access with Spatie Permission
- Arabic/English bilingual support with RTL-aware layouts
- educational domain modeling for academic and operational workflows
- public website and admissions experience integrated into the same product
- finance-ready data structures for invoices, payments, and billing flows
- comprehensive settings management for appearance, localization, notifications, payments, and security
- advanced scheduling with timetable conflicts detection and calendar views
- two-factor authentication for enhanced security
- extensive API endpoints for integration capabilities

## Current implementation status

The project already includes a broad set of implemented modules and foundational features.

### Platform and foundation
- Laravel 13 application framework
- Inertia.js 3 + React 19 + TypeScript frontend
- Tailwind CSS 4 design system and reusable UI primitives
- authentication and school context handling
- onboarding wizard for new school setup
- configured architecture for organizations, schools, memberships, and roles
- advanced settings management system with appearance, localization, notifications, payments, and security configurations
- two-factor authentication implementation
- comprehensive API routes for frontend-backend communication

### Academic and student operations
- academic years, semesters, grade levels, sections, and subjects
- offerings and course structure
- student, guardian, and teacher management
- enrollment workflow and school-wide academic records
- grading scales, assessment categories, exams, and report card support
- academic policies and validation rules

### Admissions and learning
- admissions periods and application pipelines
- public application pages and guided applicant journeys
- materials, assignments, quizzes, and submission flows
- assessment import and export capabilities
- question bank management for exams

### Operations and finance
- room and timetable management with conflict detection
- attendance sessions and records with correction workflows
- invoice, fee, payment, allocation, and refund structures
- Saudi-market-ready invoice fields for ZATCA-aligned processing
- multiple payment gateway support (including Moyasar)
- payment settlement and reconciliation services
- invoice delivery methods (email, SMS, download links)
- PDF invoice generation with Arabic support
- discount and coupon management
- offline payment tracking

### Communication, documents, and content
- announcements, notifications, and messaging system
- documents and document categories with file management
- public content pages for news, events, and FAQs
- audit logging and settings pages
- calendar integration for events and timetables
- localization management with Arabic language support
- translation management system for custom content

### Scheduling and timetable
- comprehensive timetable management with grid, calendar, and conflict views
- period and calendar day management
- timetable entry scheduling with conflict resolution
- schedule printing and export capabilities
- teacher and room availability checking

### Settings and customization
- appearance settings with theme customization and accent colors
- localization settings for multiple languages
- notification configuration (email, SMS, system)
- payment settings and gateway configurations
- security settings including two-factor authentication
- grading scale and category management
- preference settings for users
- translation management for custom content

## Architecture

The project follows a clearly separated, domain-oriented structure:

- `app/Domain/` contains business domains and feature-specific logic
- `app/Models/` holds the data models and relationships
- `database/migrations/` includes the system schema
- `resources/js/` contains the Inertia + React frontend and UI logic
- `routes/` defines the application endpoints
- `docs/` provides architecture, decisions, build status, and implementation notes

This domain structure supports scalable extension without collapsing all business logic into a single monolithic layer.

Each domain follows a consistent structure:
- Actions: Business logic encapsulated in action classes
- Models: Eloquent models with relationships and attributes
- Events: Domain events for loose coupling
- Policies: Authorization policies
- Services: Business service classes
- DTOs: Data transfer objects where applicable
- Enums: Type-safe enumerations

## Tech stack

- PHP 8.3+
- Laravel 13
- Inertia.js 3
- React 19
- TypeScript
- Tailwind CSS 4
- Vite
- MySQL 8+ / SQLite for local development
- Redis
- Spatie Permission
- Spatie Activity Log
- DOMPDF
- Laravel Sanctum (for API authentication)
- Laravel Jetstream (foundation)
- Ziggy (for Laravel routes in JavaScript)

## Repository structure

```bash
app/
  Domain/
    Academics/
    Admissions/
    Assessment/
    Attendance/
    Communication/
    Finance/
    Identity/
    Localization/
    People/
    Scheduling/
    Schools/
  Http/
    Controllers/
    Middleware/
    Listeners/
    Mail/
  Models/
  Policies/
  Providers/
config/
database/
  migrations/
  factories/
  seeders/
docs/
public/
resources/
  css/
  js/
    Pages/
    components/
    lib/
    types/
routes/
tests/
  Feature/
  Unit/
```

## Getting started

### 1) Install dependencies

```bash
composer install
npm install
```

### 2) Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

Then update the database, app URL, and environment values in `.env`.

### 3) Run the database migrations

```bash
php artisan migrate
```

### 4) Start the application

Run the frontend:

```bash
npm run dev
```

Run the backend:

```bash
php artisan serve
```

Or use the project helper script:

```bash
composer run dev
```

## Useful commands

```bash
composer run setup
composer run test
php artisan test
npm run build
npm run lint
php artisan pint
phpstan analyse
```

## Public website and school experience

The platform includes a public-facing school experience with routes such as:

- `/` — landing page
- `/about` — school overview
- `/programs` — academic offerings
- `/admissions` — admissions information
- `/contact` — contact and inquiry pages
- `/apply` — guided applicant journey
- `/calendar` — school calendar view
- `/news` — news and announcements
- `/events` — upcoming events

## Admin and operations areas

The system includes admin screens for:

- dashboard
- students and guardians
- teachers and staff
- academic planning
- admissions workflow
- attendance and scheduling
- exams and assessments
- finance and invoicing
- messages and announcements
- documents and reports
- settings and roles
- audit trail and onboarding
- calendar management
- localization and translations
- theme customization
- payment gateway configuration
- security settings

## Documentation

Project documentation is available in:

- [docs/architecture.md](docs/architecture.md)
- [docs/build-status.md](docs/build-status.md)
- [docs/domain-map.md](docs/domain-map.md)
- [docs/decisions.md](docs/decisions.md)
- [docs/IMPLEMENTATION_SUMMARY.md](docs/IMPLEMENTATION_SUMMARY.md)
- [docs/API_REFERENCE.md](docs/API_REFERENCE.md)

## Roadmap and next improvements

The project has achieved significant maturity with the recent updates. The next priorities are focused on:

1. **Testing and quality assurance**
   - Expand feature and unit test coverage
   - Implement automated UI testing with Playwright
   - Add performance benchmarking
   - Strengthen type safety across frontend and backend

2. **Integration and extensibility**
   - Develop comprehensive API documentation with examples
   - Create webhook system for external integrations
   - Add support for additional payment gateways
   - Implement LTI (Learning Tools Interoperability) support
   - Create plugin/extension system for custom functionality

3. **User experience enhancements**
   - Improve mobile responsiveness across all screens
   - Add keyboard navigation and accessibility improvements
   - Implement dark/light theme toggle persistence
   - Enhance search functionality with filtering and saved views
   - Add bulk operations for common administrative tasks

4. **Operational excellence**
   - Implement automated backup and recovery procedures
   - Add monitoring and health check endpoints
   - Create database optimization and indexing strategies
   - Implement caching strategies for frequently accessed data
   - Add comprehensive error reporting and logging

5. **Compliance and localization**
   - Expand Arabic language support throughout the platform
   - Add support for additional languages and regional formats
   - Implement GDPR and data privacy controls
   - Add FERPA compliance features for student data protection
   - Enhance audit trails for regulatory compliance

## License

This project is open-source and licensed under the MIT license.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing-feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please make sure to follow the existing code style and include tests for new features.