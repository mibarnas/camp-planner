<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A star rating for one program cell within a day review.
 *
 * @property int $id
 * @property int $day_review_id
 * @property int $program_entry_id
 * @property int $rating 1..5
 * @property string|null $reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ActivityRating extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'day_review_id',
        'program_entry_id',
        'rating',
        'reason',
    ];

    /**
     * @return BelongsTo<DayReview, $this>
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(DayReview::class, 'day_review_id');
    }

    /**
     * @return BelongsTo<ProgramEntry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(ProgramEntry::class, 'program_entry_id');
    }
}
