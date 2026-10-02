# Plan: Implementasi RBAC (Role-based Access Control)

## Current State

| Component | Status |
|-----------|--------|
| Spatie Permission v8.3.0 | ✅ Installed |
| HasRoles trait in User model | ✅ Active |
| Roles/Permissions tables | ✅ Exist (tenant-scoped) |
| RoleSeeder | ⚠️ Exists but NOT run |
| User Resource | ❌ Missing |
| Role Resource | ❌ Missing |
| Permission checks | ❌ Not implemented |
| Middleware | ❌ Not wired up |

## Implementation Plan

### Step 1: Update RoleSeeder (Fix Tenant Scoping)
- Modify `RoleSeeder` to create roles per tenant instead of global
- Create tenant-specific roles: owner, manager, cashier
- Seed permissions (global)

**File:** `database/seeders/RoleSeeder.php`

### Step 2: Create User Resource
Create Filament resource for managing users with role assignment.

**Files to create:**
- `app/Filament/Resources/Users/UserResource.php`
- `app/Filament/Resources/Users/Pages/ListUsers.php`
- `app/Filament/Resources/Users/Pages/CreateUser.php`
- `app/Filament/Resources/Users/Pages/EditUser.php`
- `app/Filament/Resources/Users/Pages/ViewUser.php`
- `app/Filament/Resources/Users/Tables/UsersTable.php`
- `app/Filament/Resources/Users/Schemas/UserForm.php`

### Step 3: Create Role Resource
Create Filament resource for managing roles with permission assignment.

**Files to create:**
- `app/Filament/Resources/Roles/RoleResource.php`
- `app/Filament/Resources/Roles/Pages/ListRoles.php`
- `app/Filament/Resources/Roles/Pages/CreateRole.php`
- `app/Filament/Resources/Roles/Pages/EditRole.php`
- `app/Filament/Resources/Roles/Tables/RolesTable.php`
- `app/Filament/Resources/Roles/Schemas/RoleForm.php`

### Step 4: Wire up Spatie Middleware
Update `AdminPanelProvider` to add permission middleware.

**File:** `app/Providers/Filament/AdminPanelProvider.php`

Add to `authMiddleware`:
- `RoleMiddleware` (alias: 'role')
- `PermissionMiddleware` (alias: 'permission')

### Step 5: Add Authorization to Resources
Add `canAccess()` checks to existing resources.

**Resources to update:**
- Products: Require `products.view`
- Ingredients: Require `ingredients.view`
- Customers: Require `customers.view`
- Sales: Require `sales.view`
- Tables: Require `tables.view`

### Step 6: Add Authorization to Pages
Protect sensitive pages with role/permission checks.

**Pages to protect:**
- POS: Require `sales.create`
- Reports: Require `reports.view`
- Settings: Require `settings.view`

### Step 7: Run Migration/Seeder
```bash
php artisan db:seed --class=RoleSeeder
```

## Files to Create

### User Resource
```
app/Filament/Resources/Users/
├── UserResource.php
├── Pages/
│   ├── ListUsers.php
│   ├── CreateUser.php
│   ├── EditUser.php
│   └── ViewUser.php
├── Tables/
│   └── UsersTable.php
└── Schemas/
    └── UserForm.php
```

### Role Resource
```
app/Filament/Resources/Roles/
├── RoleResource.php
├── Pages/
│   ├── ListRoles.php
│   ├── CreateRole.php
│   └── EditRole.php
├── Tables/
│   └── RolesTable.php
└── Schemas/
    └── RoleForm.php
```

## Files to Modify
- `database/seeders/RoleSeeder.php` - Fix tenant scoping
- `app/Providers/Filament/AdminPanelProvider.php` - Add middleware
- `app/Filament/Resources/Products/ProductResource.php` - Add authorization
- `app/Filament/Resources/Ingredients/IngredientResource.php` - Add authorization
- `app/Filament/Resources/Customers/CustomerResource.php` - Add authorization
- `app/Filament/Resources/Sales/SaleResource.php` - Add authorization
- `app/Filament/Resources/Tables/TableResource.php` - Add authorization
- `app/Filament/Pages/POS.php` - Add authorization
- `app/Filament/Pages/Reports/SalesReport.php` - Add authorization

## Permission Matrix

| Resource | Owner | Manager | Cashier |
|----------|-------|---------|---------|
| Products | CRUD | CRUD | View |
| Ingredients | CRUD | CRUD | - |
| Customers | CRUD | CRUD | View |
| Tables | CRUD | CRUD | View |
| Sales | CRUD | CRUD | Create, View |
| Reports | View, Export | View, Export | - |
| Settings | View, Update | View | - |
| Users | CRUD | CRUD | - |

## Rollback Plan
If issues occur:
1. Clear permissions cache: `php artisan cache:forget spatie.permission.cache`
2. Re-run seeder: `php artisan db:seed --class=RoleSeeder`
3. Test with admin user directly in database
