<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $activity_library_id
 * @property int|null $activity_category_id
 * @property int|null $created_by
 * @property string $name
 * @property string|null $description
 * @property int $default_duration
 * @property string|null $color
 * @property string|null $materials
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ActivityLibrary|null $library
 * @property-read ActivityCategory|null $category
 */
class Activity extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'activity_library_id',
        'activity_category_id',
        'created_by',
        'name',
        'description',
        'default_duration',
        'color',
        'materials',
    ];

    /**
     * @return BelongsTo<ActivityLibrary, $this>
     */
    public function library(): BelongsTo
    {
        return $this->belongsTo(ActivityLibrary::class, 'activity_library_id');
    }

    /**
     * @return BelongsTo<ActivityCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ActivityCategory::class, 'activity_category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Program cells across all camps that reference this activity.
     *
     * @return HasMany<ProgramEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(ProgramEntry::class);
    }
}
