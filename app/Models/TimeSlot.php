<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $camp_id
 * @property string $name
 * @property string $start_time
 * @property string $end_time
 * @property string $kind
 * @property string|null $color
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TimeSlot extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'camp_id',
        'name',
        'start_time',
        'end_time',
        'kind',
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
}
