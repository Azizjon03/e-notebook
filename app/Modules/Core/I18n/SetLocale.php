<?php

namespace App\Modules\Core\I18n;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale ?? $request->session()->get('locale');
        app()->setLocale(Locale::valid($locale));

        return $next($request);
    }
}
