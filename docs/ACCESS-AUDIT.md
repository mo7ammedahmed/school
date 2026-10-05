# Access and interface audit — October 5, 2026

The access gaps found in this review were fixed in the application and covered by regression tests. Authorization now controls the data sent to the browser as well as the links and buttons displayed there. Existing role grants were preserved; newly introduced permissions require an explicit grant.

## Who can see and do what

An active school membership establishes the tenant. Staff modules require their actual permissions, and record policies add ownership and school restrictions. Custom roles follow the same permission rules as seeded roles.

| Role | Available surfaces under the current seeded grants | Restrictions |
| --- | --- | --- |
| Super administrator | Platform administration and school modules | Explicit platform authority; school routes still require a selected context |
| School administrator | People, academics, learning, finance, content, school settings, user management and audit logs | Cannot grant platform privileges or change accounts shared with another school; organization checks protect school administration |
| Principal | People, academics, learning, content, school settings, audit logs and discounts | No user-role administration or core invoice/payment modules; another teacher's live studio is read-only |
| Registrar | People, academics, learning, admissions, fee assignments and discounts | No school settings, user-role administration or core invoice/payment modules |
| Teacher | Scheduling, attendance, assessment and learning modules, documents and communication | No people administration, school settings, user-role administration or finance modules; personal schedule requires a teacher profile; live publishing requires ownership |
| Accountant | Scheduling, finance, documents, communication and reports | No people administration, assessment/learning management, school settings or user-role administration |
| Student | Own schedule, attendance, released grades, published assignments, issued fees, enrolled live/recorded lessons and published enrolled quizzes | No staff modules, school-wide statistics, quiz solutions, draft fees or unreleased grades |
| Guardian | Linked children's schedule, attendance, released grades, published assignments and issued fees | Unrelated children and staff modules are denied |

The role table summarizes the current seed configuration. It does not replace the route middleware and policies. Permissions remain global to an account in this application's existing architecture; school administrators therefore cannot mutate accounts shared with other schools.

## Fixes

- Dashboard queries and payload fields are permission-filtered. Sidebar groups, shortcuts and settings controls use the corresponding route permissions.
- User creation and updates validate assignable roles on the server. Platform accounts, stronger accounts and accounts shared with other schools are protected against local administrators.
- School administration checks the original organization before viewing, editing, moving or deleting a school. Onboarding and organization translations require permission.
- Permission-cache invalidation now invalidates browser permission lists globally after role changes.
- Missing student/teacher profiles fail closed on legacy personal routes. Withdrawn enrollments no longer grant lesson or assignment access.
- Student quiz payloads omit answer keys, solutions and unnecessary offering data. Staff edit controls are hidden for student attempts.
- Student and guardian payloads omit unnecessary profile fields and staff contact details. Draft assignments/invoices and unpublished or future report cards are excluded from lists and summary calculations.
- Outstanding fees use the remaining balance. Submitted work is excluded from pending assignment counts. Currency follows the school's setting.
- Students and guardians land in their own portal after school selection. Guardian greetings receive the name the screen expects.
- Pagination no longer repeats page links or produces invalid numbers. Placeholder generated reports and inactive generation controls were removed.
- Dark-mode login links, selected navigation and home-page text/cards were corrected without changing saved appearance settings.

## Live media boundary and rollout

MediaMTX previously accepted anonymous publishing and reading. Its configuration now uses the application's `/media/authorize` HTTP callback. Credentials are encrypted, expire after two hours and are bound to one user, lesson and action. Current membership, enrollment, permission and live status are checked when media authorization runs. A read credential cannot publish. HLS playlists and segments use a session-protected application proxy, including native Safari playback. Only an authorized live studio response permits camera and microphone use.

This implementation follows MediaMTX's documented [HTTP authentication and bearer-token interface](https://mediamtx.org/docs/features/authentication).

For deployment:

1. Deploy the application and rebuild assets. Reload configuration normally.
2. Restart MediaMTX with the updated `docker/mediamtx/mediamtx.yml`. In Compose its callback is `http://nginx/media/authorize`; outside Compose set `MTX_AUTHHTTPADDRESS` to an app address reachable by the media server. Do not retain an anonymous internal-auth configuration.
3. Configure `MEDIA_HLS_INTERNAL_URL` for the app-to-media connection. Compose uses `http://mediamtx:8888`; `MEDIA_HLS_URL` enables the fallback. Use TLS for public production endpoints.
4. Configure private `MEDIA_API_USER` and `MEDIA_API_PASSWORD` values for reconciliation. Empty values intentionally deny control API authorization. No existing credentials or `.env` values were changed during this work.
5. Verify a teacher can publish, an enrolled student can watch over WHEP/HLS, and anonymous or unenrolled clients are refused. Refresh an open lesson page when its media credential expires.

Docker/MediaMTX was not available as a runnable local service for an end-to-end broadcast test. Callback and proxy behavior were tested with application tests and mocked upstream responses. Existing WebRTC connections are authorized at connection establishment; revoking a role or enrollment does not forcibly disconnect an already-established peer. Restarting the media service during rollout terminates existing anonymous connections. Immediate mid-connection revocation needs a media-session disconnect mechanism.

## Visual walkthrough

Screenshots were captured from the local app at a 747 × 884 viewport, saved as exact screenshot bytes and reopened for inspection. The browser checks used seeded school-administrator and student accounts. The eight-role coverage comes from automated authorization tests, not eight separate visual sessions.

### 1. Login — usable; dark-link contrast fixed

The sign-in form has labeled controls and a clear submit action. The initial screenshot showed dark green links on a black background. The corrected screenshot below verifies the lighter links. Keyboard and screen-reader behavior beyond the accessible labels was not fully audited.

![Login](C:/Users/moham/Downloads/projects/school-1/storage/app/access-audit/01-login.png)

### 2. School selection — working

Only active membership schools are offered; Continue remains disabled until selection. The school-administrator and student flows were both completed. Students and guardians now redirect to their own dashboard. The public header/footer makes this screen taller than necessary, but the selection action is usable.

![School selection](C:/Users/moham/Downloads/projects/school-1/storage/app/access-audit/02-school-selection.png)

### 3. User form — restricted

The school-administrator role selector offered seven local roles and omitted super administrator. The options were verified through the accessible control; the closed selector in this screenshot does not display its option list. Crafted requests for platform roles are covered by server tests. No user was created through the browser.

![User form](C:/Users/moham/Downloads/projects/school-1/storage/app/access-audit/03-admin-role-options.png)

### 4. Student navigation — restricted and readable

The student sidebar contains personal portal routes and account controls. Staff settings and finance administration are absent. The underlying dashboard shows personal statistics and school currency. The selected link's contrast was corrected after inspecting the school theme's actual computed colors.

![Student navigation](C:/Users/moham/Downloads/projects/school-1/storage/app/access-audit/04-student-navigation.png)

### 5. Direct staff URL as student — correctly denied

Opening `/settings/users` directly returned 403. Hiding a link is therefore backed by server authorization. The error screen is plain and English; its message correctly denies access and exposes no staff records.

![Denied staff URL](C:/Users/moham/Downloads/projects/school-1/storage/app/access-audit/05-student-forbidden.png)

### 6. Public home in dark mode — corrected

The heading accent and motto card previously had unreadable contrast. The revised colors and card backgrounds remain readable in the school's saved dark theme. This is a visual check of the captured viewport, not a complete WCAG audit across every customizable palette.

![Public home](C:/Users/moham/Downloads/projects/school-1/storage/app/access-audit/06-public-home.png)

## Verification

- Full PHPUnit run: **1,133 tests, 5,182 assertions passed**. Additional final affected checks cover portal publication, media authorization, navigation permissions and session handling.
- Vitest: **61 tests across 11 files passed**.
- TypeScript, Oxlint, PHPStan and client/SSR production builds passed.
- npm and Composer audits reported no known vulnerabilities or abandoned Composer packages.
- `git diff --check` passed. The working tree already contained substantial user changes; those were preserved. No commit, push or deployment was performed.

Logs and local screenshots are in `storage/logs/access-audit-*` and `storage/app/access-audit/`. The report-generation backend remains unimplemented; its page now shows an honest empty state. Live-camera behavior, production media connectivity and every screen at every viewport are outside the evidence obtained in this local run.
