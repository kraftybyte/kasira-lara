# KasirAja - Point of Sale System
## Product Requirements Document (PRD)

**Version:** 1.0.0
**Last Updated:** September 2026
**Status:** Active Development

---

## 1. Product Overview

### 1.1 Product Name
**KasirAja** - Multi-Tenant Point of Sale System

### 1.2 Tagline
"Point of Sale modern untuk bisnis F&B Indonesia"

### 1.3 Target Users

| User Type | Description |
|-----------|-------------|
| **Restaurant Owners** | Multi-branch restaurant operators requiring centralized management |
| **Cafe Owners** | Single or multi-location cafe businesses |
| **Quick Service (QSR)** | Fast food and takeaway-focused businesses |
| **Cashiers** | Front-line staff processing customer transactions |
| **Kitchen Staff** | Back-of-house staff managing order preparation |
| **Managers** | Staff monitoring sales, inventory, and operations |

### 1.4 Core Features

- [x] **Multi-Tenant Architecture** - Support for multiple business locations
- [x] **POS Terminal** - Full-featured point of sale with cash, QRIS, and VA payments
- [x] **Customer Self-Ordering** - QR-based ordering system for dine-in customers
- [x] **Kitchen Display System (KDS)** - Real-time order management for kitchen staff
- [x] **Product Management** - Categories, products, modifiers, and variations
- [x] **Table Management** - Floor plan, reservations, and table tracking
- [x] **Customer Management** - Member loyalty program with tiered discounts
- [x] **Ingredient & Inventory** - Stock tracking with reorder alerts
- [x] **Sales Reporting** - Comprehensive analytics with PDF/CSV export
- [x] **Paywuz Integration** - QRIS and Virtual Account payment processing
- [x] **Receipt Generation** - Customizable thermal printer receipts (58mm/80mm)

---

## 2. User Flows

### 2.1 Customer QR Order Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    CUSTOMER SELF-ORDER FLOW                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────┐     ┌──────────────┐     ┌───────────────────┐   │
│  │ Customer │     │ Scan Table   │     │ Select Products   │   │
│  │ scans QR │ ──> │ QR Code      │ ──> │ from Menu         │   │
│  └──────────┘     └──────────────┘     └───────────────────┘   │
│                                                │                │
│                                                ▼                │
│  ┌──────────────┐     ┌──────────────┐     ┌───────────────┐  │
│  │ Order        │     │ Make Payment  │     │ Add to Cart   │  │
│  │ Confirmed    │ <── │ via QRIS     │ <── │ & Checkout    │  │
│  └──────────────┘     └──────────────┘     └───────────────┘  │
│                                                                  │
│  ┌──────────────┐     ┌──────────────┐                           │
│  │ Kitchen      │     │ Payment      │                           │
│  │ Receives     │ <── │ Confirmed    │                           │
│  │ Order        │     │ Notification │                           │
│  └──────────────┘     └──────────────┘                           │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

**Steps:**
1. Customer scans QR code placed on table
2. Customer browses menu by category
3. Customer adds products to cart (with optional modifiers)
4. Customer selects payment method (QRIS)
5. Customer completes payment
6. Order appears in Kitchen Display
7. Kitchen prepares order
8. Order marked as ready when complete

### 2.2 POS Cashier Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                      POS CASHIER FLOW                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────┐     ┌──────────────┐     ┌───────────────┐  │
│  │ Select Table │     │ Add Products │     │ Apply Modifiers│  │
│  │ or Take Away │ ──> │ to Cart      │ ──> │ if needed     │  │
│  └──────────────┘     └──────────────┘     └───────────────┘  │
│                                                │                │
│                                                ▼                │
│  ┌──────────────┐     ┌──────────────┐     ┌───────────────┐  │
│  │ Transaction  │     │ Handle       │     │ Select Payment│  │
│  │ Complete      │ <── │ Change       │ <── │ Method        │  │
│  └──────────────┘     └──────────────┘     └───────────────┘  │
│                                                                  │
│  ┌──────────────┐     ┌──────────────┐     ┌───────────────┐  │
│  │ Print        │     │ Cash Drawer  │     │ Receive Cash  │  │
│  │ Receipt      │ <── │ Opens        │ <── │ / QR Payment  │  │
│  └──────────────┘     └──────────────┘     └───────────────┘  │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

**Steps:**
1. Cashier selects table or Take Away mode
2. Cashier adds products to cart
3. Optional: Apply customer/member for loyalty points
4. Cashier proceeds to checkout
5. Cashier selects payment method (Cash/QRIS/VA)
6. For Cash: Enter amount received, calculate change
7. Process payment and complete transaction
8. Receipt auto-prints (thermal printer)

### 2.3 Kitchen Display Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                   KITCHEN DISPLAY FLOW                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────┐     ┌──────────────┐     ┌───────────────┐  │
│  │ New Order    │     │ Kitchen      │     │ Order Items   │  │
│  │ Appears      │ ──> │ Acknowledges │ ──> │ Confirmed     │  │
│  └──────────────┘     └──────────────┘     └───────────────┘  │
│                                                │                │
│                                                ▼                │
│  ┌──────────────┐     ┌──────────────┐     ┌───────────────┐  │
│  │ Ready for    │     │ Mark as      │     │ Preparation   │  │
│  │ Pickup       │ <── │ Ready        │ <── │ Complete      │  │
│  └──────────────┘     └──────────────┘     └───────────────┘  │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

**Order Status Flow:**
- `pending` → `preparing` → `ready` → `completed`

### 2.4 Payment Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                      PAYMENT FLOW                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│                    ┌───────────────────────┐                     │
│                    │  Payment Methods    │                     │
│                    │  ┌─────┬─────┬────┐ │                     │
│                    │  │Cash │QRIS │ VA │ │                     │
│                    └──┴──┬──┴──┬──┴─┬──┘─┘                     │
│                           │     │    │                         │
│         ┌─────────────────┘     │    └─────────────────┐       │
│         ▼                       ▼                       ▼       │
│  ┌─────────────┐        ┌─────────────┐        ┌─────────────┐  │
│  │ Cash        │        │ QRIS        │        │ Virtual     │  │
│  │ - Calculate │        │ - Generate  │        │ Account     │  │
│  │   change    │        │   QR via    │        │ - Create VA │  │
│  │ - Open       │        │   Paywuz    │        │   via       │  │
│  │   drawer     │        │ - Customer   │        │   Paywuz    │  │
│  │ - Complete   │        │   scans     │        │ - Send VA   │  │
│  │   immediate  │        │ - Confirm    │        │   number    │  │
│  └─────────────┘        │   payment    │        └─────────────┘  │
│                         └─────────────┘                           │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 3. Feature Specifications

### 3.1 Tables Management

| Feature | Status | Description |
|---------|--------|-------------|
| Table CRUD | ✅ | Create, read, update, delete tables |
| Table Status | ✅ | Track: available, active, reserved |
| Table Capacity | ✅ | Set and display seating capacity |
| QR Code Generation | ✅ | Generate unique QR per table |
| Reservations | ✅ | Book tables in advance |
| Floor Overview | ✅ | Visual grid of all tables |

**Table Status Transitions:**
```
available ──[customer sits]──> active ──[payment complete]──> available
    ↑                              │
    │                              │
    └───[reservation no-show]──────┘
```

### 3.2 Product Catalog

| Feature | Status | Description |
|---------|--------|-------------|
| Categories | ✅ | Organize products by type |
| Product Management | ✅ | Full CRUD with images |
| SKU & Barcode | ✅ | Track with SKU/barcode |
| Pricing | ✅ | Cost price, selling price |
| Stock Tracking | ✅ | Real-time inventory |
| Product Modifiers | ✅ | Add-ons and options |
| Ingredient Linkage | ✅ | Link to raw materials |
| Rate Types | ✅ | Fixed price or duration-based |

**Product Rate Types:**
- `fixed` - Standard product (e.g., food, beverage)
- `duration` - Time-based billing (e.g., hourly rental)

### 3.3 Order Management

| Feature | Status | Description |
|---------|--------|-------------|
| Cart Management | ✅ | Add, remove, update items |
| Table Orders | ✅ | Link orders to tables |
| Take Away Orders | ✅ | Orders without table |
| Order Splitting | ✅ | Multiple orders per table |
| Order Notes | ✅ | Special instructions |
| Order Cancellation | ✅ | Void with reason |
| Order History | ✅ | View past transactions |

### 3.4 Payment Methods

| Method | Status | Integration |
|--------|--------|-------------|
| **Cash** | ✅ | Direct completion |
| **QRIS** | ✅ | Paywuz API |
| **Virtual Account** | ✅ | Paywuz API (BCA, BNI, BRI, Mandiri, Permata) |
| **Manual Transfer** | ✅ | Bank account display |

**Paywuz Integration Details:**
```php
// Base URL: https://api.paywuz.id/v1
// Supported Methods:
// - POST /transactions (Dynamic QR)
// - POST /qris/static (Static QR)
// - POST /va/create (Virtual Account)
// - GET /transactions/:id (Status inquiry)
// - POST /transaction/cancel (Cancellation)
```

### 3.5 Kitchen Display

| Feature | Status | Description |
|---------|--------|-------------|
| Real-time Updates | ✅ | Live order queue |
| Order Queue | ✅ | Pending orders list |
| Status Updates | ✅ | pending → preparing → ready → completed |
| Timer Display | ✅ | Show time since order placed |
| Audio Alerts | ✅ | New order notifications |

### 3.6 Customer Loyalty

| Feature | Status | Description |
|---------|--------|-------------|
| Customer Database | ✅ | Name, phone, email, address |
| Member System | ✅ | Member codes, tiers |
| Tier Levels | ✅ | Bronze, Silver, Gold, Platinum |
| Tier Discounts | ✅ | 0%, 5%, 10%, 15% |
| Points System | ✅ | Configurable points per spending |
| Points Redemption | ✅ | Convert points to discounts |
| Transaction History | ✅ | Track customer spending |

**Member Tiers & Benefits:**
| Tier | Total Spent | Discount | Color |
|------|-------------|----------|-------|
| Bronze | < Rp 1,000,000 | 0% | Amber |
| Silver | Rp 1,000,000+ | 5% | Gray |
| Gold | Rp 5,000,000+ | 10% | Yellow |
| Platinum | Rp 10,000,000+ | 15% | Sky |

---

## 4. UI/UX Guidelines

### 4.1 Color Palette

**Primary Brand Colors:**
```
Primary Red:    #EF4444 (Primary buttons, CTAs)
Primary Orange: #F97316 (Secondary accents)
```

**Semantic Colors:**
```
Success Green:  #22C55E (Completed, available)
Warning Amber:  #F59E0B (Pending, low stock)
Danger Red:     #EF4444 (Errors, cancelled)
Info Blue:      #3B82F6 (Information)
```

**Dark Mode Support:**
- Background: `#1F2937` (Gray 800)
- Surface: `#374151` (Gray 700)
- Text Primary: `#F9FAFB` (Gray 50)
- Text Secondary: `#9CA3AF` (Gray 400)

### 4.2 Typography

**Font Family:**
```
Primary: 'Plus Jakarta Sans' (Headings, UI)
Mono: 'JetBrains Mono' (Prices, codes)
```

**Size Scale:**
| Element | Size | Weight |
|---------|------|--------|
| H1 (Page titles) | 24px | 700 |
| H2 (Section headers) | 20px | 600 |
| H3 (Card titles) | 16px | 600 |
| Body | 14px | 400 |
| Small | 12px | 400 |
| Caption | 10px | 500 |

### 4.3 Component Patterns

**Button Variants:**
```css
.btn-primary  { bg-red-500, text-white, rounded-xl }
.btn-secondary{ bg-gray-100, text-gray-900, rounded-xl }
.btn-success  { bg-emerald-500, text-white, rounded-xl }
.btn-danger   { bg-red-500, text-white, rounded-xl }
.btn-outline  { border, bg-transparent, rounded-xl }
```

**Card Styles:**
```css
.card        { bg-white, rounded-2xl, shadow-sm, border }
.card-hover  { hover:shadow-md, transition-all }
```

**Navigation:**
- Left sidebar for admin panel
- Bottom navigation for customer app
- Sticky headers with blur backdrop

### 4.4 Responsive Breakpoints

| Breakpoint | Width | Usage |
|------------|-------|-------|
| Mobile | < 640px | Customer QR ordering |
| Tablet | 640px - 1024px | POS split view |
| Desktop | > 1024px | Full admin panel |
| Large | > 1280px | Extended dashboard |

---

## 5. Technical Specifications

### 5.1 Tech Stack

**Backend:**
```
PHP:           8.4
Laravel:       13.x
Filament:      5.x (Admin Panel)
Livewire:      4.x (Reactive UI)
```

**Frontend:**
```
Tailwind CSS:  4.x
Alpine.js:     3.x (Client-side interactivity)
Vite:          8.x (Build tool)
```

**Database:**
```
MySQL:         8.x
```

**Key Packages:**
| Package | Version | Purpose |
|---------|---------|---------|
| laravel/framework | 13.26.1 | Core framework |
| filament/filament | 5.7.6 | Admin panel |
| livewire/livewire | 4.4.1 | Reactive components |
| spatie/laravel-permission | 8.3.0 | Role-based access |
| bezhansalleh/filament-shield | 4.3.1 | Filament RBAC |
| barryvdh/laravel-dompdf | 3.1.2 | PDF generation |
| chillerlan/php-qrcode | 5.0.5 | QR code generation |

### 5.2 Database Schema Overview

**Core Tables:**
```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   tenants   │────<│tenant_settings│   │  tables     │
└─────────────┘     └─────────────┘     └─────────────┘
       │                   │                   │
       │                   │                   │
       ▼                   ▼                   ▼
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   users     │     │ products    │     │ reservations│
└─────────────┘     └─────────────┘     └─────────────┘
       │                   │                   │
       │                   ▼                   │
       │            ┌─────────────┐            │
       │            │ categories  │            │
       │            └─────────────┘            │
       │                   │                   │
       ▼                   ▼                   ▼
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│    sales    │─────<│  sale_items │     │ customers   │
└─────────────┘     └─────────────┘     └─────────────┘
       │                   │                   │
       │                   ▼                   │
       │            ┌─────────────┐            │
       │            │ingredients  │            │
       │            └─────────────┘            │
       │                   │                   │
       ▼                   ▼                   │
┌─────────────┐     ┌─────────────┐            │
│  payments   │     │product_     │────────────┘
└─────────────┘     │ingredients  │
                   └─────────────┘
```

**Key Tables:**

| Table | Purpose |
|-------|---------|
| `tenants` | Multi-tenant organization |
| `users` | Staff accounts with roles |
| `tables` | Restaurant floor tables |
| `categories` | Product categories |
| `products` | Menu items |
| `product_modifiers` | Add-ons and options |
| `ingredients` | Raw material inventory |
| `product_ingredients` | Recipe linkages |
| `customers` | Customer database |
| `sales` | Transaction records |
| `sale_items` | Line items |
| `payments` | Payment records |
| `reservations` | Table bookings |
| `tenant_settings` | Store configuration |

### 5.3 API Endpoints

**Public Routes (Customer):**
```
GET  /order/{tenant}/{table}           - Show order menu
POST /order/{tenant}/{table}/checkout  - Process order
GET  /order/{tenant}/{table}/{sale}/payment     - Payment page
POST /order/{tenant}/{table}/{sale}/confirm     - Confirm payment
GET  /order/{tenant}/{table}/{sale}/success      - Success page
```

**Admin Routes:**
```
/{tenant}/dashboard          - Dashboard
/{tenant}/pos                - POS Terminal
/{tenant}/kitchen            - Kitchen Display
/{tenant}/tables             - Table Management
/{tenant}/products           - Product Management
/{tenant}/categories         - Category Management
/{tenant}/customers          - Customer Management
/{tenant}/sales              - Sales History
/{tenant}/reservations       - Reservations
/{tenant}/ingredients        - Inventory
/{tenant}/suppliers          - Suppliers
/{tenant}/users              - User Management
/{tenant}/settings           - Store Settings
/{tenant}/sales-report       - Sales Reports
/{tenant}/ingredient-report  - Ingredient Reports
```

**Webhook:**
```
POST /webhook/paywuz          - Paywuz payment notifications
```

### 5.4 Paywuz Integration

**Configuration:**
```php
// config/services.php
'paywuz' => [
    'base_url' => env('PAYWUZ_BASE_URL', 'https://api.paywuz.id/v1'),
    'merchant_name' => env('PAYWUZ_MERCHANT_NAME', 'KasirAja'),
    'api_key' => env('PAYWUZ_API_KEY'),
    'callback_url' => env('PAYWUZ_CALLBACK_URL'),
],
```

**Service Methods:**
```php
PaywuzService::createDynamicQr()    // Generate QRIS
PaywuzService::createStaticQr()    // Generate static QR
PaywuzService::createVirtualAccount() // Create VA
PaywuzService::inquiry()           // Check transaction status
PaywuzService::cancel()            // Cancel transaction
PaywuzService::getHistory()        // Transaction history
```

---

## 6. Security Requirements

### 6.1 Authentication & Authorization

- [x] **User Authentication** - Laravel's built-in auth
- [x] **Role-Based Access Control** - Spatie Laravel Permission
- [x] **Filament Shield** - Resource-level permissions
- [x] **Tenant Isolation** - All queries scoped to tenant

**Default Roles:**
| Role | Permissions |
|------|-------------|
| super_admin | Full system access |
| owner | Tenant owner |
| manager | Store management |
| cashier | POS operations |
| kitchen | Kitchen display only |

### 6.2 Data Security

- [x] **Tenant Data Isolation** - All models scoped by tenant_id
- [x] **Input Validation** - Form request validation
- [x] **SQL Injection Prevention** - Eloquent ORM with parameterized queries
- [x] **XSS Prevention** - Blade automatic escaping
- [x] **CSRF Protection** - Laravel CSRF tokens

### 6.3 API Security

- [x] **Webhook Verification** - Paywuz callback validation
- [x] **Rate Limiting** - Laravel throttling
- [x] **HTTPS Only** - Force SSL in production

---

## 7. Open Issues & Known Limitations

### 7.1 Critical Issues

| Issue | Priority | Status | Notes |
|-------|----------|--------|-------|
| Loyalty settings not persisted | High | 🔴 | New migration exists but not applied |
| Paywuz API retry logic | Medium | 🟡 | Needs exponential backoff |
| Webhook signature validation | High | 🔴 | Should validate Paywuz signatures |

### 7.2 Known Limitations

| Limitation | Workaround | Tracking |
|------------|------------|----------|
| No multi-branch reporting | Use tenant filter | Future |
| No inventory cost tracking | Manual calculation | Future |
| No kitchen printer integration | Print via browser | Future |
| No offline mode | Requires network | By design |
| QR code regeneration | Each table = unique QR | By design |

### 7.3 Pending Features

| Feature | Priority | Estimated |
|---------|----------|-----------|
| Ingredient cost per product | Medium | 1 sprint |
| Profit margin analytics | Medium | 1 sprint |
| Daily cash reconciliation | Medium | 1 sprint |
| Member card QR generation | Low | 2 sprints |
| SMS/Email notifications | Low | 2 sprints |
| Multi-language support | Low | 3 sprints |

---

## 8. Appendix

### 8.1 Invoice Number Format
```
INV-YYYYMMDD-XXXX
Example: INV-20260911-0001
```

### 8.2 Member Code Format
```
MBR-XXXXX
Example: MBR-00001
```

### 8.3 Receipt Paper Sizes
- **58mm** - Narrow thermal printers
- **80mm** - Standard thermal printers

### 8.4 Supported Banks for VA
| Bank | Code | VA Prefix |
|------|------|-----------|
| Bank BCA | bca | 880 |
| Bank BNI | bni | 881 |
| Bank BRI | bri | 002 |
| Bank Mandiri | mandiri | 886 |
| Bank Permata | permata | 013 |

### 8.5 Glossary
| Term | Definition |
|------|------------|
| QRIS | Quick Response Code Indonesian Standard - National QR payment system |
| VA | Virtual Account - Bank transfer to temporary account |
| KDS | Kitchen Display System - Digital order display for kitchen |
| POS | Point of Sale - Transaction processing system |
| Tenant | Business location/organization unit |

---

**Document Status:** Draft
**Next Review:** Monthly
**Owner:** Development Team
