# Kasira Design System

## Overview

Design system terpusat menggunakan CSS custom properties di `resources/css/filament/admin/theme.css`.

## Color Palette

### Brand Colors
```css
--brand-primary: #ef4444;       /* Red-500 */
--brand-primary-hover: ...      /* Darker shade */
--brand-primary-soft: ...       /* Lighter shade for backgrounds */
```

### Semantic Colors
```css
--success: #10b981;             /* Emerald */
--warning: #f59e0b;             /* Amber */
--danger: #ef4444;              /* Red */
--info: #3b82f6;                /* Blue */
```

### Neutral Scale
```css
--gray-50: #f8fafc;
--gray-100: #f1f5f9;
--gray-200: #e2e8f0;
--gray-300: #cbd5e1;
--gray-400: #94a3b8;
--gray-500: #64748b;
--gray-600: #475569;
--gray-700: #334155;
--gray-800: #1e293b;
--gray-900: #0f172a;
```

### Surface
```css
--bg: #f8fafc;                 /* Page background */
--surface: #ffffff;             /* Card/section background */
--border: #e2e8f0;              /* Default border color */
--text: #0f172a;                /* Primary text */
--muted: #64748b;               /* Secondary text */
--faint: #94a3b8;               /* Tertiary text */
```

## Border Radius

```css
--radius-xs: 4px;              /* Checkbox, small elements */
--radius-sm: 8px;              /* Buttons, inputs */
--radius-md: 12px;             /* Notifications */
--radius-lg: 16px;             /* Cards, sections */
--radius-xl: 20px;             /* Modals */
--radius-full: 9999px;         /* Badges, pills */
```

## Shadows

```css
--shadow-xs: 0 1px 2px rgba(15, 23, 42, 0.04);
--shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.06);
--shadow-md: 0 4px 6px rgba(15, 23, 42, 0.07);
--shadow-lg: 0 10px 15px rgba(15, 23, 42, 0.08);
--shadow-xl: 0 20px 25px rgba(15, 23, 42, 0.1);
```

## Typography

```css
--font-display: 'Plus Jakarta Sans', ui-sans-serif, system-ui;
```

## Utility Classes

### Buttons

```html
<!-- Sizes -->
<button class="btn btn-sm">Small</button>
<button class="btn btn-md">Medium</button>
<button class="btn btn-lg">Large</button>

<!-- Variants -->
<button class="btn btn-primary">Primary (gradient)</button>
<button class="btn btn-secondary">Secondary</button>
<button class="btn btn-success">Success</button>
<button class="btn btn-danger">Danger</button>
```

### Cards

```html
<div class="card">Static Card</div>
<div class="card-hover">Hover Card</div>
```

### Badges

```html
<span class="badge badge-success">Success</span>
<span class="badge badge-warning">Warning</span>
<span class="badge badge-danger">Danger</span>
<span class="badge badge-info">Info</span>
<span class="badge badge-gray">Gray</span>
```

### Alerts

```html
<div class="alert alert-success">Success message</div>
<div class="alert alert-warning">Warning message</div>
<div class="alert alert-danger">Error message</div>
<div class="alert alert-info">Info message</div>
```

### Gradients

```html
<div class="gradient-bg">Full gradient</div>
<div class="gradient-bg-shadow">Gradient with shadow</div>
<div class="gradient-bg-soft">Soft gradient (for backgrounds)</div>
<span class="gradient-text">Gradient text</span>
```

### Table

```html
<div class="section-header">Section Header</div>
<tr class="table-header">Table Header Row</tr>
<tr class="table-row">Table Data Row</tr>
```

### Layout

```html
<div class="stat-icon">Icon Container</div>
<div class="empty-state">
    <div class="empty-state-icon">Empty Icon</div>
</div>
```

### Scrollbar

```html
<div class="scrollbar-thin">Thin scrollbar</div>
<div class="kasira-scrollbar">Custom scrollbar</div>
```

### Loading

```html
<div class="spinner"></div>
```

## Rules

1. **No inline styles** - Use CSS classes defined in theme.css
2. **No hardcoded colors** - Use CSS variables
3. **No @apply chains** - Expand all chained utilities
4. **Use semantic naming** - Prefer `badge-success` over `bg-green-100`
5. **Use consistent spacing** - Use CSS variables for spacing

## File Structure

```
resources/css/filament/admin/theme.css
├── Design Tokens (lines 1-100)
├── Filament Overrides (lines 102-500)
└── Utility Classes (lines 500+)
    ├── Buttons
    ├── Cards
    ├── Gradients
    ├── Badges
    ├── Alerts
    ├── Table
    ├── Layout
    ├── Scrollbar
    └── Loading
```
