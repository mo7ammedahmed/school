# Prompt: merge Appearance + Theme into one screen (repo: mo7ammedahmed/school)

Copy everything between the rules into the AI agent you are using. It is written to be
self-contained: it names the defects visible in the screenshots, the files involved, the
contract the backend expects, and how to prove the work is done.

---

## Task

Merge the **Settings → Appearance** and **Settings → Theme** screens in this Laravel 13 +
Inertia + React (TypeScript) school-management app into **one** screen, and fix the broken
colour editor that currently ships on the Appearance page.

The current state is wrong in three ways (see attached screenshots):

1. **Two screens own the same thing.** `Settings → Theme` (`/settings/theme`) still renders its
   own editor with two side-by-side cards ("Dark mode" / "Light mode"), each repeating the same
   five colour rows, while `Settings → Appearance` (`/settings/appearance`) has a "Theme mode"
   field and an "Appearance & Theme" title. Palettes are edited in two places and only one of
   them is reachable from the main navigation.
2. **The Appearance page is nearly empty by default.** With "Show advanced options" unchecked
   the page is only a logo upload, a favicon upload, a theme-mode dropdown and a Save button —
   the school cannot see the colours its site actually uses.
3. **The "Website Colors" panel is visually broken.** Its token grid renders unlabelled colour
   swatches whose captions overlap each other ("prim**primary**cond**textgro**und", "colorSeco…",
   "colorCardForegro…"), text spills outside the card, and the "Grouped / All tokens" tabs are
   unreadable. Nothing in that panel can be edited with confidence.

## Goal

One page, one save button, no duplicated editors, every colour clearly labelled, and a live
preview of both the dashboard and the public marketing site.

## What the finished screen must look like

Single route `GET /settings/appearance`, single sidebar entry, page title
**"Appearance & Theme"** (bilingual: AR "المظهر والسمة"). Remove the duplicate
`Theme` entry from the sidebar and keep `GET /settings/theme` as a redirect to
`/settings/appearance` so bookmarks do not 404.

Order the page as sections inside one `<form>`:

1. **Brand** — school logo, favicon, and (separately, clearly captioned) the *user's* dashboard
   theme preference (Light / Dark / System). Say explicitly that the theme mode is a personal
   preference while the palettes below are school-wide.
2. **Dashboard palette** — the five headline tokens (`accent`, `background`, `surface`, `text`,
   `muted`). Do **not** render the light and dark palettes as two wide duplicate cards:
   - one card, a `Tabs`/segmented control to switch between **Light** and **Dark**, plus a
     **Reset palette** action that only resets the active mode.
   - each token is a labelled row: label, one-line explanation, swatch, hex input, and a
     **Fill: Solid | Gradient** switch (never a bare "Solid" checkbox — it reads as the opposite
     of what it does).
   - **Solid** = one flat colour plus an optional "Opaque" toggle (off = 55 % tint).
   - **Gradient** = type (Linear / Radial), angle slider (linear only), a stop list with
     colour + position + add/remove (minimum 2, maximum 6 stops) and a live CSS preview of the
     resulting value.
3. **Public website colours** — every `color*` token in the school's `theme_config`
   (`colorPrimary`, `colorSecondary`, `colorAccent`, `colorBackground`, `colorForeground`,
   `colorCard`, `colorBorder`, `colorInput`, `colorRing`, `colorLink`, `colorLinkHover`,
   `colorSidebar`, `colorSidebarForeground`, `colorMuted`, `colorMutedForeground`, `colorHeader*`,
   `colorFooter*`, `colorTable*`, `colorButton*`, …). Group them under headings — *Brand*,
   *Page surfaces*, *Text*, *Links*, *Borders & inputs*, *Sidebar*, *Header*, *Footer*,
   *Tables*, *Buttons* — each in a responsive grid (`grid gap-4 sm:grid-cols-2 xl:grid-cols-3`).
   Every control must be a swatch **and** a hex input **and** a visible label that never
   overlaps its neighbour: label above the control, `min-w-0` on grid items, `truncate`/
   `break-all` on long hex values, and no fixed pixel widths that can collide.
4. **Preview** — one card that previews the dashboard palette for the mode being edited, and one
   that previews the public site (header, button, card, footer) using the website tokens. The
   previews must update as the operator types, and must show gradients, not just flat colours.
5. **Save** — a single primary action ("Save appearance & theme") plus a per-section Reset.
   Keep the existing success flash and validation-error display.

## Backend contract — keep it working

- `App\Http\Controllers\Settings\AppearanceSettingsController`
  - `index` renders `settings/appearance` with props `appearance`, `themeConfig`, `themeModes`,
    `userThemeMode`.
  - `store` accepts: `theme` (light|dark|system), `light` / `dark` token maps (each with
    `<token>`, `<token>_solid`, `<token>_gradient`), `theme_config` (JSON string of the website
    tokens), `primary_color` / `secondary_color` / `accent_color`, `logo`, `favicon`.
    It persists palettes through `School::setThemeModes()` and the website tokens through
    `School::setThemeConfig()`, then forgets the `school-appearance:{id}` cache key.
  - Validation must accept the gradient payload (`<token>_gradient` is JSON or an empty string)
    and must not reject a form-multipart `"0"`/`"1"` for `<token>_solid`.
- `App\Http\Controllers\Settings\ThemeSettingsController` keeps **only** the per-user
  `storeMode` endpoint; `index` redirects to `settings.appearance`.
- `resources/js/lib/theme.tsx` owns the token model. Reuse and extend it rather than inventing a
  second one: `TOKEN_KEYS`, `canHoldGradient`, `parseGradient`, `normalisePalettes`,
  `serialisePalettes`, `tokenBackground`, `tokenBaseColor`, `applyPalette`, `applyTheme`,
  `ThemeProvider`, `useThemeMode`.
- `resources/js/app.tsx` applies the palettes for the active mode and must keep working for the
  public site (guests included) — a signed-out visitor toggling light/dark must not be bounced to
  the login screen.
- Tokens reach the CSS as `--color-*` (a plain colour, used by borders/rings/text) plus
  `--gradient-*` (the optional gradient, painted by the `.bg-background`, `.bg-card`,
  `.bg-primary` hooks in `resources/css/app.css`). Do not collapse the two into one variable.
- Anonymous components only: `Badge, Button, Card*, Checkbox, Dialog, EmptyState, FilterBar,
  Input, Label, PageHeader, Pagination, Select, Tabs/TabList/Tab/TabPanels/TabPanel, Textarea,
  ThemeToggle, LanguageSwitcher`. There is **no** shadcn composite `Select` — the `Select`
  primitive is a styled native `<select>`, so use `<option>` children.
- Laravel 13 uses attribute-based mass assignment (`#[Fillable([...])]`), not `$fillable`.

## Hard requirements

- One page, one save; no duplicated colour editors anywhere in the app.
- No overlapping or clipped labels at any viewport width (check 360 px, 768 px, 1280 px and
  1920 px, LTR and RTL).
- Fully bilingual EN/AR: every string this screen introduces must exist in both languages
  (`resources/js/lib/i18n/copy.ts`, where `ar` is typed against `en`, so both must be updated
  together). RTL must not mirror colour inputs or angle sliders into nonsense.
- Nothing may become unreachable: gradients must round-trip (saved, reloaded, re-edited), the
  "Opaque" flag must survive, and a school with no saved theme must still see working defaults.
- Do not add a new UI or colour dependency. No `npm`/`composer` installs.
- Do not touch unrelated files, and never commit or push.

## Tests and verification (all must pass)

- `tests/Feature/Settings/ThemeSettingsTest.php` — palettes persist per mode, a gradient
  round-trips, the old `/settings/theme` URL redirects to `/settings/appearance`, invalid colours
  are rejected.
- `tests/Feature/Settings/SettingsPagesTest.php` — every settings page renders the component that
  exists on disk (update or delete the `theme` entry to match the merge).
- `tests/Feature/Navigation/SidebarLinksTest.php` — every sidebar href resolves; the removed
  `Theme` entry must be gone from both the test list and `NAV_GROUPS`.
- `./vendor/bin/phpunit`
- `npx tsc --noEmit` (strict, `noUnusedLocals`, `noUnusedParameters` — an unused import fails the
  build, so clean up as you go)
- `npm run build`
- `./vendor/bin/pint`
- Then open the page in a browser and confirm: light/dark palettes edit and preview correctly,
  a gradient can be created and saved, the website-colour grid has no overlapping text, and the
  public site reflects the saved tokens. Screenshot both the light and dark variants.

## Definition of done

- `/settings/theme` redirects, and the sidebar shows exactly one theme-related entry.
- The Appearance screen shows brand, dashboard palette (light + dark in one card), all website
  colour tokens with readable labels, live previews, and a single Save.
- Every visible label is legible at every width; no hex text overflows its card.
- Gradient and Opaque state survive a save/reload round-trip.
- Suite is green: phpunit, tsc, build, pint.
- The diff touches only the Appearance/Theme screens, their controller(s), `lib/theme.tsx`,
  the sidebar/nav copy, the small CSS hook layer, and the tests named above.

## Deliverable

A short report: what changed, the before/after of the broken grid, screenshots (light + dark,
EN + AR), and any decision you had to make that a reviewer should know about — especially if you
dropped a control that used to exist.
