<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAppLocale
{
    /**
     * Apply the display language chosen in app settings; fall back to config when none is set.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = AppSetting::query()->value('locale');

        if ($locale) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
