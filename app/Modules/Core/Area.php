<?php

namespace App\Modules\Core;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    public const ROUTINE = 'routine';

    public const SPORT = 'sport';

    public const STUDY = 'study';

    public const ILM = 'ilm';

    public const WORK = 'work';

    public const ALL = [
        self::ROUTINE => 'Kun tartibi',
        self::SPORT => 'Sport',
        self::STUDY => 'O\'qish',
        self::ILM => 'Ilm',
        self::WORK => 'Ish',
    ];

    protected $guarded = [];

    /** Nom bazada o'zbekcha saqlanadi, chiqishda joriy tilga tarjima qilinadi. */
    protected function name(): Attribute
    {
        return Attribute::get(fn (string $value) => __($value));
    }

    public static function idFor(string $key): int
    {
        static $cache = [];

        return $cache[$key] ??= (int) static::where('key', $key)->value('id');
    }
}
