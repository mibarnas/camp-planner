<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A leader of one camp. Leaders with an account are linked to a user; the rest
 * are plain names the camp can still point at.
 *
 * @property int $id
 * @property int $camp_id
 * @property int|null $user_id
 * @property string $name
 * @property string|null $color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CampLeader extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'camp_id',
        'user_id',
        'name',
        'color',
    ];

    /**
     * @return BelongsTo<Camp, $this>
     */
    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasAccount(): bool
    {
        return $this->user_id !== null;
    }
}
