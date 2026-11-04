<?php

namespace App\Modules\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Ball hisobi: har o'zgarish points_ledger ga yoziladi, jami va daraja undan hisoblanadi.
 */
class Points
{
    public function award(User $user, Model $source, string $reason, int $points): void
    {
        if ($points <= 0) {
            return;
        }

        DB::table('points_ledger')->insertOrIgnore([
            'user_id' => $user->id,
            'source_type' => $source->getMorphClass(),
            'source_id' => $source->getKey(),
            'reason' => $reason,
            'points' => $points,
            'created_at' => now(),
        ]);
    }

    public function revoke(Model $source, string $reason): void
    {
        DB::table('points_ledger')
            ->where('source_type', $source->getMorphClass())
            ->where('source_id', $source->getKey())
            ->where('reason', $reason)
            ->delete();
    }

    public function total(User $user): int
    {
        return (int) DB::table('points_ledger')->where('user_id', $user->id)->sum('points');
    }

    public function today(User $user): int
    {
        $start = now($user->timezone)->startOfDay()->utc();

        return (int) DB::table('points_ledger')->where('user_id', $user->id)->where('created_at', '>=', $start)->sum('points');
    }

    /**
     * @return array{level:int, total:int, into:int, needed:int}
     */
    public static function level(int $total): array
    {
        $base = (float) config('points.level_base');
        $factor = (float) config('points.level_factor');

        $level = 0;
        $need = $base;
        $left = $total;
        while ($left >= $need) {
            $left -= (int) round($need);
            $level++;
            $need *= $factor;
        }

        return ['level' => $level, 'total' => $total, 'into' => $left, 'needed' => (int) round($need)];
    }
}
