<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $camp_id
 * @property Carbon $date
 * @property bool $is_trip
 * @property string|null $trip_name
 * @property string|null $name_days
 * @property string|null $birthdays
 * @property string|null $materials
 * @property string|null $notes
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CampDay extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'camp_id',
        'date',
        'is_trip',
        'trip_name',
        'name_days',
        'birthdays',
        'materials',
        'notes',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_trip' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Camp, $this>
     */
    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class);
    }

    /**
     * @return HasMany<ProgramEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(ProgramEntry::class);
    }

    /**
     * @return HasMany<DayReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(DayReview::class);
    }

    /**
     * This day's deviations from the camp-wide daily skeleton.
     *
     * @return HasMany<TimeSlotOverride, $this>
     */
    public function slotOverrides(): HasMany
    {
        return $this->hasMany(TimeSlotOverride::class);
    }
}
