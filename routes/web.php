<?php

use App\Http\Controllers\ActivityCategoryController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityLibraryController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CampController;
use App\Http\Controllers\CampDayController;
use App\Http\Controllers\CampLeaderController;
use App\Http\Controllers\CampMemberController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\DayReviewController;
use App\Http\Controllers\EntryPointsController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\FeedbackQuestionController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupTypeController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LibraryVersionController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PlanVersionController;
use App\Http\Controllers\ProgramEntryController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\TimeSlotController;
use App\Http\Controllers\TimeSlotOverrideController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Legal pages are public on purpose: someone whose data a camp organiser
// entered must be able to read the privacy policy without an account.
Route::get('privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('terms', [LegalController::class, 'terms'])->name('legal.terms');

// Language switcher. A preference cookie, so it works for guests too.
Route::put('locale', [LocaleController::class, 'update'])->name('locale.update');

// Public share/invite landing page (works for guests and logged-in users).
Route::get('invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');
// Joining only needs authentication (not email verification), so a freshly
// registered user can accept immediately.
Route::post('invitations/{token}', [InvitationController::class, 'accept'])
    ->middleware('auth')->name('invitations.accept');

// Public read-only view of a single shared activity.
Route::get('a/{token}', [ActivityController::class, 'shared'])->name('activities.shared');

// Public activity-library share link.
Route::get('libraries/join/{token}', [ActivityLibraryController::class, 'joinShow'])->name('libraries.join.show');
Route::post('libraries/join/{token}', [ActivityLibraryController::class, 'joinAccept'])
    ->middleware('auth')->name('libraries.join.accept');

Route::middleware(['auth', 'verified'])->group(function () {
    // The camps list is the app's home; keep the name so existing links redirect.
    Route::redirect('dashboard', '/camps')->name('dashboard');

    // The "what's new" dialog: fetched on demand from the sidebar, and
    // acknowledged when it is closed.
    Route::get('changelog', [ChangelogController::class, 'show'])->name('changelog.show');
    Route::post('changelog/seen', [ChangelogController::class, 'dismiss'])->name('changelog.seen');

    // Administrator panel — only the account named by config('app.admin_email').
    Route::get('admin', [AdminController::class, 'index'])->middleware('can:admin')->name('admin.index');

    // Camps
    Route::get('camps', [CampController::class, 'index'])->name('camps.index');
    Route::get('camps/create', [CampController::class, 'create'])->name('camps.create');
    Route::post('camps', [CampController::class, 'store'])->name('camps.store');
    Route::get('camps/{camp}', [CampController::class, 'show'])->name('camps.show');
    Route::put('camps/{camp}', [CampController::class, 'update'])->name('camps.update');
    Route::delete('camps/{camp}', [CampController::class, 'destroy'])->name('camps.destroy');
    Route::post('camps/{camp}/duplicate', [CampController::class, 'duplicate'])->name('camps.duplicate');
    Route::post('camps/{camp}/fill-name-days', [CampController::class, 'fillNameDays'])->name('camps.fillNameDays');
    Route::post('camps/{camp}/lock', [CampController::class, 'lock'])->name('camps.lock');
    Route::delete('camps/{camp}/lock', [CampController::class, 'unlock'])->name('camps.unlock');
    Route::post('camps/{camp}/summary', [SummaryController::class, 'camp'])->name('camps.summary');
    Route::post('days/{day}/summary', [SummaryController::class, 'day'])->name('days.summary');

    // Camp-scoped pages reached from the sidebar
    Route::get('camps/{camp}/leaders', [CampLeaderController::class, 'index'])->name('leaders.index');
    Route::post('camps/{camp}/leaders', [CampLeaderController::class, 'store'])->name('leaders.store');
    // The parameter name drives the scoped lookup: {leader} -> Camp::leaders()
    Route::put('camps/{camp}/leaders/{leader}', [CampLeaderController::class, 'update'])
        ->scopeBindings()->name('leaders.update');
    Route::delete('camps/{camp}/leaders/{leader}', [CampLeaderController::class, 'destroy'])
        ->scopeBindings()->name('leaders.destroy');
    Route::get('camps/{camp}/settings', [CampController::class, 'settings'])->name('camps.settings');
    Route::get('camps/{camp}/blocks', [TimeSlotController::class, 'index'])->name('slots.index');
    Route::get('camps/{camp}/feedback', [FeedbackController::class, 'index'])->name('camps.feedback');
    Route::get('camps/{camp}/leaderboard', [LeaderboardController::class, 'index'])->name('camps.leaderboard');

    // The camp's own review questions, asked inside the day review
    Route::post('camps/{camp}/feedback-questions', [FeedbackQuestionController::class, 'store'])
        ->name('feedbackQuestions.store');
    Route::put('camps/{camp}/feedback-questions/{feedbackQuestion}', [FeedbackQuestionController::class, 'update'])
        ->scopeBindings()->name('feedbackQuestions.update');
    Route::delete('camps/{camp}/feedback-questions/{feedbackQuestion}', [FeedbackQuestionController::class, 'destroy'])
        ->scopeBindings()->name('feedbackQuestions.destroy');

    // Groups the camp is split into, and the camp's own list of group types
    Route::get('camps/{camp}/groups', [GroupController::class, 'index'])->name('groups.index');
    Route::post('camps/{camp}/groups', [GroupController::class, 'store'])->name('groups.store');
    Route::put('camps/{camp}/groups/{group}', [GroupController::class, 'update'])
        ->scopeBindings()->name('groups.update');
    Route::delete('camps/{camp}/groups/{group}', [GroupController::class, 'destroy'])
        ->scopeBindings()->name('groups.destroy');
    Route::post('camps/{camp}/group-types', [GroupTypeController::class, 'store'])->name('groupTypes.store');
    Route::put('camps/{camp}/group-types/{groupType}', [GroupTypeController::class, 'update'])
        ->scopeBindings()->name('groupTypes.update');
    Route::delete('camps/{camp}/group-types/{groupType}', [GroupTypeController::class, 'destroy'])
        ->scopeBindings()->name('groupTypes.destroy');

    // Camp days (derived from the date range — only their meta is editable)
    Route::put('days/{day}', [CampDayController::class, 'update'])->name('days.update');
    Route::post('days/{day}/review', [DayReviewController::class, 'store'])->name('days.review.store');

    // Time slots (columns)
    Route::post('camps/{camp}/slots', [TimeSlotController::class, 'store'])->name('slots.store');
    Route::put('slots/{slot}', [TimeSlotController::class, 'update'])->name('slots.update');
    Route::delete('slots/{slot}', [TimeSlotController::class, 'destroy'])->name('slots.destroy');

    // Per-day deviations from the daily skeleton (moved/hidden on one day only)
    Route::put('days/{day}/slots/{slot}/override', [TimeSlotOverrideController::class, 'upsert'])->name('slotOverrides.upsert');
    Route::delete('days/{day}/slots/{slot}/override', [TimeSlotOverrideController::class, 'destroy'])->name('slotOverrides.destroy');

    // Saved snapshots of the whole schedule
    Route::post('camps/{camp}/versions', [PlanVersionController::class, 'store'])->name('versions.store');
    // The parameter name drives the scoped lookup: {planVersion} -> Camp::planVersions()
    Route::post('camps/{camp}/versions/{planVersion}/restore', [PlanVersionController::class, 'restore'])
        ->scopeBindings()->name('versions.restore');
    Route::delete('camps/{camp}/versions/{planVersion}', [PlanVersionController::class, 'destroy'])
        ->scopeBindings()->name('versions.destroy');

    // Program entries (time-positioned activities)
    Route::post('program-entries', [ProgramEntryController::class, 'store'])->name('entries.store');
    Route::put('program-entries/bulk', [ProgramEntryController::class, 'bulkUpdate'])->name('entries.bulkUpdate');
    Route::post('program-entries/bulk-delete', [ProgramEntryController::class, 'bulkDestroy'])->name('entries.bulkDestroy');
    Route::put('program-entries/{entry}', [ProgramEntryController::class, 'update'])->name('entries.update');
    Route::put('program-entries/{entry}/status', [ProgramEntryController::class, 'setStatus'])->name('entries.setStatus');
    Route::delete('program-entries/{entry}', [ProgramEntryController::class, 'destroy'])->name('entries.destroy');
    // Recording results is not editing the plan, so this survives a frozen schedule.
    Route::put('program-entries/{entry}/points', [EntryPointsController::class, 'update'])->name('entries.points.update');

    // Members & invitations
    Route::post('camps/{camp}/members', [CampMemberController::class, 'store'])->name('members.store');
    Route::delete('camps/{camp}/members/{user}', [CampMemberController::class, 'destroy'])->name('members.destroy');
    Route::delete('camps/{camp}/invitations/{invitation}', [CampMemberController::class, 'destroyInvitation'])->name('invitations.destroy');
    Route::post('camps/{camp}/share-link', [CampMemberController::class, 'storeShareLink'])->name('camps.shareLink');

    // Activities (library-scoped)
    Route::get('activities', [ActivityController::class, 'index'])->name('activities.index');
    Route::post('activities', [ActivityController::class, 'store'])->name('activities.store');
    Route::post('activities/{activity}/duplicate', [ActivityController::class, 'duplicate'])->name('activities.duplicate');
    Route::put('activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');
    Route::delete('activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');

    // Activity libraries
    Route::post('libraries', [ActivityLibraryController::class, 'store'])->name('libraries.store');
    Route::put('libraries/{library}', [ActivityLibraryController::class, 'update'])->name('libraries.update');
    Route::delete('libraries/{library}', [ActivityLibraryController::class, 'destroy'])->name('libraries.destroy');
    Route::post('libraries/{library}/members', [ActivityLibraryController::class, 'storeMember'])->name('libraries.members.store');
    Route::delete('libraries/{library}/members/{user}', [ActivityLibraryController::class, 'destroyMember'])->name('libraries.members.destroy');
    Route::post('libraries/{library}/share-link', [ActivityLibraryController::class, 'storeShareLink'])->name('libraries.shareLink.store');
    Route::delete('libraries/{library}/share-link', [ActivityLibraryController::class, 'destroyShareLink'])->name('libraries.shareLink.destroy');
    Route::get('libraries/{library}/export', [ActivityLibraryController::class, 'export'])->name('libraries.export');
    Route::post('libraries/{library}/import', [ActivityLibraryController::class, 'import'])->name('libraries.import');

    // Saved snapshots of a library's activities
    Route::post('libraries/{library}/versions', [LibraryVersionController::class, 'store'])->name('libraries.versions.store');
    // The parameter name drives the scoped lookup: {libraryVersion} -> ActivityLibrary::libraryVersions()
    Route::post('libraries/{library}/versions/{libraryVersion}/restore', [LibraryVersionController::class, 'restore'])
        ->scopeBindings()->name('libraries.versions.restore');
    Route::delete('libraries/{library}/versions/{libraryVersion}', [LibraryVersionController::class, 'destroy'])
        ->scopeBindings()->name('libraries.versions.destroy');

    // Activity categories (user-defined tags, scoped to a library)
    Route::post('libraries/{library}/categories', [ActivityCategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/{category}', [ActivityCategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [ActivityCategoryController::class, 'destroy'])->name('categories.destroy');
});

require __DIR__.'/settings.php';
