<?php

namespace App\Modules\Core\I18n;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        // Demo hisob umumiy: til faqat shu mehmonning sessiyasida saqlanadi.
        $locale = $user && ! $user->is_demo ? $user->locale : null;
        $locale ??= $request->session()->get('locale');
        app()->setLocale(Locale::valid($locale));

        return $next($request);
    }
}
