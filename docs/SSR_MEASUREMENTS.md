# Inertia SSR — before/after measurements

What server-side rendering actually changed, measured as an A/B on one machine with
the same code, the same database and the same session: two Laravel processes, one
with SSR on, one with it off.

Recorded 2026-10-02. Every number below was produced by the commands in
[How to reproduce](#how-to-reproduce).

## Bottom line

With the same bundle in both modes, SSR moved the first paint of the page from
**2.2–3.4 s to 0.67–0.89 s** and time to interactive from **2.2–3.4 s to 1.7–2.4 s**,
and paid for it with **+3.6–8.0 KB of gzipped HTML** and **+39–108 ms of TTFB** per
request.

## Environment

| | |
| --- | --- |
| SSR on | `http://localhost:8000` (`INERTIA_SSR_ENABLED` unset → default on), Inertia SSR node server on `127.0.0.1:13714` (`php artisan inertia:start-ssr`) |
| SSR off | `http://localhost:8001` (`INERTIA_SSR_ENABLED=false`, `php artisan serve --port=8001 --no-reload`) |
| PHP | 8.6.0beta2, built-in dev server — **one process, requests serialised, no gzip/brotli** |
| Client assets | production Vite build (`public/build`, minified + hashed, `@vite` directive — no dev server, no `hot` file) |
| Browser | preview Chromium, viewport 1280×900 |
| Session | same authenticated superadmin session in both processes (cookies ignore the port) |
| Pages | `/` and `/about` (public), `/dashboard` (authenticated) |

The only difference between the two stacks is the SSR toggle. The browser payload —
the bundle, its size, its chunking — is identical in both, because Inertia SSR ships
markup, not JavaScript.

## Server response

Ten interleaved requests per cell (`curl`, medians, `min–max` for TTFB). "gzip -9" is
the local gzip size of the same body — the two dev servers do not compress, so that
column is what a CDN/HTTP-2 host would put on the wire.

| Page | Mode | TTFB ms (min–max) | Total ms | HTML bytes | gzip -9 |
| --- | --- | --- | --- | --- | --- |
| `/` | SSR | **148** (119–278) | 150 | 64 822 | 11 302 |
| `/` | CSR | **88** (78–200) | 90 | 20 027 | 4 253 |
| `/about` | SSR | **117** (99–211) | 118 | 39 951 | 7 191 |
| `/about` | CSR | **78** (73–192) | 80 | 18 155 | 3 570 |
| `/dashboard` | SSR | **234** (204–356) | 237 | 99 688 | 11 551 |
| `/dashboard` | CSR | **126** (101–251) | 128 | 18 038 | 3 533 |

Deltas (SSR minus CSR):

| Page | TTFB | HTML | HTML gzipped | ×bytes | ×gzip |
| --- | --- | --- | --- | --- | --- |
| `/` | +60 ms | +44 795 B | +7 049 B | 3.2× | 2.7× |
| `/about` | +39 ms | +21 796 B | +3 621 B | 2.2× | 2.0× |
| `/dashboard` | +108 ms | +81 650 B | +8 018 B | 5.5× | 3.3× |

The CSR response is not empty. `/about` with the same session is 18 155 B, of which
14 401 B is the `data-page` props JSON the client renders from; SSR is 39 951 B, i.e.
21 796 B of markup wrapped around the same payload. Signed out, the same two pages are
13 007 B and 33 734 B — the difference is the shared `auth.user` props, and it scales
both modes equally.

## First paint and time to interactive

Read in the browser after each warm navigation: Paint Timing for first paint,
Navigation Timing for TTFB/DCL, and a temporary `performance.mark` pair (see below)
for the React commit and the two animation frames that follow it.

`frame2` is the time-to-interactive proxy used here: the app has committed, rendered
and presented a second frame, i.e. the main thread has yielded and the page can take
input. `frame2 − FCP` is the window in which the page is *visible but dead*.

| Page | Mode | TTFB | HTML KB | First paint | **FCP** | DOMContentLoaded | commit | frame2 | **visible-but-dead** | last JS byte |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `/` | SSR | 577 | 63 | 673 | **673** | 937 | 1703 | **1722** | 1049 ms | 1577 |
| `/` | CSR | 644 | 20 | 3103 | **3103** | 1013 | 1748 | **3087** | ~0 ms | 1661 |
| `/about` | SSR | 740 | 39 | 844 | **844** | 1129 | 1860 | **1876** | 1032 ms | 1743 |
| `/about` | CSR | 594 | 18 | 1153 | **2202** | 964 | 1650 | **2219** | ~0 ms | 1594 |
| `/dashboard` | SSR | 788 | 97 | 888 | **888** | 1159 | 2289 | **2353** | 1465 ms | 2115 |
| `/dashboard` | CSR | 738 | 18 | 1322 | **3388** | 1131 | 2200 | **3371** | ~0 ms | 2080 |

Wins (SSR relative to CSR, negative = SSR is faster):

| Page | First paint | Time to interactive |
| --- | --- | --- |
| `/` | −2 430 ms | −1 365 ms |
| `/about` | −1 358 ms | −343 ms |
| `/dashboard` | −2 500 ms | −1 018 ms |

Three things fall out of the table:

1. **The page appears 1.4–2.5 s earlier.** In every SSR cell FCP (673–888 ms) lands
   *before* `DOMContentLoaded` (937–1159 ms): the content is on screen before the
   app's JavaScript has finished executing. In every CSR cell FCP (2202–3388 ms) lands
   *after* the last JavaScript byte has arrived (1594–2080 ms) and after 0.6–1.4 s of
   further main-thread work — for CSR the content *is* the JavaScript's output.
2. **Interactivity moves 0.3–1.4 s, not 2.5 s.** Both modes are gated by the same
   bundle: 599–628 KB uncompressed across 11–20 files, arriving at 1.6–2.1 s. SSR
   renders the frame from HTML instead of building it from scratch, which is why its
   interactive moment still comes earlier — but it is not a JavaScript optimisation.
3. **SSR buys a visible-but-not-interactive window instead of a blank one.** With SSR
   the user sees the real page ~1.0–1.5 s before it responds to a click. With CSR
   there is no such window: nothing is visible until 2.2–3.4 s, and by then it works.

## Repeatability

Every sample collected in this session, including the ones taken before the temporary
marks were added:

| Page | Mode | FCP samples | Notes |
| --- | --- | --- | --- |
| `/` | SSR | 683, 674, 673, 750 | |
| `/` | CSR | 3081, 3081, 3103, 3081 | |
| `/about` | SSR | 785, 741, 844, 716 | one 1304 outlier in a run whose TTFB was also 1034 |
| `/about` | CSR | 2110, 2157, 2202, 2488 | |
| `/dashboard` | SSR | 771, 822, 888 | |
| `/dashboard` | CSR | 3207, 3003, 3376, 3388 | |

FCP is stable to about ±100 ms (SSR) and ±400 ms (CSR) once the cache is warm; browser
TTFB wanders by ±300 ms on a shared machine. The differences above are an order of
magnitude larger than that spread. Treat the `curl` medians as the server numbers and
the browser table as the user-visible ones.

## Caveats

- **The server is the unrealistic part.** `php -S` is one process: it serialises every
  request (including the ~20 asset requests a page makes) and does not compress. A real
  host terminates HTTP/2 and gzip/brotli at the edge, so both the byte penalty and the
  TTFB delta in this document are upper bounds. The `gzip -9` column is the honest
  comparison to quote — +3.6 to +8.0 KB per page.
- **The SSR render is a synchronous HTTP hop** to the node server on the same machine.
  A saturated `inertia:start-ssr` process would show up as TTFB, which is what
  `INERTIA_SSR_TIMEOUT` limits.
- **Warm, not cold.** These are repeat visits. A cold load fetches freshly hashed
  assets and the render-blocking CSS again and inflates FCP for *both* modes — one cold
  SSR `/dashboard` sample during this run: TTFB 1390 ms, FCP 1918 ms.
- **`frame2` is a proxy, not Lighthouse TTI** (which needs a quiet-window analysis and
  real input latency). Pre-load script injection is not available in this environment —
  `X-Frame-Options: DENY` blocks framing and no Playwright/Puppeteer is installed — so
  the commit/frame timestamps come from a temporary `performance.mark` block in the
  shared root provider instead of a DevTools trace.
- **Dark-mode first paint is still light.** Theme selection happens in a client effect,
  so a visitor whose preference is dark sees the light palette first whether or not SSR
  rendered the markup. SSR does not change that.
- The public layout pulls a 70 KB animation library (`gsap`), and the JS payload is
  dominated by `locale-context` at 343 KB uncompressed (107 KB gzipped). Those cost the
  same in both modes and are, by this document's numbers, where the remaining
  interactivity delay lives — not in the rendering mode.

## How to reproduce

```bash
# The twin without SSR. `--no-reload` matters: artisan serve drops non-allowlisted
# env vars when it spawns its child server.
nohup env INERTIA_SSR_ENABLED=false APP_URL=http://localhost:8001 \
  php artisan serve --host=127.0.0.1 --port=8001 --no-reload > /tmp/serve8001.log 2>&1 &

# Server response: 10 interleaved requests per cell, medians.
for i in $(seq 1 10); do
  curl -s -o /tmp/b.txt -b /tmp/jar.txt \
    -w "%{http_code} %{time_starttransfer} %{time_total} %{size_download}\n" \
    http://127.0.0.1:8000/about
done
gzip -9 -c /tmp/b.txt | wc -c
```

Browser numbers: load each page in both processes, then read

```js
performance.getEntriesByType('paint');        // first-paint, first-contentful-paint
performance.getEntriesByType('navigation')[0]; // responseStart, domContentLoadedEventEnd
performance.getEntriesByType('mark');          // app-commit, app-frame1, app-frame2
```

with this temporary block in `resources/js/root.tsx`'s `InertiaRoot` (it was removed
before this document was written; rebuild with `npm run build` while it is in place):

```ts
useEffect(() => {
    performance.mark('app-commit');
    requestAnimationFrame((f1) => {
        performance.mark('app-frame1', { startTime: f1 });
        requestAnimationFrame((f2) => performance.mark('app-frame2', { startTime: f2 }));
    });
}, []);
```
