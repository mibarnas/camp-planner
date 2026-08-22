<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A camp's own review question. Scope 'day' is asked in every day's review,
 * 'camp' once as part of the last day's.
 *
 * @property int $id
 * @property int $camp_id
 * @property string $scope
 * @property string $text
 * @property int $position
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class FeedbackQuestion extends Model
{
    public const SCOPES = ['day', 'camp'];

    /** @var list<string> */
    protected $fillable = [
        'camp_id',
        'scope',
        'text',
        'position',
        'archived_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
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
     * @return HasMany<FeedbackAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(FeedbackAnswer::class);
    }

    /**
     * @param  Builder<FeedbackQuestion>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }
}
