<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $owner_id
 * @property string $name
 * @property string|null $share_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ActivityLibrary extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'owner_id',
        'name',
        'share_token',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'activity_library_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Activity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->orderBy('name');
    }

    /**
     * @return HasMany<ActivityCategory, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(ActivityCategory::class)->orderBy('position')->orderBy('name');
    }

    /**
     * Give a fresh library a sensible starter set of categories (tags).
     */
    public function seedDefaultCategories(): void
    {
        $defaults = [
            ['Duchovné', 'sky'],
            ['Scénka', 'violet'],
            ['Hra', 'emerald'],
            ['Šport', 'lime'],
            ['Tvorenie', 'orange'],
            ['Stanoviská', 'teal'],
            ['Jedlo / oddych', 'amber'],
            ['Výlet', 'fuchsia'],
            ['Iné', 'slate'],
        ];

        foreach ($defaults as $i => [$name, $color]) {
            $this->categories()->create(['name' => $name, 'color' => $color, 'position' => $i]);
        }
    }

    /**
     * @return HasMany<Camp, $this>
     */
    public function camps(): HasMany
    {
        return $this->hasMany(Camp::class);
    }

    /**
     * @return HasMany<LibraryVersion, $this>
     */
    public function libraryVersions(): HasMany
    {
        return $this->hasMany(LibraryVersion::class);
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->whereKey($user->getKey())->exists();
    }

    /**
     * Idempotently add a user to the library.
     */
    public function addMember(User $user, string $role = 'member'): void
    {
        if (! $this->hasMember($user)) {
            $this->members()->attach($user->id, ['role' => $role]);
        }
    }
}
