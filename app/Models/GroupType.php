<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A camp-defined kind of group — "Detské skupiny", "Programoví vedúci",
 * "Fotografi". Every camp keeps its own list.
 *
 * @property int $id
 * @property int $camp_id
 * @property string $name
 * @property string|null $color
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class GroupType extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'camp_id',
        'name',
        'color',
        'position',
    ];

    /**
     * @return BelongsTo<Camp, $this>
     */
    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class);
    }

    /**
     * @return HasMany<CampGroup, $this>
     */
    public function groups(): HasMany
    {
        return $this->hasMany(CampGroup::class);
    }
}
