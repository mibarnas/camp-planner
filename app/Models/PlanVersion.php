<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $camp_id
 * @property int|null $user_id
 * @property string $name
 * @property array<string, mixed> $payload
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PlanVersion extends Model
{
    /** The snapshot payload format this app reads and writes. */
    public const FORMAT = 'taborplanner.plan';

    public const VERSION = 1;

    /** @var list<string> */
    protected $fillable = [
        'camp_id',
        'user_id',
        'name',
        'payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
