<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Filament\Facades\Filament;
use Filament\Models\Contracts\HasTenants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IdentifyTenant
{
    /**
     * Paths that bypass tenant identification (no authentication required).
     */
    private const PUBLIC_PATHS = [
        'admin/login',
        'admin/register',
        'admin/forgot-password',
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        $path = $request->path();

        // Early return for public/auth pages - no authentication needed here
        if ($this->isPublicPath($path)) {
            return $next($request);
        }

        // Get current panel
        $panel = Filament::getCurrentOrDefaultPanel();

        // Skip if panel doesn't have tenancy
        if (! $panel->hasTenancy()) {
            return $next($request);
        }

        // Skip if route doesn't have tenant parameter
        if (! $request->route() || ! $request->route()->hasParameter('tenant')) {
            return $next($request);
        }

        $tenantParam = $request->route()->parameter('tenant');

        // Handle empty tenant parameter
        if (empty($tenantParam)) {
            $user = $this->getUser($panel);

            // No user = not authenticated, let Filament's Authenticate handle it
            if (! $user) {
                return $next($request);
            }

            // Has user but no tenant in URL - this is ok for some pages
            return $next($request);
        }

        // Validate and fix session for LiteSpeed/Hostinger
        $this->ensureSessionIsValid($request);

        // Get authenticated user - retry from database if session fails
        $user = $this->getUser($panel);

        if (! $user) {
            Log::warning('IdentifyTenant: No authenticated user in session', [
                'path' => $path,
                'tenant_param' => $tenantParam,
            ]);

            // Try session regeneration for LiteSpeed
            if ($request->hasSession()) {
                try {
                    $request->session()->regenerate();
                    $user = $this->getUser($panel);
                } catch (\Throwable $e) {
                    Log::debug('IdentifyTenant: Session regeneration failed', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Still no user - this is likely a session/auth issue on Hostinger
            // Let Authenticate middleware handle it (redirect to login)
            if (! $user) {
                Log::info('IdentifyTenant: Allowing request through, auth middleware will redirect to login');
                return $next($request);
            }
        }

        // Verify user implements HasTenants
        if (! $user instanceof HasTenants) {
            abort(404, 'User tidak memiliki akses multi-tenant.');
        }

        // Resolve tenant
        $tenant = $this->resolveTenant($tenantParam);

        if (! $tenant) {
            Log::warning('IdentifyTenant: Tenant not found', [
                'tenant_param' => $tenantParam,
            ]);
            abort(404, 'Tenant tidak ditemukan.');
        }

        // Check access permission with detailed logging
        if (! $user->canAccessTenant($tenant)) {
            Log::warning('IdentifyTenant: Access denied', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'tenant_id' => $tenant->id,
                'tenant_slug' => $tenant->slug,
                'has_super_admin' => $user->hasRole('super_admin'),
                'tenant_status' => $tenant->is_active,
            ]);
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        // Set tenant context
        Filament::setTenant($tenant);

        Log::debug('IdentifyTenant: Success', [
            'user' => $user->email,
            'tenant' => $tenant->slug,
            'path' => $path,
        ]);

        return $next($request);
    }

    /**
     * Check if path is public (no authentication required).
     */
    private function isPublicPath(string $path): bool
    {
        $normalized = trim($path, '/');

        foreach (self::PUBLIC_PATHS as $publicPath) {
            if ($normalized === $publicPath || str_starts_with($normalized, $publicPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get authenticated user safely.
     */
    private function getUser($panel): ?Model
    {
        try {
            return $panel->auth()->user();
        } catch (\Throwable $e) {
            Log::debug('IdentifyTenant: Error getting user', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Resolve tenant from parameter.
     */
    private function resolveTenant(mixed $tenantParam): ?Tenant
    {
        if (! is_string($tenantParam)) {
            $tenantParam = (string) $tenantParam;
        }

        // Try exact match first
        try {
            $tenant = Filament::getCurrentOrDefaultPanel()->getTenant($tenantParam);
            if ($tenant) {
                return $tenant;
            }
        } catch (ModelNotFoundException) {
            // Continue
        }

        // Try case-insensitive
        $decoded = rawurldecode($tenantParam);
        $normalized = strtolower(trim($decoded));

        $tenant = Tenant::whereRaw('LOWER(slug) = ?', [$normalized])->first();
        if ($tenant) {
            return $tenant;
        }

        // Try by ID
        if (is_numeric($tenantParam)) {
            return Tenant::find((int) $tenantParam);
        }

        return null;
    }

    /**
     * Ensure session is valid for LiteSpeed/Hostinger environment.
     * LiteSpeed sometimes doesn't properly maintain sessions between requests.
     */
    private function ensureSessionIsValid(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        try {
            // Check if session is started
            if (! $request->session()->isStarted()) {
                Log::warning('IdentifyTenant: Session not started on LiteSpeed, regenerating');
                $request->session()->regenerate(true);
            }

            // Verify session has required keys
            $session = $request->session();

            // Ensure CSRF token exists (signals valid session)
            if (! $session->token()) {
                Log::debug('IdentifyTenant: Session missing CSRF token, attempting regeneration');
                $session->regenerateToken();
            }

            // For LiteSpeed, ensure the session ID is valid
            $sessionId = $session->getId();
            if (empty($sessionId) || strlen($sessionId) < 16) {
                Log::warning('IdentifyTenant: Invalid session ID on LiteSpeed, regenerating');
                $session->regenerate(true);
            }
        } catch (\Throwable $e) {
            Log::debug('IdentifyTenant: Session validation error', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
