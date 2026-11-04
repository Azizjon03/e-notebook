<?php

namespace App\Modules\Sport;

use App\Modules\Core\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class GymWorkout extends Model
{
    use BelongsToUser;

    protected $fillable = ['user_id', 'program_key', 'day_index', 'date', 'completed_sets', 'finished_at'];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'completed_sets' => 'array',
            'finished_at' => 'datetime',
        ];
    }
}
