# Kasira POS - Product Requirements Document (PRD)

## 1. Project Overview

**Project Name:** Kasira POS
**Type:** Multi-tenant Point of Sale System
**Core Functionality:** Restaurant/Cafe management system with POS, table management, inventory tracking, and customer orders
**Target Users:** Restaurant/cafe owners and cashiers
**Tech Stack:** Laravel 13, Filament 5.7.6, Livewire 4.4, MySQL, PHP 8.4

---

## 2. Technology Stack

### Core Framework
- **Laravel 13.26.1**
- **PHP 8.4**
- **MySQL Database**

### Admin Panel & UI
- **Filament 5.7.6** - Admin panel framework
- **Livewire 4.4** - Dynamic interfaces
- **Tailwind CSS 4.3** - Styling
- **Vite 8.2** - Build tool
- **Plus Jakarta Sans** - Display font

### Key Packages
- Spatie Permission (Role management)
- Laravel Excel/CSV export
- QR Code generation
- Google2FA
- Carbon (Date handling)

---

## 3. Data Architecture

### Multi-Tenancy Model
System uses **multi-tenancy** via Tenant model with `tenant_id` foreign key on all business entities.

### Core Entities

```
Tenant (Business Owner)
├── Users (Staff/Cashiers)
├── Tables (Restaurant tables)
├── Products (Menu items)
│   └── ProductIngredients (Recipe/bom)
├── Categories (Menu categories)
├── Customers (Walk-in/Members)
├── Ingredients (Raw materials)
├── Suppliers (Vendor management)
├── Sales (Transactions)
│   ├── SaleItems (Line items)
│   └── Payments (Payment records)
└── TenantReceiptSettings (Struk/kitchen slip config)
```

### Database Tables (26 total)
| Table | Purpose |
|-------|---------|
| tenants | Multi-tenant businesses |
| users | Staff accounts |
| tables | Restaurant tables |
| products | Menu items |
| categories | Menu categories |
| customers | Walk-in & members |
| ingredients | Raw materials |
| suppliers | Vendor list |
| sales | Transactions |
| sale_items | Transaction line items |
| payments | Payment records |
| product_ingredients | Recipe/bom |
| tenant_receipt_settings | Struk format |

---

## 4. Feature Specifications

### 4.1 POS (Point of Sale)
**Path:** `/admin/{tenant}/pos`

#### Capabilities
- [x] Product grid with category filtering
- [x] Search by product name/SKU/barcode
- [x] Add to cart with quantity control
- [x] Duration/hourly rate products (karaoke/etc)
- [x] Table selection (required for duration products)
- [x] Customer selection (walk-in/member)
- [x] Multiple payment methods (cash, QRIS, transfer)
- [x] Real-time checkout modal
- [x] Member point tracking
- [x] Auto-struk print on payment success
- [ ] **INCOMPLETE**: Member tier system (gold/silver/bronze)
- [ ] **INCOMPLETE**: Customer order notes/modifiers

#### Payment Flow
1. Add products → Select table → Select customer → Checkout
2. Choose payment method → Process payment
3. Success popup → Auto-print struk after 5s countdown
4. Cart clears → Ready for next order

### 4.2 Table Management
**Path:** `/admin/{tenant}/tables-overview`

#### Features
- [x] Grid view of tables with status badges
- [x] Quick stats (available/occupied/reserved)
- [x] Active table details modal
- [x] QR code generation for customer ordering
- [x] Order merging (add items to existing bill)
- [ ] **INCOMPLETE**: Reservation booking system
- [ ] **INCOMPLETE**: Table transfer between orders

### 4.3 Customer QR Order (Self-Order)
**Path:** `/order/{tenant}/{table}`

#### Capabilities
- [x] Browse menu by category
- [x] Add to cart with modifiers
- [x] Checkout flow
- [x] Payment confirmation
- [x] Success page
- [ ] **MISSING**: Order tracking/pickup notification
- [ ] **MISSING**: Customer loyalty login

### 4.4 Inventory Management

#### Ingredients (Raw Materials)
**Path:** Filament Resource

- [x] Stock tracking
- [x] Low stock alerts
- [x] Auto-deduct on product sale
- [x] Supplier linking
- [ ] **INCOMPLETE**: Auto-reorder suggestions
- [ ] **INCOMPLETE**: Supplier price history

#### Products (Menu Items)

**Types:**
1. **Fixed** - Standard products (stock-tracked)
2. **Duration/Hourly** - Time-based (karaoke, pool table, etc)

- [x] SKU/barcode support
- [x] Image upload
- [x] Category assignment
- [x] Cost/sell price
- [x] Recipe/bom management
- [x] Low stock warning

### 4.5 Reporting

#### Sales Report
**Path:** `/admin/{tenant}/sales-report`

- [x] Date range filtering
- [x] Search transactions
- [x] Pagination
- [x] Export CSV
- [x] Top products chart
- [x] Ingredient usage tracking
- [x] Payment method breakdown
- [ ] **MISSING**: Profit margin calculations
- [ ] **MISSING**: Staff performance reports

#### Ingredient Report
- [x] Low stock list
- [x] Out of stock alerts
- [x] Total stock value calculation
- [ ] **MISSING**: Supplier reorder reports

### 4.6 Customer Management

#### Customer Types
1. **Walk-in** - Anonymous, no tracking
2. **Member** - Registered with phone/email

- [x] Member points system (1 point per Rp 1,000)
- [x] Transaction history
- [x] Member tier levels
- [ ] **INCOMPLETE**: Tier benefits (discounts)
- [ ] **MISSING**: Points expiration
- [ ] **MISSING**: Referral system

### 4.7 Settings

#### Receipt Settings
- [x] Store name/phone/address/logo
- [x] Paper size (58mm/80mm)
- [x] Tax rate configuration
- [x] Toggle fields on/off
- [ ] **MISSING**: Custom header/footer text
- [ ] **MISSING**: QR code on struk

---

## 5. UI/UX Specifications

### 5.1 Design System

**Brand Color:** Red (#EF4444) + Orange accent (#F97316)

#### Tokens
```css
--brand-primary: #ef4444
--success: #10b981 (green)
--warning: #f59e0b (amber)
--danger: #ef4444 (red)
--info: #3b82f6 (blue)
```

#### Typography
- **Display Font:** Plus Jakarta Sans
- **Sizes:** 11px (labels) / 13px (body) / 14px (headings) / 24px (page titles)

#### Border Radius
- `--radius-sm: 8px` - Buttons, inputs
- `--radius-lg: 16px` - Cards, modals
- `--radius-full: 9999px` - Badges, pills

### 5.2 Component Library

| Component | States | Notes |
|-----------|--------|-------|
| Button Primary | default, hover, disabled | Gradient red-orange |
| Button Secondary | default, hover | White/bordered |
| Button Ghost | default, hover | Text only |
| Card | default, hover | Subtle shadow lift |
| Badge | success/warning/danger/info | Rounded pill |
| Input | default, focus, error | Red border on focus |
| Modal | - | Backdrop blur |
| Toast | - | Bottom-right position |

### 5.3 Admin Sidebar
- Width: 280px
- Background: Gradient brand (red-orange)
- Active state: White background, black text
- Hover: Semi-transparent white
- Font: White, 14px bold

### 5.4 Topbar
- Background: Gradient brand
- Icons: White with hover state
- Tenant selector: Glassmorphism effect

### 5.5 Customer Pages (QR Order)
- Clean, minimal design
- Product cards with image
- Cart drawer from right
- Payment confirmation modal

---

## 6. API/Integration Points

### External Services
- [x] QR code generation (table links)
- [x] Font CDN (Google Fonts)
- [ ] **MISSING**: Payment gateway (MIDTRANS/EDC integration)
- [ ] **MISSING**: SMS notification API
- [ ] **MISSING**: WhatsApp integration

### Print/Export
- [x] Receipt printing (browser print)
- [x] CSV export
- [ ] **MISSING**: Kitchen slip printer
- [ ] **MISSING**: PDF export

---

## 7. Security & Auth

### Authentication
- [x] Filament default auth
- [x] Session-based auth
- [x] Tenant scoping

### Authorization
- [x] Spatie Permission package installed
- [ ] **NOT CONFIGURED**: Role-based access control
- [ ] **NOT CONFIGURED**: Permission guards

---

## 8. Performance Considerations

### Current State
- [x] Vite asset bundling
- [x] Lazy-loaded Livewire components
- [ ] **SLOW**: No query optimization (N+1 issues possible)
- [ ] **MISSING**: Image optimization/compression
- [ ] **MISSING**: Response caching

---

## 9. Roadmap - Incomplete Features

### Phase 1 - Core POS (MVP)
- [x] Basic ordering
- [x] Table management
- [x] Customer queue

### Phase 2 - Inventory
- [ ] Auto-reorder alerts
- [ ] Supplier management
- [ ] Cost tracking

### Phase 3 - Customer Engagement
- [ ] Loyalty program tiers
- [ ] Points expiration
- [ ] Referral system
- [ ] Push notifications

### Phase 4 - Operations
- [ ] Kitchen display system (KDS)
- [ ] Staff scheduling
- [ ] Daily sales targets

### Phase 5 - Integrations
- [ ] Payment gateway
- [ ] Accounting export
- [ ] WhatsApp bot ordering

---

## 10. File Structure Key

```
app/
├── Filament/
│   ├── Pages/
│   │   ├── Dashboard.php
│   │   ├── POS.php
│   │   ├── TablesOverview.php
│   │   └── Reports/
│   │       ├── SalesReport.php
│   │       └── IngredientReport.php
│   └── Resources/
│       ├── Products/
│       ├── Ingredients/
│       ├── Customers/
│       └── Sales/
├── Livewire/
│   ├── TableQrModal.php
│   └── OrderDetailsModal.php
└── Models/ (Eloquent)
    └── 13 models

resources/
├── css/filament/admin/theme.css (Design system)
├── views/customer/ (QR order pages)
└── views/filament/ (Admin overrides)

routes/
└── web.php (Customer + Receipt routes)
```

---

## 11. Environment Configuration

```env
APP_NAME=Kasira POS
APP_ENV=local/production
DB_HOST=localhost
DB_DATABASE=kasira_laravel
QUEUE_CONNECTION=sync
SESSION_DRIVER=database
```

---

## 12. Deployment Notes

- Standard Laravel deployment
- **REQUIRED**: `npm run build` before deploy
- **REQUIRED**: `php artisan view:clear` after code changes
- **OPTIONAL**: Queue worker for async jobs

---

*Document Version: 1.0*
*Last Updated: 2026-09-08*
