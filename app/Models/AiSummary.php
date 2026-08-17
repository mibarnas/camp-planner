<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $camp_id
 * @property int|null $camp_day_id null for a whole-camp summary
 * @property int|null $user_id
 * @property string $summary
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AiSummary extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'camp_id',
        'camp_day_id',
        'user_id',
        'summary',
    ];

    /**
     * @return BelongsTo<Camp, $this>
     */
    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class);
    }

    /**
     * @return BelongsTo<CampDay, $this>
     */
    public function day(): BelongsTo
    {
        return $this->belongsTo(CampDay::class, 'camp_day_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
