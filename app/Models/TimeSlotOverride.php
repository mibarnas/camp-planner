<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One day's deviation from the camp-wide daily skeleton. Times left null keep
 * the template's; no row at all means the day follows the template exactly.
 *
 * @property int $id
 * @property int $camp_day_id
 * @property int $time_slot_id
 * @property string|null $start_time
 * @property string|null $end_time
 * @property bool $is_hidden
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TimeSlotOverride extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'camp_day_id',
        'time_slot_id',
        'start_time',
        'end_time',
        'is_hidden',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_hidden' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<CampDay, $this>
     */
    public function day(): BelongsTo
    {
        return $this->belongsTo(CampDay::class, 'camp_day_id');
    }

    /**
     * @return BelongsTo<TimeSlot, $this>
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class, 'time_slot_id');
    }
}
