# School Platform Design System

## Overview
This document outlines the design system for the Laravel 13 + Inertia React school platform. The system is built on Tailwind CSS v4 with CSS custom properties for design tokens, following a light-first approach with dark mode support via class-based variants.

## Design Tokens

### Color System

#### Semantic Colors
These colors are used for specific UI purposes and maintain meaning across light/dark modes.
The primary and secondary colors are dynamically set based on the school's branding colors,
with appropriate foreground colors calculated for accessibility.

| Token | Description | Light Value (Default) | Dark Value (Default) | Dynamic? |
|-------|-------------|----------------------|----------------------|----------|
| `--color-primary` | Primary brand color (from school settings) | `#0a5c42` | `#0a5c42` | Yes |
| `--color-primary-foreground` | Text/icon color on primary (calculated for contrast) | `#ffffff` | `#ffffff` | Yes |
| `--color-secondary` | Secondary brand color (from school settings) | `#f2efe8` | `#2a302d` | Yes |
| `--color-secondary-foreground` | Text/icon color on secondary (calculated for contrast) | `#29261f` | `#f4f5f2` | Yes |
| `--color-muted` | Muted background/surface | `#f2efe8` | `#2a302d` | No |
| `--color-muted-foreground` | Muted text | `#74705f` | `#b1bbb4` | No |
| `--color-accent` | Accent background | `#efecdf` | `#303a35` | No |
| `--color-accent-foreground` | Text/icon color on accent | `#29261f` | `#f4f5f2` | No |
| `--color-border` | Border color | `#e6e1d3` | `#3b4540` | No |
| `--color-input` | Input background | `#ddd8c9` | `#4a5750` | No |
| `--color-ring` | Focus ring color | `#0a5c42` | `#0a5c42` | No |
| `--color-background` | Page background | `#faf9f5` | `#171a19` | No |
| `--color-foreground` | Page text | `#1c1a16` | `#f4f5f2` | No |
| `--color-card` | Card/surface background | `#ffffff` | `#202523` | No |
| `--color-card-foreground` | Text in cards | `#1c1a16` | `#f4f5f2` | No |

#### Semantic State Colors
Used for conveying state and feedback.

| Token | Description | Value (same in light/dark) |
|-------|-------------|----------------------------|
| `--color-success` | Success state | `#15803d` |
| `--color-warning` | Warning state | `#b45309` |
| `--color-error` / `--color-destructive` | Error/destructive state | `#b42318` |
| `--color-info` | Informational state | `#0e7490` |

#### Brand Colors
School-specific brand colors that remain consistent across light/dark modes.

##### Pine Shades (Primary Brand)
| Token | Value |
|-------|-------|
| `--color-pine-50` | `#eef7f1` |
| `--color-pine-100` | `#d7ecdf` |
| `--color-pine-200` | `#b3d8c4` |
| `--color-pine-300` | `#85bda2` |
| `--color-pine-400` | `#579e80` |
| `--color-pine-500` | `#388263` |
| `--color-pine-600` | `#25684d` |
| `--color-pine-700` | `#1c533e` |
| `--color-pine-800` | `#0a5c42` (primary) |
| `--color-pine-900` | `#0c3f30` |
| `--color-pine-950` | `#06281e` |

##### Gold Shades (Accent Brand)
| Token | Value |
|-------|-------|
| `--color-gold-100` | `#f6ecd7` |
| `--color-gold-200` | `#ead5a8` |
| `--color-gold-300` | `#dcba77` |
| `--color-gold-400` | `#cda253` |
| `--color-gold-500` | `#bb8a3d` |
| `--color-gold-600` | `#a06f2c` |
| `--color-gold-700` | `#7f5624` |

#### Legacy Gray Mapping
For backward compatibility with legacy gray-* usage.

| Token | Light Value | Dark Value |
|-------|-------------|------------|
| `--color-gray-50` | `#faf9f6` | `#16130f` |
| `--color-gray-100` | `#f4f1ea` | `#1c1b19` |
| `--color-gray-200` | `#e8e3d8` | `#232019` |
| `--color-gray-300` | `#d8d2c3` | `#2a2824` |
| `--color-gray-400` | `#b4ad9b` | `#47423b` |
| `--color-gray-500` | `#918b78` | `#655f57` |
| `--color-gray-600` | `#6f6a59` | `#847d73` |
| `--color-gray-700` | `#544f42` | `#a29a90` |
| `--color-gray-800` | `#39352c` | `#bfb8af` |
| `--color-gray-900` | `#232019` | `#d9d4cd` |
| `--color-gray-950` | `#16130f` | `#f2f0eb` |

### Typography

#### Font Families
| Token | Description | Value |
|-------|-------------|-------|
| `--font-sans` | Default sans-serif | `"Outfit", "IBM Plex Sans Arabic", ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"` |
| `--font-serif` | Serif font | `"Fraunces", "Amiri", "Source Serif 4", ui-serif, Georgia, Cambria, serif` |
| `--font-arabic` | Arabic-optimized sans | `"IBM Plex Sans Arabic", "Outfit", ui-sans-serif, system-ui, sans-serif` |
| `--font-display` | Display/heading font | `"Fraunces", "Amiri", "Source Serif 4", ui-serif, Georgia, serif` |
| `--font-mono` | Monospace font | `"IBM Plex Mono", ui-monospace, "SFMono-Regular", Menlo, monospace` |

#### Typography Contract
Defined semantic roles for consistent typographic hierarchy.

| Role | Font Size | Font Weight | Line Height | Letter Spacing | Usage |
|------|-----------|-------------|-------------|----------------|-------|
| **Display** | 3rem (48px) | 560 | 1.1 | -0.02em | Marketing headlines, public site hero text |
| **H1** | 2.25rem (36px) | 600 | 1.2 | -0.01em | Page titles, major section headers |
| **H2** | 1.875rem (30px) | 560 | 1.3 | -0.01em | Section headers, card titles |
| **H3** | 1.5rem (24px) | 560 | 1.4 | 0 | Subsection headers, form sections |
| **Body** | 1rem (16px) | 400 | 1.5 | 0 | Main body text, paragraphs |
| **Body Small** | 0.875rem (14px) | 400 | 1.4 | 0 | Secondary text, helper text |
| **Label** | 0.875rem (14px) | 550 | 1.2 | 0 | Form labels, item labels |
| **Caption** | 0.75rem (12px) | 400 | 1.3 | 0 | Captions, timestamps, footnotes |
| **Table** | 0.875rem (14px) | 500 | 1.4 | 0 | Table body text |
| **Numeric/KPI** | 1.5rem (24px) | 600 | 1.2 | -0.005em | Numbers, metrics, statistics |

#### Arabic/RTL Considerations
- Body text in RTL layouts uses letter-spacing: 0 !important
- Display headings in RTL use letter-spacing: 0 and font-weight: 600
- Marquee animations reverse direction in RTL
- Origin points for transforms and animations are flipped in RTL

### Spacing
Uses Tailwind's default spacing scale (0.25rem increments) with semantic aliases where needed.

| Token | Value | Usage |
|-------|-------|-------|
| `--spacing-px` | 1px | Hairline borders |
| `--spacing-0` | 0 | No spacing |
| `--spacing-0.5` | 0.125rem (2px) | Tight spacing |
| `--spacing-1` | 0.25rem (4px) | Base unit |
| `--spacing-1.5` | 0.375rem (6px) | Compact spacing |
| `--spacing-2` | 0.5rem (8px) | Standard spacing |
| `--spacing-2.5` | 0.625rem (10px) |  |
| `--spacing-3` | 0.75rem (12px) | Form gaps, icon spacing |
| `--spacing-3.5` | 0.875rem (14px) |  |
| `--spacing-4` | 1rem (16px) | Standard padding/margin |
| `--spacing-5` | 1.25rem (20px) |  |
| `--spacing-6` | 1.5rem (24px) | Comfortable spacing |
| `--spacing-7` | 1.75rem (28px) |  |
| `--spacing-8` | 2rem (32px) | Section spacing |
| `--spacing-9` | 2.25rem (36px) |  |
| `--spacing-10` | 2.5rem (40px) | Large spacing |
| `--spacing-11` | 2.75rem (44px) | Input heights |
| `--spacing-12` | 3rem (48px) |  |
| `--spacing-14` | 3.5rem (56px) |  |
| `--spacing-16` | 4rem (64px) | Section margins |

### Radii
| Token | Value | Usage |
|-------|-------|-------|
| `--radius-sm` | 0.375rem (6px) | Small buttons, inputs |
| `--radius-md` | 0.5rem (8px) | Default radius |
| `--radius-lg` | 0.75rem (12px) | Larger buttons, cards |
| `--radius-xl` | 1rem (16px) | Prominent elements |
| `--radius-2xl` | 1.25rem (20px) | Very prominent elements |

### Shadows
| Token | Description | Value |
|-------|-------------|-------|
| `--shadow-sm` | Small shadow | `0 1px 2px 0 rgb(28 26 22 / 0.05)` |
| `--shadow-md` | Medium shadow | `0 2px 8px -2px rgb(28 26 22 / 0.08), 0 4px 16px -6px rgb(28 26 22 / 0.06)` |
| `--shadow-lg` | Large shadow | `0 10px 24px -8px rgb(28 26 22 / 0.12), 0 4px 8px -4px rgb(28 26 22 / 0.05)` |
| `--shadow-panel` | Panel/elevation shadow | `0 1px 2px rgb(28 26 22 / 0.04), 0 24px 48px -24px rgb(28 26 22 / 0.18)` |
| `--shadow-lift` | Lifted element shadow | `0 1px 2px rgb(28 26 22 / 0.05), 0 18px 32px -14px rgb(6 40 30 / 0.22)` |

### Motion
| Token | Description | Value |
|-------|-------------|-------|
| `--ease-out-expo` | Easing function | `cubic-bezier(0.16, 1, 0.3, 1)` |
| `--animate-marquee` | Marquee animation | `marquee 46s linear infinite` |
| `--animate-marquee-slow` | Slow marquee | `marquee 80s linear infinite` |
| `--animate-float` | Float animation | `float 7s ease-in-out infinite` |
| `--animate-shimmer` | Shimmer effect | `shimmer 2.2s linear infinite` |
| `--animate-pulse-soft` | Soft pulse | `pulse-soft 3.2s ease-in-out infinite` |

## Component Guidelines

### Buttons
Use the `.btn` base class with variant classes:
- `.btn-primary` - Primary action
- `.btn-secondary` - Secondary background
- `.btn-outline` - Outline style
- `.btn-ghost` - Transparent background
- `.btn-destructive` - Destructive action
- Size variants: `.btn-sm`, `.btn-lg`

### Inputs & Form Elements
Use the `.input` base class for text inputs, textareas, and selects.
Labels use the `.label` class.
Validation states should use appropriate semantic colors:
- Success: `--color-success`
- Warning: `--color-warning`
- Error: `--color-error`

### Cards
Use the Card component from `@/components/ui/card` which applies:
- Background: `--color-card`
- Foreground: `--color-card-foreground`
- Border: `--color-border`
- Radius: `--radius-md`
- Shadow: `--shadow-sm`

### Focus Styles
All interactive elements should use the global `:focus-visible` style:
```css
:focus-visible {
  outline: 2px solid color-mix(in srgb, var(--color-ring) 65%, transparent);
  outline-offset: 2px;
}
```

## Implementation Guidelines

### Using Tokens in CSS
Use CSS var() functions to reference tokens:
```css
.custom-element {
  background-color: var(--color-card);
  color: var(--color-foreground);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
}
```

### Using Tokens in JavaScript/TS
Access tokens via `getComputedStyle`:
```typescript
const getToken = (token: string): string => {
  return getComputedStyle(document.documentElement).getPropertyValue(token).trim();
};

// Usage
const primaryColor = getToken('--color-primary');
```

### Dark Mode
Dark mode is applied via the `.dark` class on the html or body element.
Tokens automatically adjust when this class is present.

### RTL Support
The `[dir="rtl"]` attribute is used for right-to-left layouts.
Specific overrides are defined in the CSS for:
- Letter spacing
- Animation directions
- Transform origins

## Migration & Compatibility
- Existing Tailwind utility classes (text-*, bg-*, border-*, etc.) continue to work
- Legacy class helpers (.btn, .input, .label, .card) are preserved in @layer components
- Gray scale tokens are mapped to warm neutrals for backward compatibility
- All existing components should continue to function without changes

## Future Enhancements
1. Create a token generation script to sync with Tailwind config
2. Add CSS variable fallback mechanisms for older browsers
3. Create a design token documentation site
4. Add component examples and usage guidelines
5. Create a Figma design system file matching these tokens