<?php

namespace App\Modules\Core\Demo;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Demo hisobda faqat ko'rish: har qanday o'zgartirish ro'yxatdan o'tishga yo'naltiradi. */
class BlockDemoWrites
{
    private const ALLOWED = ['locale', 'logout', 'demo.exit'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_demo && ! $request->isMethodSafe() && ! $request->routeIs(...self::ALLOWED)) {
            return DemoController::toRegister($request, __("Biror narsa qo'shish yoki o'zgartirish uchun ro'yxatdan o'ting."));
        }

        return $next($request);
    }
}
