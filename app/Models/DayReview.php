<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One camp leader's review of a single camp day.
 *
 * @property int $id
 * @property int $camp_day_id
 * @property int $user_id
 * @property int|null $camp_rating last day only, 1..5
 * @property string|null $camp_reason
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DayReview extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'camp_day_id',
        'user_id',
        'camp_rating',
        'camp_reason',
        'notes',
    ];

    /**
     * @return BelongsTo<CampDay, $this>
     */
    public function day(): BelongsTo
    {
        return $this->belongsTo(CampDay::class, 'camp_day_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ActivityRating, $this>
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(ActivityRating::class);
    }
}
