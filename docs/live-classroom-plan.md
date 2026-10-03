# Aether School OS — Live Classroom & Lesson Video Plan

> **ملخص بالعربية**
>
> - **النمط المعتمد:** بث من المعلم فقط (كاميرا/شاشة) والطلاب يشاهدون داخل المنصة.
> - **التقنية المعتمدة:** MediaMTX مستضاف ذاتيًا داخل Docker على VPS — يسجّل مباشرة إلى القرص، ويقدّم WebRTC (WHEP) وHLS للمشاهدة، ويُنشر من المتصفح عبر WHIP بدون أي SDK.
> - **التسجيل التلقائي:** ما إن تنتهي الجلسة يُنشئ طابور `FinalizeLiveSession` صف `Material` مرتبطًا بـ offering المعلم (المادة × الشعبة) — فتظهر في ملفات المادة، ويشاهدها الطالب من قسم «الدروس» الجديد في بوابة الطالب.
> - **فيديو مرفوع (بدون بث):** المعلم يرفع فيديو، والطالب يشغّله داخل المنصة عبر بثّ بنطاق HTTP Range (تشغيل/تقديم يعملان).
> - **المراحل:** (0) تجربة البنية، (1) دروس الفيديو المرفوعة، (2) الجلسات المباشرة والتسجيل التلقائي، (3) تحسينات (HLS احتياطي، رفع مجزّأ، تحليلات، دردشة).
> - **قيد مهم:** استضافة البث تحتاج VPS بعرض نطاق صاعد جيد ومنفذ UDP مفتوح؛ وإلا ننتقل إلى HLS عبر 443 أو خدمة مُدارة.

## 1. Goals

1. A teacher opens a **live session** for one of their own offerings (subject × section) and
   broadcasts camera and/or screen from the browser.
2. Students enrolled in that section watch **inside the platform** — no external links or apps.
3. The session is **recorded automatically**; when it ends, the recording is registered and
   appears in the subject's materials, playable by students and downloadable by staff.
4. A teacher can also **upload a video lesson** (not live) that students play in-platform.
5. All of it respects existing roles/permissions, tenant scoping, Arabic/English and RTL, and
   the project's self-hosted, no-vendor-cost footprint.

## 2. Architecture decision

**Chosen: MediaMTX self-hosted on the app's VPS; teacher → students broadcast.**
Decided with the product owner: teacher-only broadcast (no student webcams) and self-hosted
infrastructure (no monthly media service).

MediaMTX is a single zero-dependency Go binary/container that speaks WHIP/WHEP, HLS, RTMP, SRT
and **records natively to disk**. It fits every requirement with the least machinery:

| Requirement | How MediaMTX satisfies it |
| --- | --- |
| Teacher publishes from the browser | **WHIP** ingest (standard WebRTC; ~60 lines of `RTCPeerConnection` on the teacher page, no SDK) |
| Students watch in-platform | **WHEP** playback (~0.5 s latency) and **HLS** fallback from the same server |
| Automatic recording | `record: yes` writes fMP4 files straight to a local volume — no cloud storage needed |
| No recurring cost | One container next to the app; bandwidth is the only cost |

### Alternatives considered

- **LiveKit (SFU) + Egress** — the right choice *only if* students must also publish audio/video
  (breakout rooms, real interaction). Egress recording cannot write to local disk: it requires
  S3-compatible storage (MinIO/GCS/Azure) plus Redis and a Chrome-based egress container. That is
  three extra services for a teacher-broadcast feature. Kept as the Phase 3 upgrade path if
  interactivity is ever requested.
- **Jitsi + Jibri** — recording is resource-heavy and operationally brittle; heavier than needed.
- **Managed services (Cloudflare Stream / Mux)** — fastest to ship and most reliable under load,
  but per-minute billing and vendor dependency. Keep as the fallback if the school's uplink cannot
  carry the streams (see §9).

### Topology

```
Teacher browser ──WHIP(HTTPS)──▶ nginx ──▶ MediaMTX ──records──▶ storage/app/recordings/
Student browser ──WHEP(HTTPS)──▶ nginx ──▶ MediaMTX
Student browser ──MP4/Range────▶ Laravel ──▶ private disk (recordings + uploaded videos)
```

- MediaMTX runs as a new `docker-compose` service, bound to loopback where possible; nginx
  reverse-proxies HTTPS for WHIP/WHEP/HLS; the WebRTC UDP port range is opened on the firewall.
- Uploaded videos and finished recordings live on the app's private disk (`local` today, S3 per
  Decision 5 when volume grows) and are served by Laravel with HTTP Range support.

## 3. Data model

### New table: `live_sessions`

| Column | Notes |
| --- | --- |
| `id`, `school_id` | `school_id` + `BelongsToSchool` (tenant scope, fail-closed) |
| `offering_id` | FK → offerings; the subject × section the session belongs to |
| `started_by` | FK → users; the teacher who opened it |
| `title`, `title_ar` | Session name (bilingual columns like the rest of the app) |
| `status` | `draft` → `live` → `ended` (or `failed`) |
| `stream_key` | `Str::random(40)`, unique; the WHIP/WHEP path name |
| `started_at`, `ended_at`, `duration_seconds` | filled on start/end |
| `viewer_peak`, `viewer_total` | updated from MediaMTX stats (Phase 2/3) |
| `recording_path`, `recording_size`, `recording_status` | `pending` → `processing` → `ready` → `failed` |
| `material_id` | FK → materials; the row created automatically when the recording is ready |
| timestamps, `deleted_at` | soft deletes |

### Extend `materials` (expand migration, no in-place rename)

| Column | Notes |
| --- | --- |
| `kind` | `file` (default) · `video` (uploaded) · `recording` (from a live session) |
| `duration_seconds` | from `ffprobe` (nullable) |
| `thumbnail_path` | Phase 3 |
| `source_live_session_id` | nullable FK back to the session |

Reusing `materials` is deliberate: the recording lands in the same library the teacher knows
("ملفات المادة"), with the existing list/edit/delete flows. Only a new `kind` changes the UI.

### Permissions (Spatie)

- `manage-live-sessions` — teacher, school_admin, super_admin (principal: read-only oversight).
- `view-own-lessons` — student; a guardian equivalent later if requested.
- Migration adds them; `DatabaseSeeder` refresh does not overwrite school-edited roles (the
  permission seeder only creates missing rows).

## 4. Flows

### A. Teacher starts a live session

1. Teacher opens **`/live`** (new nav entry) → sees their own sessions + "Start a session".
2. Chooses one of their offerings. The offering list is filtered to
   `TeacherProfile::where('user_id', $user->id)->offerings` — a teacher can never select
   another teacher's offering (policy + scoped query + test).
3. `POST /live` creates the session (`status=draft`, random `stream_key`) and renders the studio
   page.
4. "Go live": the page captures `getUserMedia` (camera+mic) or `getDisplayMedia` (screen) and
   sends the SDP offer to MediaMTX `POST /{stream_key}/whip`. On HTTP 201 the app marks the
   session `live` and stamps `started_at`.
5. MediaMTX config for the path: `record: yes`, `recordFormat: fmp4`, one file per session where
   possible, `runOnNotReady` → HTTP hook back into the app (publishing stopped).

### B. Students watch live

1. New student-portal page **`/student/lessons`** («الدروس») with two tabs:
   **مباشر الآن** and **الدروس المسجّلة**.
2. "Live now" lists sessions whose offering section contains the student — the same enrollment
   query the portal already uses in `PortalController` (`offering.section.students`).
3. The player uses WHEP (WebRTC) in an HTML `<video>`; no new npm dependency for the MVP.
   Authorization is enforced by the app before the player URL is rendered; MediaMTX stream paths
   are unguessable random keys. **Phase 2/3 hardening:** MediaMTX `authHTTPAddress` → a small
   authenticated endpoint that validates a signed, short-lived token per viewer, so paths cannot
   be shared outside the platform.
4. A "live" badge appears on the student dashboard and (optionally) a notification when a session
   starts.

### C. Session ends → the recording becomes subject material (automatic)

1. End triggers, all reconciled by one command: teacher clicks "End session"; MediaMTX
   `runOnNotReady` webhook; or `live-sessions:finalize` (scheduled every 5 minutes) for crashed
   sessions.
2. `FinalizeLiveSession` is dispatched — **the project's first `ShouldQueue` job**, picked up by
   the idle `queue` container in compose:
   - waits for the recording file to stop growing,
   - remuxes fMP4 → MP4 (`ffmpeg -c copy`) and extracts duration (`ffprobe`) — **ffmpeg is added
     to the runtime image** (one apt package),
   - creates the `Material`: `kind=recording`, `offering_id` from the session, `file_path` on the
     private disk, `is_published=true`, `source_live_session_id` linking back,
   - stores `recording_status=ready`, `material_id` on the session,
   - optionally notifies the section's students (existing `Notification` model).
3. Staff download the recording through the existing materials download route; students play it in
   the Lessons page (the `MaterialPolicy` gains a student branch: published video/recording
   material of a section the student is enrolled in).

### D. Uploaded video lesson (not live)

1. Teacher opens Materials → Create and picks a video (a dedicated hint on the form distinguishes
   it from a document). New `kind=video`.
2. Upload limits raised (`upload_max_filesize`, `post_max_size`, nginx `client_max_body_size`);
   the validation whitelist (`AllowedAttachment`) gains mp4/webm/mov/mkv mime types and a size cap.
   **Phase 3:** chunked/resumable upload (tus) for large files and flaky links.
3. Playback endpoint **`GET /materials/{material}/stream`**:
   - policy check first (teacher/admin always; student only if enrolled and published),
   - reuse the `staysInsideTheDisk` guard from `ServesStoredAttachment`,
   - serve with `Storage::disk('local')->response($path)` — Symfony's `BinaryFileResponse`
     handles HTTP Range, so seeking works; the existing `download()` trait does **not** support
     Range and must not be used for video.
4. Student UI: `<video controls playsInline>` inside the Lessons page — no player library needed
   for MP4. (HLS + `hls.js` only if adaptive quality arrives in Phase 3.)

## 5. Security & tenancy

- Every new model uses `BelongsToSchool`; every query and route binding is tenant-scoped.
- `LiveSessionPolicy`: start/end only for the session's owner teacher (or admin); view for
  enrolled students + staff; nothing crosses schools.
- Stream keys are random and rotatable; publish URLs are only ever rendered to the owner teacher.
- MediaMTX is bound to loopback/private interfaces, disabled protocols are turned off, and only
  the WebRTC UDP range is exposed. Production hardening adds `authHTTPAddress` (signed tokens per
  publish/read) — required before opening the UDP port to the internet.
- Start/end and recording publication are written to the activity log (spatie/activitylog).

## 6. UI (Arabic/English, RTL)

| Page | Purpose |
| --- | --- |
| `live/index.tsx` | Teacher: their sessions, start/end, recording status |
| `live/show.tsx` | Studio: preview, camera/screen switch, live badge, viewer count, End |
| `student-portal/lessons.tsx` | Students: live now + recorded lessons + player |
| `materials/show.tsx` (edit) | Inline player when `kind` is video/recording |
| Navigation | "الدروس المباشرة" in the teacher nav, "الدروس" in the student portal nav — via the existing navigation-settings module |

All new strings go through the existing extraction + Arabic dictionary pipeline
(`scripts/extract-interface-strings.mjs` → `InterfaceTranslationSeeder`), so the AR UI stays
complete.

## 7. Infrastructure changes

- `docker-compose.yml`: new `mediamtx` service (`bluenviron/mediamtx`),
  `docker/mediamtx/mediamtx.yml`, volume `./storage/app/recordings:/recordings`, ports: WebRTC
  (UDP range) and loopback HTTP for WHIP/WHEP/HLS; nginx proxy entries for TLS.
- `Dockerfile`: add `ffmpeg` (remux + duration/thumbnail extraction).
- Env: `MEDIA_BASE_URL`, `MEDIA_PUBLISH_SECRET`, `MEDIA_RECORDINGS_PATH`; documented in
  `.env.example` and `docs/DEPLOY.md` with the firewall/bandwidth notes.
- Scheduler: `live-sessions:finalize` every five minutes (reuse the idle scheduler container).
- Queue: first real workload for the idle `queue` container; add `queue:restart` to deploys.

## 8. Phased delivery

| Phase | Scope | Effort |
| --- | --- | --- |
| **0 — spike** | Run MediaMTX locally, publish from a scratch page, verify record→disk and WHEP playback, measure CPU/bandwidth | ½ day |
| **1 — uploaded videos** | `kind`/`duration` migration, video validation, Range streaming endpoint, student Lessons page, permissions, tests | 2–3 days |
| **2 — live sessions** | Models/routes/policies, teacher studio (WHIP), student live join (WHEP), recording finalize job + scheduler + webhook, automatic Material, compose service + ffmpeg, docs, notifications | 3–5 days |
| **3 — hardening** | `authHTTPAddress`, HLS fallback + `hls.js`, chunked upload, attendance/analytics per viewer, chat, thumbnails, retention policy | later |

## 9. Risks & open items

- **Uplink bandwidth is the hard limit.** One viewer ≈ one stream copy (~2.5 Mbps): 30 students ≈
  75–80 Mbps of VPS egress, plus the teacher's ~2.5 Mbps up. A VPS below ~100 Mbps uplink cannot
  host a full class — in that case use HLS behind a CDN or the managed fallback.
- **UDP may be blocked** on school networks. WHEP needs UDP (or ICE/TCP); HLS over 443 is the
  guaranteed fallback and is already served by MediaMTX — wire it in Phase 3 or earlier if tests
  fail on the school network.
- **Storage growth:** one hour ≈ 1–1.5 GB. Plan a retention/archive policy and move old
  recordings to the S3 disk (Decision 5) when volume grows.
- **Browser support:** WHIP publishing is solid in Chrome/Edge/Firefox; verify Safari before
  promising it. Playing WHEP works in the same browsers; HLS covers the rest.
- **fMP4 playback:** without the ffmpeg remux step, recordings are not reliably seekable in
  browsers — the image change is part of Phase 2, not optional.
- **Single concurrent session** is the design target for the MVP; MediaMTX handles more, but the
  VPS link budget decides the real number.

## 10. Tests to add

- Policies: teacher can only create/end sessions for their own offerings; a student can only view
  materials/sessions of their own sections; cross-school access 404s.
- Feature: start → row created with random key; end → job dispatched (`Queue::fake`); finalize job
  → material created (`Storage::fake` on the recordings disk); stream endpoint returns 200 with
  `Accept-Ranges: bytes` and 206 for a Range request, 403/404 otherwise; video upload validation
  rejects non-video payloads.
- `EveryPageOpensTest` picks the new routes up automatically; add the new pages to the role matrix.
