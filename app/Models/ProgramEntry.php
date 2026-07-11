<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $camp_day_id
 * @property int|null $activity_id
 * @property string $start_time
 * @property int $duration
 * @property string|null $title
 * @property string|null $description
 * @property string|null $responsible
 * @property string|null $materials
 * @property string|null $notes
 * @property bool $is_done
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Activity|null $activity
 */
class ProgramEntry extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'camp_day_id',
        'activity_id',
        'start_time',
        'duration',
        'title',
        'description',
        'responsible',
        'materials',
        'notes',
        'is_done',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'duration' => 'integer',
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
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
