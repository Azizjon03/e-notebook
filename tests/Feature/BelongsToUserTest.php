<?php

use App\Models\User;
use App\Modules\Core\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OwnedNote extends Model
{
    use BelongsToUser;

    protected $table = 'owned_notes';

    protected $guarded = [];
}

beforeEach(function () {
    Schema::create('owned_notes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained();
        $table->string('body');
        $table->timestamps();
    });
});

it('only returns the current user\'s rows', function () {
    [$a, $b] = User::factory()->count(2)->create();
    OwnedNote::create(['user_id' => $a->id, 'body' => 'A']);
    OwnedNote::create(['user_id' => $b->id, 'body' => 'B']);

    $this->actingAs($a);

    expect(OwnedNote::pluck('body')->all())->toBe(['A'])
        ->and(OwnedNote::find(2))->toBeNull();
});

it('fills user_id from the logged in user', function () {
    $a = User::factory()->create();
    $this->actingAs($a);

    expect(OwnedNote::create(['body' => 'x'])->user_id)->toBe($a->id);
});
