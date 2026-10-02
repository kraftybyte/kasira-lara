# KASIRA LARAVEL - 403 FORBIDDEN FIX ON HOSTINGER

## The Problem

After login at `/admin/login`, all routes return **403 Forbidden**. This happens because:

1. **Session not persisting** between login and authenticated requests on Hostinger LiteSpeed
2. **User not assigned to any tenant** in the `tenant_user` pivot table
3. **IdentifyTenant middleware** cannot verify user has access to the tenant

## Root Causes

### 1. LiteSpeed Session Issues
Hostinger's LiteSpeed web server doesn't always maintain sessions properly between requests. The `IdentifyTenant` middleware tries to retrieve the authenticated user but fails.

### 2. Missing Tenant Assignment
When a user is created/registered, they must be assigned to a tenant via `tenant_user` table:
```sql
INSERT INTO tenant_user (user_id, tenant_id, status) VALUES (1, 1, 'active');
```

Without this, `User::canAccessTenant()` returns false → 403 error.

### 3. SESSION_SECURE_COOKIE Misconfiguration
If `SESSION_SECURE_COOKIE=true` on Hostinger but the domain isn't properly HTTPS, the session cookie won't persist.

---

## QUICK FIX (5 minutes)

### Step 1: SSH into Hostinger
```bash
ssh username@your-domain.com
cd public_html
```

### Step 2: Run the deployment fix
```bash
bash deploy-hostinger.sh
```

This script will:
- Fix `.env` session settings
- Clear all caches
- Run migrations
- Truncate old sessions

### Step 3: Verify the fix
```bash
bash fix-403-forbidden.sh
```

This will:
- Check user/tenant assignments
- Assign all users to all tenants
- Clear caches again

### Step 4: Test
1. Clear browser cookies for the domain
2. Visit `https://your-domain.com/admin/login`
3. Log in
4. You should now see the dashboard

---

## MANUAL FIX (if scripts don't work)

### 1. Update .env
```bash
# SSH in and edit .env:
nano .env
```

Find and update these lines:
```env
APP_DEBUG=false
APP_ENV=production

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=null
SESSION_SAME_SITE=lax
```

**Critical:** `SESSION_SECURE_COOKIE=null` (not `true`) on Hostinger shared hosting.

### 2. Clear caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
php artisan optimize
```

### 3. Assign user to tenant
```bash
php artisan tinker
```

Then run:
```php
$user = User::first();
$tenant = Tenant::first();
$user->tenants()->attach($tenant->id, ['status' => 'active']);
exit()
```

---

## DEBUGGING

### Check logs
```bash
tail -f storage/logs/laravel.log
```

Look for:
- `IdentifyTenant: No authenticated user`
- `IdentifyTenant: Access denied`
- Database connection errors

### Verify database
```bash
php artisan tinker
```

```php
// Check users
User::all();

// Check tenants
Tenant::all();

// Check if user is assigned to tenant
User::first()->tenants;

// Check specific user access
User::first()->canAccessTenant(Tenant::first());

exit()
```

### Force regenerate session
```bash
php artisan tinker --execute="
DB::table('sessions')->truncate();
DB::table('cache')->truncate();
echo 'Sessions and cache cleared';
"
```

---

## COMMON ISSUES & SOLUTIONS

### Issue: Still 403 after fix
**Solution:**
1. Make sure you cleared **browser cookies** for the domain
2. Try an **incognito/private window**
3. Check `storage/logs/laravel.log` for the actual error
4. Run `php artisan tinker` and check: `User::first()->tenants`

### Issue: Can't SSH into Hostinger
**Solution:**
1. Go to Hostinger control panel → Web Hosting → Advanced → SSH Access
2. Enable SSH if disabled
3. Check your SSH password/key
4. Use WinSCP or Filezilla to upload files instead

### Issue: Migrations fail
**Solution:**
```bash
# Check database name and credentials in .env
grep "^DB_" .env

# Test connection
php artisan tinker --execute="echo DB::connection()->getDatabaseName();"

# If it fails, update .env with correct credentials
```

### Issue: Still seeing "Session not started" errors
**Solution:**
This is a Hostinger LiteSpeed bug. Try:
```bash
# Force session driver change
php artisan tinker --execute="
config(['session.driver' => 'file']);
config(['session.files' => storage_path('framework/sessions')]);
echo 'Session driver set to file-based temporarily';
"
```

---

## PERMANENT PREVENTION

### For new users during registration:
Update your registration controller to auto-assign new users to their tenant:

```php
// In RegisterController or similar
$user = User::create([...]);

// Auto-assign to tenant
if ($tenant) {
    $user->tenants()->attach($tenant->id, ['status' => 'active']);
}
```

### For user management:
Always ensure `tenant_user` relationship is created when adding users:

```php
// In User creation/assignment logic
DB::table('tenant_user')->insert([
    'user_id' => $user->id,
    'tenant_id' => $tenant->id,
    'status' => 'active',
    'created_at' => now(),
    'updated_at' => now(),
]);
```

---

## FILES INVOLVED

- `app/Http/Middleware/IdentifyTenant.php` - Enhanced logging for debugging
- `deploy-hostinger.sh` - Automated deployment fix
- `fix-403-forbidden.sh` - Automated 403 fix
- `.env.example` - Updated with correct session settings

---

## SUPPORT

If you're still having issues:

1. **Collect debug info:**
   ```bash
   php artisan config:show app
   php artisan config:show session
   tail -100 storage/logs/laravel.log
   ```

2. **Check .htaccess rewrites:**
   - File: `public/.htaccess`
   - Should contain Apache URL rewrite rules
   - Hostinger LiteSpeed sometimes ignores this

3. **Contact Hostinger support:**
   - Ask them to verify: "AllowOverride All" is enabled for public_html
   - Ask them to check LiteSpeed error logs if 403 persists
   - Provide them with your `storage/logs/laravel.log` excerpt

---

**Last Updated:** 2025  
**For:** Kasira POS on Hostinger LiteSpeed
