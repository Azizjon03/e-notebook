<?php

namespace App\Modules\Core\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Foydalanuvchiga tegishli modellar uchun: so'rovlar faqat joriy foydalanuvchi
 * yozuvlarini qaytaradi, yangi yozuvga user_id avtomatik qo'yiladi.
 * Konsol buyruqlari va job'lar (Auth yo'q) uchun cheklov qo'yilmaydi; u yerda
 * user_id ni aniq ko'rsatish kerak.
 */
trait BelongsToUser
{
    public static function bootBelongsToUser(): void
    {
        static::addGlobalScope('owner', function (Builder $query) {
            if (Auth::hasUser()) {
                $query->where($query->getModel()->qualifyColumn('user_id'), Auth::id());
            }
        });

        static::creating(function ($model) {
            if ($model->user_id === null && Auth::hasUser()) {
                $model->user_id = Auth::id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
