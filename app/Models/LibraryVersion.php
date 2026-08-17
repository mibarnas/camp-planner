<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $activity_library_id
 * @property int|null $user_id
 * @property string $name
 * @property array<string, mixed> $payload
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class LibraryVersion extends Model
{
    /** Same envelope as the library's JSON export, so the two are interchangeable. */
    public const FORMAT = 'taborplanner.library';

    public const VERSION = 1;

    /** @var list<string> */
    protected $fillable = [
        'activity_library_id',
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
     * @return BelongsTo<ActivityLibrary, $this>
     */
    public function library(): BelongsTo
    {
        return $this->belongsTo(ActivityLibrary::class, 'activity_library_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
