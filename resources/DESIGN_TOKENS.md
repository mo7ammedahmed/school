# Design System Tokens Reference

## Color Tokens

### Semantic Colors (Dynamic)
These tokens are dynamically set based on school branding and theme:
```css
/* Set dynamically based on school's primary color */
--color-primary: [SCHOOL_PRIMARY_COLOR]  /* e.g., #0a5c42 */

/* Calculated for optimal contrast with --color-primary */
--color-primary-foreground: [CONTRASTING_COLOR]  /* e.g., #ffffff or #000000 */

/* Set dynamically based on school's secondary color */
--color-secondary: [SCHOOL_SECONDARY_COLOR]  /* e.g., #f2efe8 */

/* Calculated for optimal contrast with --color-secondary */
--color-secondary-foreground: [CONTRASTING_COLOR]  /* e.g., #29261f or #ffffff */

/* Static semantic tokens */
--color-muted: #f2efe8
--color-muted-foreground: #74705f
--color-accent: #efecdf
--color-accent-foreground: #29261f
--color-border: #e6e1d3
--color-input: #ddd8c9
--color-ring: #0a5c42
--color-background: #faf9f5
--color-foreground: #1c1a16
--color-card: #ffffff
--color-card-foreground: #1c1a16
```

### State Colors (Static)
```css
--color-success: #15803d
--color-warning: #b45309
--color-error: #b42318
--color-info: #0e7490
```

### Brand Colors (Pine) - Static Reference Values
```css
--color-pine-50: #eef7f1
--color-pine-100: #d7ecdf
--color-pine-200: #b3d8c4
--color-pine-300: #85bda2
--color-pine-400: #579e80
--color-pine-500: #388263
--color-pine-600: #25684d
--color-pine-700: #1c533e
--color-pine-800: #0a5c42
--color-pine-900: #0c3f30
--color-pine-950: #06281e
```

### Brand Colors (Gold) - Static Reference Values
```css
--color-gold-100: #f6ecd7
--color-gold-200: #ead5a8
--color-gold-300: #dcba77
--color-gold-400: #cda253
--color-gold-500: #bb8a3d
--color-gold-600: #a06f2c
--color-gold-700: #7f5624
```

## Typography Tokens

### Font Families
```css
--font-sans: "Outfit", "IBM Plex Sans Arabic", ui-sans-serif, system-ui, sans-serif, 
             "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
--font-serif: "Fraunces", "Amiri", "Source Serif 4", ui-serif, Georgia, Cambria, serif;
--font-arabic: "IBM Plex Sans Arabic", "Outfit", ui-sans-serif, system-ui, sans-serif;
--font-display: "Fraunces", "Amiri", "Source Serif 4", ui-serif, Georgia, serif;
--font-mono: "IBM Plex Mono", ui-monospace, "SFMono-Regular", Menlo, monospace;
```

### Typography Scale
- **Display**: 3rem (48px) | 560 | 1.1 | -0.02em
- **H1**: 2.25rem (36px) | 600 | 1.2 | -0.01em
- **H2**: 1.875rem (30px) | 560 | 1.3 | -0.01em
- **H3**: 1.5rem (24px) | 560 | 1.4 | 0
- **Body**: 1rem (16px) | 400 | 1.5 | 0
- **Body Small**: 0.875rem (14px) | 400 | 1.4 | 0
- **Label**: 0.875rem (14px) | 550 | 1.2 | 0
- **Caption**: 0.75rem (12px) | 400 | 1.3 | 0
- **Table**: 0.875rem (14px) | 500 | 1.4 | 0
- **Numeric/KPI**: 1.5rem (24px) | 600 | 1.2 | -0.005em

## Radius Tokens
```css
--radius-sm: 0.375rem (6px)
--radius-md: 0.5rem (8px)
--radius-lg: 0.75rem (12px)
--radius-xl: 1rem (16px)
--radius-2xl: 1.25rem (20px)
```

## Shadow Tokens
```css
--shadow-sm: 0 1px 2px 0 rgb(28 26 22 / 0.05)
--shadow-md: 0 2px 8px -2px rgb(28 26 22 / 0.08), 0 4px 16px -6px rgb(28 26 22 / 0.06)
--shadow-lg: 0 10px 24px -8px rgb(28 26 22 / 0.12), 0 4px 8px -4px rgb(28 26 22 / 0.05)
--shadow-panel: 0 1px 2px rgb(28 26 22 / 0.04), 0 24px 48px -24px rgb(28 26 22 / 0.18)
--shadow-lift: 0 1px 2px rgb(28 26 22 / 0.05), 0 18px 32px -14px rgb(6 40 30 / 0.22)
```

## Usage Examples

### CSS
```css
.button {
  background-color: var(--color-primary);
  color: var(--color-primary-foreground);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
}
```

### JavaScript/TypeScript
```typescript
const primaryColor = getComputedStyle(document.documentElement)
  .getPropertyValue('--color-primary')
  .trim();
const primaryForeground = getComputedStyle(document.documentElement)
  .getPropertyValue('--color-primary-foreground')
  .trim();
```

### Dark Mode
Automatically applied when `.dark` class is present on html/body element.

### RTL Support
Automatically handled via `[dir="rtl"]` selectors in the CSS.

### School Branding Customization
School colors are set dynamically through the AppearanceSettingsController and applied via JavaScript in app.tsx:
1. Primary and secondary colors are stored per school in the database
2. When updated, they are validated for accessibility contrast
3. The applyAppearance function sets CSS variables for both the brand colors
   and their calculated contrasting foreground colors
4. These variables override the default values in the CSS
5. UI components use these semantic tokens ensuring consistent theming