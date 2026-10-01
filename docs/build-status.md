# Aether School OS — Build Status

## Phase 1: Repository Audit
- [x] Inspect repository structure
- [x] Inspect Laravel structure
- [x] Inspect package versions
- [x] Inspect existing migrations
- [x] Inspect React/Inertia pages
- [x] Inspect routes
- [x] Inspect auth
- [x] Inspect Tailwind/Vite configuration
- [x] Inspect existing tests
- [x] Inspect environment configuration
- [x] Create docs/architecture.md
- [x] Create docs/domain-map.md
- [x] Create docs/build-status.md
- [x] Create docs/decisions.md

## Phase 2: Foundation and Project Setup
- [x] Install Composer dependencies (Spatie Permission, Activity Log, DOMPDF, Predis)
- [x] Install npm dependencies (Radix UI, CVA, date-fns, lucide-react)
- [x] Create domain directory structure
- [x] Configure Tailwind CSS with design system
- [x] Set up TypeScript strict mode
- [x] Create tsconfig.json
- [x] Update vite.config.js
- [x] Create reusable UI primitives (Button, Input, Card, Badge, etc.)
- [x] Create utility functions (cn, formatCurrency, formatDate, etc.)
- [x] Create app shell layout
- [x] Create public layout

## Phase 3: Database and Domain Model
- [x] Create migrations for all domains (55 migrations)
- [x] Create Eloquent models for all domains
- [x] Configure model relationships
- [x] Run migrations successfully
- [x] Create factories and seeders
- [x] Seed Al Noor School development data

## Phase 4: Identity, Organization, and School Context
- [x] Organizations table and model
- [x] Schools table and model
- [x] User memberships table and model
- [x] Update User model with Spatie Permission
- [x] Configure AppServiceProvider with Super Admin gate
- [x] Create authentication middleware
- [x] Create school context middleware
- [x] Create authentication controllers
- [x] Create login page
- [x] Create school selection page
- [x] Configure HandleInertiaRequests middleware

## Phase 5: Onboarding Wizard
- [x] Onboarding routes
- [x] Onboarding pages
- [x] Shepherd.js integration

## Phase 6: Academic Structure
- [x] Academic years, semesters, grade levels, sections, subjects, offerings
- [x] Academic management pages
- [x] Grading scales and categories

## Phase 7: People
- [x] Students, guardians, teachers tables and models
- [x] People management pages
- [x] Guardian-child relationships

## Phase 8: Admissions Staff Workflow
- [x] Admissions routes and pages
- [x] Public application journey: five steps that persist on submit, with the
  uploads stored and a reference the family can quote
- [x] Review queue: filters by status/priority/assignee, assignment, priority
  changes, internal notes, and an applicant detail screen
- [x] Decision workflow: approve/reject on the application screen and the review
  screen, and convert-to-student which creates the guardian link and enrolls
  into the applied grade's section (tests: ApplicationDecisionTest)
- [x] Review decision screen: it used to post the same field name twice, so
  "Reject" submitted `approved`. It now goes through Inertia like the application
  screen, and a component test pins what the request carries.
- [ ] The decision endpoints trust whatever `decision` arrives as long as it is
  `approved` or `rejected`; a reviewer who is not the assignee can still decide.

## Phase 9: Enrollment
- [x] Enrollment management pages
- [ ] Waitlist functionality

## Phase 10-13: Scheduling, Attendance, Assessment, Learning
- [x] Management pages for each domain
- [x] Scheduling: rooms, timetable
- [x] Attendance: sessions, records
- [x] Assessment: exams, results, report cards
- [x] Learning: materials, assignments, quizzes, submissions

## Phase 14-17: Finance, Payments, ZATCA
- [x] Finance management pages
- [x] Payment gateway abstraction
- [x] Webhook settlement
- [x] Invoice lifecycle: `draft` on create, `issued` only through the issue action
  (which delivers it to the guardian), then moved on by payments. Neither form
  offers a status field, and editing a `partially_paid` invoice no longer
  rewrites it as a draft (tests: InvoiceTest)
- [ ] ZATCA-ready invoicing
- [x] Machine values read in the interface language: statuses, priorities and
  payment methods are matched on the stored value (`value.<value>` in the copy
  dictionary, normalised so any spelling a screen picks is found) instead of
  being left as underscored English. Covers the 23 call sites across 21 files
  that print `status`, `priority`, `payment_method` and `event_type`
- [x] Invoice status: the badge renders its label from the dictionary through one
  shared helper, and `partially_paid` — which had no label at all — gained one in
  both languages
- [x] Invoice list reads in Arabic end to end: its headers, action labels, empty
  state and "Not sent" now come from hand-written pairs instead of waiting on the
  provider. Verified in a browser against a seeded school
- [ ] Responsive check on the data tables: at a 700px viewport the invoice table
  scrolls horizontally and the actions column is off screen. Seen while checking
  the Arabic list; unrelated to translation
- Note: the dashboard is not "English-only" where a page has no `t(locale, …)`
  calls. `handWrittenArabic()` inverts the copy dictionary into English→Arabic
  phrase pairs and the shell applies them to chrome by walking the DOM, which is
  why most pages legitimately hard-code English. What the walker will not do is
  guess: a cell is only translated for a value it already knows, so a phrase like
  "Not sent" still has to be in the dictionary

## Phase 18-24: Communication, Documents, Reports, Settings, Content
- [x] Communication pages
- [x] Document management
- [x] Reports and exports
- [x] Settings pages
- [x] Content management

## Phase 25-29: UX, Accessibility, Performance, Security
- [ ] Responsive design verification
- [ ] Accessibility improvements
- [ ] RTL support testing
- [ ] Performance optimization
- [x] Tenant isolation: every public page filters by one resolved school through
  `PublicController::schoolId()` and the fail-closed `forSchool()` scope; the
  signed-in app goes through `TenantContext` and the `BelongsToSchool` global
  scope (queries, route bindings, `exists:`/`unique:` validation)
- [x] Security headers (CSP, frame options, HSTS in production), two-factor
  authentication, rate limits on the public contact form and application journey
- [ ] Security follow-ups: the CSP still allows `'unsafe-inline'`/`'unsafe-eval'`
  for scripts, uploads are served through the application rather than signed
  URLs, and there is no enforced IP allowlist

## Phase 30-34: Testing, Deployment, Observability, Compliance
- [x] Testing suite: 446 tests / 2423 assertions on in-memory SQLite, covering
  tenancy, the public site, the application journey, schema/payload contracts,
  and the admission review actions
- [x] Static analysis and style: PHPStan level 5 (including the public
  controllers, which used to be excluded) and Pint, both with no errors
- [x] CI: `.github/workflows/ci.yml` runs the suite, PHPStan, Pint,
  `tsc --noEmit`, `npm test` and the asset build on every push
- [x] Frontend lint: `npm run lint` runs oxlint with the correctness category
  over 276 files, warnings fail the gate, and CI runs it
- [x] Frontend tests: Vitest + happy-dom + Testing Library. Covers the
  admissions decision screen (what the request carries, and that the decision
  never goes through a native form — the bug where "Reject" submitted
  `approved`) and the failure reporting the two shells own
- [ ] Frontend tests for the remaining pages: four suites exist out of 224 page
  components, so the other screens are still only checked by hand
- [x] Rejected saves are reported everywhere: the dashboard shell and the public
  shell render validation errors and a flashed failure once, for every page.
  Before this, only a dozen settings screens showed them, so six real
  `with('error')` paths (an already-confirmed payment, an incomplete application,
  a contact message that never sent, online payment unavailable, deleting the
  school you are in) failed in silence
- [ ] Wider lint rules: only the correctness category is enabled; the style and
  `unicorn` suggestions are off (ESLint's typed rules cannot be installed while
  the project is on TypeScript 7)
- [ ] Deployment configuration
- [ ] Observability
- [ ] Compliance review

---