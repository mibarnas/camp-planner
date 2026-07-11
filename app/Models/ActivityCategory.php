<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A user-defined activity tag, scoped to one activity library.
 *
 * @property int $id
 * @property int $activity_library_id
 * @property string $name
 * @property string|null $color
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ActivityLibrary $library
 */
class ActivityCategory extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'activity_library_id',
        'name',
        'color',
        'position',
    ];

    /**
     * @return BelongsTo<ActivityLibrary, $this>
     */
    public function library(): BelongsTo
    {
        return $this->belongsTo(ActivityLibrary::class, 'activity_library_id');
    }

    /**
     * @return HasMany<Activity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
}
