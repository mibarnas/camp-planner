<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $camp_day_id
 * @property int|null $activity_id
 * @property string $kind one of detailed|simple
 * @property string $start_time
 * @property int $duration
 * @property string|null $title
 * @property string|null $description
 * @property string|null $responsible
 * @property string|null $materials
 * @property string|null $notes
 * @property string $status one of todo|none|done
 * @property string $points_mode one of none|raw|placement
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
        'kind',
        'start_time',
        'duration',
        'title',
        'description',
        'responsible',
        'materials',
        'notes',
        'status',
        'points_mode',
    ];

    /** Progress states an entry can be in. */
    public const STATUSES = ['todo', 'none', 'done'];

    /** A full programme activity, or a plain block like a meal or a transfer. */
    public const KINDS = ['detailed', 'simple'];

    /** Not a scoring activity. */
    public const POINTS_NONE = 'none';

    /** The numbers leaders record are the points. */
    public const POINTS_RAW = 'raw';

    /** The numbers only rank the groups; points follow the ranking. */
    public const POINTS_PLACEMENT = 'placement';

    /** How an activity turns group results into points. */
    public const POINTS_MODES = [self::POINTS_NONE, self::POINTS_RAW, self::POINTS_PLACEMENT];

    /** Match the column defaults so a just-created entry isn't serialized as null. */
    protected $attributes = [
        'status' => 'none',
        'kind' => 'detailed',
        'points_mode' => self::POINTS_NONE,
    ];

    /** Whether this activity scores the camp's groups at all. */
    public function isForPoints(): bool
    {
        return $this->points_mode !== self::POINTS_NONE;
    }

    /**
     * Simple blocks are scaffolding around the programme, not programme itself:
     * they carry no scenario or materials and aren't worth rating.
     */
    public function isSimple(): bool
    {
        return $this->kind === 'simple';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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

    /**
     * @return HasMany<ActivityRating, $this>
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(ActivityRating::class);
    }

    /**
     * @return HasMany<EntryGroupPoint, $this>
     */
    public function groupPoints(): HasMany
    {
        return $this->hasMany(EntryGroupPoint::class);
    }
}
