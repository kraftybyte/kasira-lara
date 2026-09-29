# Kasira POS - Product Requirements Document (PRD)

## 1. Project Overview

**Project Name:** Kasira POS
**Type:** Multi-tenant Point of Sale System
**Core Functionality:** Restaurant/cafe management system with POS, table management, inventory tracking, and customer orders
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
- Paywuz Payment Gateway (QRIS, VA)

---

## 3. Data Architecture

### Multi-Tenancy Model
System uses **multi-tenancy** via Tenant model with `tenant_id` foreign key on all business entities.

### Core Entities

```
Tenant (Business Owner)
├── Users (Staff/Cashiers)
├── Tables (Restaurant tables)
│   └── Reservations (Table bookings)
├── Products (Menu items)
│   └── ProductIngredients (Recipe/bom)
├── Categories (Menu categories)
├── Customers (Walk-in/Members)
├── Ingredients (Raw materials)
├── Suppliers (Vendor management)
├── Sales (Transactions)
│   ├── SaleItems (Line items)
│   │   └── SaleItemModifiers (Toppings/extras)
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
| sale_item_modifiers | Item toppings/extras |
| payments | Payment records |
| product_ingredients | Recipe/bom |
| tenant_receipt_settings | Struk format |
| reservations | Table bookings |

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
- [x] Multiple payment methods (cash, QRIS, VA, transfer)
- [x] Real-time checkout modal
- [x] Member point tracking
- [x] Auto-struk print on payment success
- [x] Modifier support for products
- [x] Bulk counter payment mode
- [x] QRIS automatic & manual payment
- [x] Virtual Account payment
- [ ] **INCOMPLETE**: Member tier system (gold/silver/bronze)
- [ ] **INCOMPLETE**: Customer order notes/modifiers

#### Payment Flow
1. Add products → Select table → Select customer → Checkout
2. Choose payment method → Process payment
3. Success popup → Auto-print struk after 5s countdown
4. Cart clears → Ready for next order

---

### 4.2 Table Management (MEJA) ⚠️ UPDATED v2.0

#### Core Concept
Meja dikunci selama ada order aktif. Meja baru bisa dipakai setelah kasir klik **"Close Table"**.

#### Table Status
| Status | Meaning |
|--------|---------|
| `available` | Meja kosong, siap digunakan |
| `active` | Sedang digunakan (legacy, auto-set by orders) |
| `reserved` | Dipesan untuk reservasi |

#### Order-Based Logic (Primary)
Table ditampilkan berdasarkan **existence of active orders**, bukan hanya `table.status`.

| Category | Criteria | Display Label |
|----------|----------|---------------|
| **Tersedia** | `table.status='available'` DAN **TIDAK** ada orders aktif | Tersedia |
| **Terpakai** | Ada orders aktif (`closed_at IS NULL`) | Terpakai/Digunakan |
| **Dipesan** | `table.status='reserved'` DAN **TIDAK** ada orders aktif | Dipesan |

#### Sale Status Flow
| Status | Meaning | Used By |
|--------|---------|---------|
| `open` | Order terbuka, belum dibayar | POS Cash payment |
| `pending` | Menunggu pembayaran | QRIS/VA, Customer QR |
| `completed` | Sudah dibayar lunas | All payment types |
| `cancelled` | Dibatalkan | Manual/expired |

#### Close Table Flow
```
1. Kasir klik tombol "Close Table" di TablesOverview
2. Database Transaction:
   - Lock table row (FOR UPDATE)
   - Mark all sales.closed_at = now()
   - Delete sale_items
   - Delete payments
   - Set table.status = 'available'
3. Browser reload page
4. Meja muncul di kategori "Tersedia"
```

#### Pages
- **TablesOverview:** `/admin/{tenant}/tables-overview`
- **Tables CRUD:** `/admin/{tenant}/tables`
- **POS:** `/admin/{tenant}/pos`

#### Features
- [x] Grid view of tables with status badges
- [x] Quick stats (available/occupied/reserved)
- [x] Active table details modal
- [x] QR code generation for customer ordering
- [x] Order merging (add items to existing bill)
- [x] Close table with cleanup
- [x] Table reservation system
- [x] Consistent status across all pages (TablesOverview, POS, Tables CRUD)
- [ ] **INCOMPLETE**: Table transfer between orders

---

### 4.3 Customer QR Order (Self-Order)
**Path:** `/order/{tenant}/{table}`

#### Capabilities
- [x] Browse menu by category
- [x] Add to cart with modifiers
- [x] Checkout flow
- [x] Payment confirmation (QRIS, VA, Counter)
- [x] Success page
- [x] Paywuz gateway integration

#### Customer Payment Flow
```
1. Customer scan QR → /order/{tenant}/{table}
2. Pilih produk → Checkout
3. Pilih metode pembayaran:
   - QRIS → Bayar dengan QR
   - VA → Bayar via bank
   - Counter → Bayar di kasir
4. Meja DIKUNCI (bisa tambah order lagi)
5. Meja baru bisa dipakai setelah kasir close
```

---

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
- [x] Modifier support (addon, option)

---

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

---

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
- [x] Paywuz Payment Gateway (QRIS, VA)
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
- [x] Role-based access control configured

---

## 8. Performance Considerations

### Current State
- [x] Vite asset bundling
- [x] Lazy-loaded Livewire components
- [x] Database indexing on frequently queried columns
- [x] Atomic transactions for critical operations
- [ ] **MISSING**: Image optimization/compression
- [ ] **MISSING**: Response caching

---

## 9. Table Management - Technical Details (v2.0)

### Database Schema

#### tables
```sql
- id (bigint, PK)
- tenant_id (bigint, FK)
- name (varchar)
- table_number (varchar, nullable)
- status (enum: 'available', 'active', 'reserved')
- capacity (int)
- notes (text, nullable)
- is_active (boolean)
- created_at, updated_at
```

#### sales (relevant columns)
```sql
- id (bigint, PK)
- tenant_id (bigint, FK)
- table_id (bigint, FK, nullable)
- status (enum: 'open', 'pending', 'completed', 'cancelled')
- source (enum: 'pos', 'customer')
- payment_method (varchar)
- grand_total (decimal)
- closed_at (timestamp, nullable) -- set when table is closed
- created_at, updated_at
```

### Race Condition Prevention

```php
// closeTable() uses atomic lock
DB::select("
    SELECT t.id as table_id, s.id as sale_id
    FROM tables t
    LEFT JOIN sales s ON s.table_id = t.id
        AND s.closed_at IS NULL
        AND s.status != 'cancelled'
    WHERE t.id = ?
    FOR UPDATE
", [$tableId]);
```

### Consistency Rules

| Location | Query Pattern |
|----------|---------------|
| TablesOverview::availableTables | `WHERE status='available' AND id NOT IN (sales with closed_at IS NULL)` |
| TablesOverview::activeTables | `WHERE id IN (sales with closed_at IS NULL)` |
| POS::availableTables | Same as TablesOverview |
| POS::activeTables | Same as TablesOverview |
| TablesTable | `display_status` method checks `hasActiveOrders()` |

---

## 10. File Structure

```
app/
├── Filament/
│   ├── Pages/
│   │   ├── Dashboard.php
│   │   ├── POS.php
│   │   ├── Tables/
│   │   │   └── TablesOverview.php
│   │   └── Reports/
│   │       ├── SalesReport.php
│   │       └── IngredientReport.php
│   └── Resources/
│       ├── Products/
│       ├── Ingredients/
│       ├── Customers/
│       ├── Tables/
│       │   ├── TableResource.php
│       │   ├── Pages/
│       │   │   └── ListTables.php
│       │   └── Tables/
│       │       └── TablesTable.php
│       └── Sales/
├── Http/Controllers/
│   ├── CustomerOrderController.php
│   └── PaywuzWebhookController.php
├── Livewire/
│   ├── TableQrModal.php
│   └── OrderDetailsModal.php
├── Models/
│   ├── Table.php (with reservations, activeSales, hasActiveOrders)
│   ├── Sale.php
│   ├── Reservation.php
│   └── ...
└── Services/
    └── PaywuzService.php

resources/
├── css/filament/admin/theme.css
├── views/
│   ├── customer/ (QR order pages)
│   └── filament/pages/
│       ├── tables-overview.blade.php
│       └── pos-header.blade.php

routes/
└── web.php

tests/Feature/
└── CloseTableTest.php
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

## 13. Changelog

### v2.0 - Table Management Overhaul (2026-09-29)

#### Changes
1. **Consistent Table Logic** - Table availability now determined by presence of active orders, not just `table.status`
2. **Close Table Cleanup** - Sales records preserved with `closed_at` timestamp, items/payments deleted
3. **Race Condition Fix** - `closeTable()` now uses atomic database lock (FOR UPDATE)
4. **Model Enhancements** - Added `reservations()`, `activeSales()`, `hasActiveOrders()` to Table model
5. **Consistent UI** - TablesOverview, POS header, and Tables CRUD all show consistent statuses

#### Before vs After
| Aspect | Before | After |
|--------|--------|-------|
| Table Available | Just `status='available'` | `status='available'` AND no active orders |
| Table Terpakai | `status='active'` | Any table with orders (closed_at=null) |
| After Payment | Table may auto-release | Table stays locked until Close Table |
| Race Condition | Separate locks | Atomic FOR UPDATE |

---

## 14. Roadmap

### Phase 1 - Core POS (MVP)
- [x] Basic ordering
- [x] Table management (v2.0)
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

*Document Version: 2.0*
*Last Updated: 2026-09-29*
