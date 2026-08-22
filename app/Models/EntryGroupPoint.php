<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One group's result in one scoring activity.
 *
 * @property int $id
 * @property int $program_entry_id
 * @property int $camp_group_id
 * @property float $value
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class EntryGroupPoint extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'program_entry_id',
        'camp_group_id',
        'value',
        'recorded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'float',
        ];
    }

    /**
     * @return BelongsTo<ProgramEntry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(ProgramEntry::class, 'program_entry_id');
    }

    /**
     * @return BelongsTo<CampGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(CampGroup::class, 'camp_group_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
