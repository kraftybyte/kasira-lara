# Custom Domain Feature - Implementation Plan

## Overview
Enable tenants to use their own custom domain (e.g., `resto-abc.com`) instead of slug-based URL (`kasira.app/kasira-demo`).

## Current Architecture
- **URL Pattern**: `/admin/{tenant-slug}/...`
- **Tenant Resolution**: Via `IdentifyTenant` middleware using slug
- **Models**: `Tenant` (basic info), `TenantSetting` (config)

---

## Implementation Phases

### Phase 1: Database & Model Changes

#### 1.1 Migration - Add `custom_domain` to Tenant
```php
// Add to tenants table:
// - custom_domain (string, nullable, unique)
// - domain_verified_at (timestamp, nullable)
// - domain_verification_token (string, nullable)
```

#### 1.2 Update Tenant Model
- Add `custom_domain` to `$fillable`
- Add `domain_verified_at` and `domain_verification_token` casts
- Add helper methods:
  - `hasCustomDomain(): bool`
  - `isDomainVerified(): bool`
  - `generateVerificationToken(): string`
  - `verifyDomain(token): bool`

---

### Phase 2: Domain Verification System

#### 2.1 DNS Verification Flow
1. User enters domain (e.g., `resto-abc.com`)
2. System generates unique TXT record value as verification token
3. User adds TXT record to their DNS
4. System checks DNS and verifies ownership
5. On success, domain is marked as verified

#### 2.2 Create DomainVerificationService
```
app/Services/DomainVerificationService.php
- generateToken(): string
- getVerificationInstructions(domain): array
- verifyDomain(domain, tenant): bool
- checkTxtRecord(domain, expectedToken): bool
```

---

### Phase 3: Tenant Resolution Middleware

#### 3.1 Create Custom Tenant Resolver
```php
app/Http/Middleware/ResolveTenantFromDomain.php

Logic:
1. Check request host
2. If host == main app domain → use slug from URL (existing behavior)
3. If host != main domain → lookup tenant by custom_domain
4. If tenant found and verified → set tenant context
5. If not found → abort 404
```

#### 3.2 Update AdminPanelProvider
- Add custom middleware to handle domain resolution

---

### Phase 4: Settings UI

#### 4.1 Add "Custom Domain" Tab to TenantSettings
```
Tab: "Domain" / "Domain Kustom"
├── Section: "Domain Settings"
│   ├── TextInput: custom_domain (e.g., resto-abc.com)
│   └── Toggle: force_https (default: true)
│
├── Section: "Verification Status"
│   ├── Status Badge: Verified / Pending / Not Set
│   ├── Verification Token Display
│   ├── "Copy Verification Record" button
│   └── "Verify Now" button
│
└── Section: "DNS Instructions"
    └── Step-by-step guide to add TXT record
```

#### 4.2 Verification Actions
- `verifyDomain()` - Check DNS and verify
- `resendVerification()` - Generate new token

---

### Phase 5: SSL/HTTPS Handling

#### 5.1 Options for SSL
| Option | Pros | Cons | Complexity |
|--------|------|------|------------|
| Manual SSL | Works with any provider | Tenant must configure | Low |
| Let's Encrypt (DIY) | Free, auto-renew | Needs server access | Medium |
| CloudFlare | Easy, free | Tenant needs CloudFlare | Low |
| SaaS SSL Service | Fully managed | Extra cost | High |

#### 5.2 Recommended Approach
**Phase 1**: Manual SSL + clear instructions
- Tenant manages their own SSL
- App provides clear DNS configuration steps
- Display SSL status in settings

**Phase 2** (future): Integration with:
- Let's Encrypt auto-provision via API
- CloudFlare SSL
- AWS ACM

---

### Phase 6: URL Generation Updates

#### 6.1 Update Tenant Model
```php
public function getAppUrl(string $path = ''): string
{
    if ($this->hasCustomDomain() && $this->isDomainVerified()) {
        $scheme = $this->force_https ? 'https' : 'http';
        return rtrim("{$scheme}://{$this->custom_domain}", '/') . '/' . ltrim($path, '/');
    }

    return url("/admin/{$this->slug}/" . ltrim($path, '/'));
}
```

#### 6.2 Update All URL References
- QR Code generation (order pages)
- Email notifications
- Receipt URLs

---

### Phase 7: Customer Order Pages

#### 7.1 Update Order Routes
Current: `/order/{tenant-slug}/{table}`
New:
- `/order/{tenant-slug}/{table}` (keep for backward compat)
- `/{table}` (when accessed via custom domain)

#### 7.2 Update CustomerOrderController
- Detect tenant from domain or slug
- Redirect to correct tenant context

---

## File Changes Summary

### New Files
```
app/
├── Http/Middleware/ResolveTenantFromDomain.php
├── Services/DomainVerificationService.php
└── Notifications/DomainVerificationFailed.php

database/migrations/
└── xxxx_add_custom_domain_to_tenants.php

resources/views/filament/pages/settings/
└── partials/domain-settings.blade.php
```

### Modified Files
```
app/Models/Tenant.php           - Add fields & helpers
app/Filament/Pages/Settings/TenantSettings.php  - Add domain tab
app/Providers/Filament/AdminPanelProvider.php   - Add middleware
routes/web.php                  - Update order routes
```

---

## User Flow

```
1. Owner goes to Settings → Domain
2. Enters "resto-abc.com"
3. System shows:
   - TXT record to add: kasira-verification-abc123.rest-abc.com
   - TTL: 5 minutes
4. Owner adds TXT record to DNS
5. Owner clicks "Verify Now"
6. System checks DNS → Success
7. Domain marked as verified
8. Owner configures SSL on their server
9. Done! Customer can access via resto-abc.com/admin
```

---

## Edge Cases to Handle

1. **Duplicate domain** - Prevent two tenants using same domain
2. **Domain expiry** - Show warning if domain DNS check fails
3. **Subdomain vs Apex** - Support both `shop.rest-abc.com` and `rest-abc.com`
4. **WWW redirect** - Optionally redirect `www.` to apex domain
5. **HTTP vs HTTPS** - Detect and handle both protocols
6. **Tenant without domain** - Gracefully fallback to slug-based URL

---

## Testing Scenarios

1. Set custom domain → see verification token
2. Add correct TXT record → verification passes
3. Add incorrect TXT record → verification fails with message
4. Access via custom domain → tenant resolves correctly
5. Access via slug URL → still works (backward compat)
6. Domain expires → graceful fallback to slug URL
7. Two tenants try same domain → error prevented
