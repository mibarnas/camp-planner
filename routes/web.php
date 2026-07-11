<?php

use App\Http\Controllers\ActivityCategoryController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityLibraryController;
use App\Http\Controllers\CampController;
use App\Http\Controllers\CampDayController;
use App\Http\Controllers\CampMemberController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ProgramEntryController;
use App\Http\Controllers\TimeSlotController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Public share/invite landing page (works for guests and logged-in users).
Route::get('invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');
// Joining only needs authentication (not email verification), so a freshly
// registered user can accept immediately.
Route::post('invitations/{token}', [InvitationController::class, 'accept'])
    ->middleware('auth')->name('invitations.accept');

Route::middleware(['auth', 'verified'])->group(function () {
    // The camps list is the app's home; keep the name so existing links redirect.
    Route::redirect('dashboard', '/camps')->name('dashboard');

    // Camps
    Route::get('camps', [CampController::class, 'index'])->name('camps.index');
    Route::post('camps', [CampController::class, 'store'])->name('camps.store');
    Route::get('camps/{camp}', [CampController::class, 'show'])->name('camps.show');
    Route::put('camps/{camp}', [CampController::class, 'update'])->name('camps.update');
    Route::delete('camps/{camp}', [CampController::class, 'destroy'])->name('camps.destroy');
    Route::post('camps/{camp}/duplicate', [CampController::class, 'duplicate'])->name('camps.duplicate');
    Route::post('camps/{camp}/fill-name-days', [CampController::class, 'fillNameDays'])->name('camps.fillNameDays');

    // Camp days
    Route::post('camps/{camp}/days', [CampDayController::class, 'store'])->name('days.store');
    Route::put('days/{day}', [CampDayController::class, 'update'])->name('days.update');
    Route::delete('days/{day}', [CampDayController::class, 'destroy'])->name('days.destroy');

    // Time slots (columns)
    Route::post('camps/{camp}/slots', [TimeSlotController::class, 'store'])->name('slots.store');
    Route::put('slots/{slot}', [TimeSlotController::class, 'update'])->name('slots.update');
    Route::delete('slots/{slot}', [TimeSlotController::class, 'destroy'])->name('slots.destroy');

    // Program entries (time-positioned activities)
    Route::post('program-entries', [ProgramEntryController::class, 'store'])->name('entries.store');
    Route::put('program-entries/bulk', [ProgramEntryController::class, 'bulkUpdate'])->name('entries.bulkUpdate');
    Route::post('program-entries/bulk-delete', [ProgramEntryController::class, 'bulkDestroy'])->name('entries.bulkDestroy');
    Route::put('program-entries/{entry}', [ProgramEntryController::class, 'update'])->name('entries.update');
    Route::put('program-entries/{entry}/toggle', [ProgramEntryController::class, 'toggle'])->name('entries.toggle');
    Route::delete('program-entries/{entry}', [ProgramEntryController::class, 'destroy'])->name('entries.destroy');

    // Members & invitations
    Route::post('camps/{camp}/members', [CampMemberController::class, 'store'])->name('members.store');
    Route::delete('camps/{camp}/members/{user}', [CampMemberController::class, 'destroy'])->name('members.destroy');
    Route::delete('camps/{camp}/invitations/{invitation}', [CampMemberController::class, 'destroyInvitation'])->name('invitations.destroy');
    Route::post('camps/{camp}/share-link', [CampMemberController::class, 'storeShareLink'])->name('camps.shareLink');

    // Activities (library-scoped)
    Route::get('activities', [ActivityController::class, 'index'])->name('activities.index');
    Route::post('activities', [ActivityController::class, 'store'])->name('activities.store');
    Route::put('activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');
    Route::delete('activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');

    // Activity libraries
    Route::post('libraries', [ActivityLibraryController::class, 'store'])->name('libraries.store');
    Route::put('libraries/{library}', [ActivityLibraryController::class, 'update'])->name('libraries.update');
    Route::delete('libraries/{library}', [ActivityLibraryController::class, 'destroy'])->name('libraries.destroy');
    Route::post('libraries/{library}/members', [ActivityLibraryController::class, 'storeMember'])->name('libraries.members.store');
    Route::delete('libraries/{library}/members/{user}', [ActivityLibraryController::class, 'destroyMember'])->name('libraries.members.destroy');

    // Activity categories (user-defined tags, scoped to a library)
    Route::post('libraries/{library}/categories', [ActivityCategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/{category}', [ActivityCategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [ActivityCategoryController::class, 'destroy'])->name('categories.destroy');
});

require __DIR__.'/settings.php';
