<?php

namespace App\Modules\Ilm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingLog extends Model
{
    protected $fillable = ['book_id', 'date', 'from_page', 'to_page', 'chapter'];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d'];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function pages(): int
    {
        return max(0, $this->to_page - $this->from_page);
    }
}
