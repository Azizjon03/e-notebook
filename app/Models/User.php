<?php

namespace App\Models;

use App\Modules\Geo\Region;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ONBOARDING_DONE = 'done';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'telegram_id',
        'telegram_username',
        'first_name',
        'last_name',
        'gender',
        'language_code',
        'region_id',
        'latitude',
        'longitude',
        'timezone',
        'onboarding_step',
        'is_admin',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'telegram_id' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'is_admin' => 'boolean',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.($this->last_name ?? ''));
    }

    public function hasFinishedOnboarding(): bool
    {
        return $this->onboarding_step === self::ONBOARDING_DONE;
    }
}
