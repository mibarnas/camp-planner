<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * One group within a camp. Competing groups collect points from activities
 * marked for scoring and appear on the leaderboard.
 *
 * @property int $id
 * @property int $camp_id
 * @property int|null $group_type_id
 * @property string $name
 * @property bool $competes
 * @property string|null $color
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CampGroup extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'camp_id',
        'group_type_id',
        'name',
        'competes',
        'color',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'competes' => 'boolean',
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
     * @return BelongsTo<GroupType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(GroupType::class, 'group_type_id');
    }

    /**
     * @return BelongsToMany<CampLeader, $this>
     */
    public function leaders(): BelongsToMany
    {
        return $this->belongsToMany(CampLeader::class, 'camp_group_leader')->withTimestamps();
    }
}
