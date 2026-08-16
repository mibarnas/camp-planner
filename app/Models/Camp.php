<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $owner_id
 * @property int|null $activity_library_id
 * @property string $name
 * @property string $icon
 * @property string $color
 * @property int $year
 * @property string|null $description
 * @property string|null $location
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property Carbon|null $schedule_locked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Pivot|null $pivot
 * @property-read ActivityLibrary|null $activityLibrary
 */
class Camp extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'owner_id',
        'activity_library_id',
        'name',
        'icon',
        'color',
        'year',
        'description',
        'location',
        'start_date',
        'end_date',
        'schedule_locked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'schedule_locked_at' => 'datetime',
        ];
    }

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
        return $this->belongsToMany(User::class, 'camp_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<CampInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(CampInvitation::class);
    }

    /**
     * @return HasMany<CampDay, $this>
     */
    public function days(): HasMany
    {
        return $this->hasMany(CampDay::class)->orderBy('position')->orderBy('date');
    }

    /**
     * @return HasMany<TimeSlot, $this>
     */
    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class)->orderBy('position')->orderBy('start_time');
    }

    /**
     * @return HasMany<PlanVersion, $this>
     */
    public function planVersions(): HasMany
    {
        return $this->hasMany(PlanVersion::class);
    }

    /**
     * @return BelongsTo<ActivityLibrary, $this>
     */
    public function activityLibrary(): BelongsTo
    {
        return $this->belongsTo(ActivityLibrary::class, 'activity_library_id');
    }

    /**
     * While the schedule is frozen nobody — the owner included — may move the
     * program around; day notes and reviews stay editable.
     */
    public function isScheduleLocked(): bool
    {
        return $this->schedule_locked_at !== null;
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->whereKey($user->getKey())->exists();
    }

    /**
     * Add a user to the camp and mirror the membership into the linked
     * activity library so they can use its activities right away.
     */
    public function addMember(User $user, string $role = 'leader'): void
    {
        if (! $this->hasMember($user)) {
            $this->members()->attach($user->id, ['role' => $role]);
        }

        $this->activityLibrary?->addMember($user);
    }
}
