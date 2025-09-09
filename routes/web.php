<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ChildController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IcsImportController;
use App\Http\Controllers\KidsModeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Legacy Supabase Authentication routes (for transition period)
// These routes are prefixed to avoid conflicts with Breeze routes
Route::prefix('auth/supabase')->name('supabase.')->middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    // Email confirmation callback from Supabase
    Route::get('/confirm', [AuthController::class, 'confirmEmail'])->name('confirm');
});

// Locale routes (available to both guests and authenticated users)
// These MUST be web routes (not API prefix) to have session support
Route::post('/locale/update', [LocaleController::class, 'updateLocale'])->name('locale.update');
Route::get('/translations/{locale}', [LocaleController::class, 'getTranslations'])->name('locale.translations');
Route::get('/locales', [LocaleController::class, 'getAvailableLocales'])->name('locale.available');

// Legacy locale routes for backward compatibility
Route::post('/locale/session', [LocaleController::class, 'updateSessionLocale'])->name('locale.session');

// Test route for debugging database issues
Route::get('/debug/subject/{id}', function ($id) {
    try {
        \Log::info('Debug route: Testing database query', ['id' => $id]);

        // Test 1: Raw database query
        $raw = \DB::select('SELECT * FROM subjects WHERE id = ?', [$id]);
        \Log::info('Debug route: Raw query result', ['result' => $raw]);

        // Test 2: Query builder
        $builder = \DB::table('subjects')->where('id', $id)->first();
        \Log::info('Debug route: Query builder result', ['result' => $builder]);

        // Test 3: Direct parent::find (bypassing custom find)
        $parent = \App\Models\Subject::query()->find((int) $id);
        \Log::info('Debug route: Parent find result', ['result' => $parent]);

        return response()->json([
            'raw' => $raw,
            'builder' => $builder,
            'parent' => $parent,
        ]);
    } catch (\Exception $e) {
        \Log::error('Debug route error', ['exception' => $e->getMessage()]);

        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// Protected routes - using Laravel native auth middleware
Route::middleware('auth')->group(function () {
    // Legacy Supabase logout route (for transition period)
    Route::post('/auth/supabase/logout', [AuthController::class, 'logout'])->name('supabase.logout');

    // Legacy user locale route for backward compatibility
    Route::post('/locale/user', [LocaleController::class, 'updateUserLocale'])->name('locale.user');

    // Profile management routes (Laravel Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Onboarding routes
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/', [OnboardingController::class, 'index'])->name('index')->middleware('redirect-if-onboarding-completed');
        Route::post('/children', [OnboardingController::class, 'saveChildren'])->name('children');
        Route::post('/subjects', [OnboardingController::class, 'saveSubjects'])->name('subjects');
        Route::post('/complete', [OnboardingController::class, 'complete'])->name('complete');
        Route::post('/skip', [OnboardingController::class, 'skip'])->name('skip');
    });

    // Dashboard routes - Parent/Child Views (Milestone 5)
    // Main dashboard (redirect to parent view) - blocked in kids mode
    Route::get('/dashboard', [DashboardController::class, 'parentDashboard'])
        ->name('dashboard')
        ->middleware('not-in-kids-mode');

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        // Parent dashboard (explicit) - blocked in kids mode
        Route::get('/parent', [DashboardController::class, 'parentDashboard'])
            ->name('parent')
            ->middleware('not-in-kids-mode');

        // Child today view with access control
        Route::get('/child/{child_id}/today', [DashboardController::class, 'childToday'])
            ->name('child-today')
            ->middleware(\App\Http\Middleware\ChildAccess::class);

        // Parent one-click actions - blocked in kids mode
        Route::post('/skip-day', [DashboardController::class, 'skipDay'])
            ->name('skip-day')
            ->middleware('not-in-kids-mode');
        Route::post('/move-theme', [DashboardController::class, 'moveTheme'])
            ->name('move-theme')
            ->middleware('not-in-kids-mode');
        Route::post('/bulk-complete-today', [DashboardController::class, 'bulkCompleteToday'])
            ->name('bulk-complete-today')
            ->middleware('not-in-kids-mode');

        // Session management - allow completion and reordering in kids mode
        Route::post('/sessions/{sessionId}/complete', [DashboardController::class, 'completeSession'])->name('sessions.complete');
        Route::post('/child/{childId}/reorder-today', [DashboardController::class, 'reorderTodaySessions'])->name('reorder-today');

        // Advanced session management - blocked in kids mode
        Route::put('/sessions/move-in-week', [DashboardController::class, 'moveSessionInWeek'])
            ->name('move-session-in-week')
            ->middleware('not-in-kids-mode');

        // Independence level management - blocked in kids mode
        Route::put('/child/{childId}/independence-level', [DashboardController::class, 'updateIndependenceLevel'])
            ->name('independence-level')
            ->middleware('not-in-kids-mode');
    });

    // Children management routes - all blocked in kids mode
    Route::prefix('children')->name('children.')->middleware('not-in-kids-mode')->group(function () {
        Route::get('/', [ChildController::class, 'index'])->name('index');
        Route::get('/create', [ChildController::class, 'create'])->name('create');
        Route::post('/', [ChildController::class, 'store'])->name('store');
        Route::get('/{id}', [ChildController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [ChildController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ChildController::class, 'update'])->name('update');
        Route::delete('/{id}', [ChildController::class, 'destroy'])->name('destroy');
    });

    // Calendar/Time Block management routes - all blocked in kids mode
    Route::prefix('calendar')->name('calendar.')->middleware('not-in-kids-mode')->group(function () {
        Route::get('/', [CalendarController::class, 'index'])->name('index');
        Route::get('/create', [CalendarController::class, 'create'])->name('create');
        Route::post('/', [CalendarController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [CalendarController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CalendarController::class, 'update'])->name('update');
        Route::delete('/{id}', [CalendarController::class, 'destroy'])->name('destroy');

        // ICS Import routes (Milestone 6)
        Route::get('/import', [IcsImportController::class, 'index'])->name('import');
        Route::post('/import/preview', [IcsImportController::class, 'preview'])->name('import.preview');
        Route::post('/import/file', [IcsImportController::class, 'import'])->name('import.file');
        Route::post('/import/url', [IcsImportController::class, 'importUrl'])->name('import.url');
        Route::get('/import/help', [IcsImportController::class, 'help'])->name('import.help');
    });

    // Planning Board routes - all blocked in kids mode
    Route::prefix('planning')->name('planning.')->middleware('not-in-kids-mode')->group(function () {
        Route::get('/', [PlanningController::class, 'index'])->name('index');
        Route::get('/create-session', [PlanningController::class, 'createSession'])->name('create-session');
        Route::post('/sessions', [PlanningController::class, 'createSession'])->name('sessions.store');
        Route::put('/sessions/{id}/status', [PlanningController::class, 'updateSessionStatus'])->name('sessions.status');
        Route::get('/sessions/{id}/schedule', [PlanningController::class, 'scheduleSession'])->name('sessions.schedule');
        Route::put('/sessions/{id}/schedule', [PlanningController::class, 'scheduleSession'])->name('sessions.schedule.store');
        Route::put('/sessions/{id}/unschedule', [PlanningController::class, 'unscheduleSession'])->name('sessions.unschedule');
        Route::delete('/sessions/{id}', [PlanningController::class, 'deleteSession'])->name('sessions.destroy');

        // Milestone 3: Flexible Scheduling Routes
        Route::get('/skip-day-modal/{id}', [PlanningController::class, 'showSkipDayModal'])->name('skip-day-modal');
        Route::post('/sessions/{id}/skip-day', [PlanningController::class, 'skipSessionDay'])->name('sessions.skip-day');
        Route::put('/sessions/{id}/commitment-type', [PlanningController::class, 'updateSessionCommitmentType'])->name('sessions.commitment-type');
        Route::get('/sessions/{id}/scheduling-suggestions', [PlanningController::class, 'getSchedulingSuggestions'])->name('scheduling-suggestions');

        // Catch-up session routes
        Route::post('/redistribute-catchup', [PlanningController::class, 'redistributeCatchUp'])->name('redistribute-catchup');
        Route::put('/catch-up/{id}/priority', [PlanningController::class, 'updateCatchUpPriority'])->name('catch-up.priority');
        Route::delete('/catch-up/{id}', [PlanningController::class, 'deleteCatchUpSession'])->name('catch-up.delete');

        // Capacity analysis
        Route::get('/capacity-analysis', [PlanningController::class, 'getCapacityAnalysis'])->name('capacity-analysis');

        // Quality heuristics analysis (Milestone 6)
        Route::get('/quality-analysis', [PlanningController::class, 'getQualityAnalysis'])->name('quality-analysis');
    });

    // Review System routes (Milestone 4)
    Route::prefix('reviews')->name('reviews.')->group(function () {
        // Allow basic review functionality in kids mode (read-only for child)
        Route::get('/', [ReviewController::class, 'index'])->name('index');
        Route::get('/session/{childId}', [ReviewController::class, 'startSession'])->name('session');
        Route::post('/process/{reviewId}', [ReviewController::class, 'processResult'])->name('process');
        Route::get('/review/{reviewId}', [ReviewController::class, 'show'])->name('show');
        Route::post('/complete/{sessionId}', [ReviewController::class, 'completeSession'])->name('complete');

        // Review slots management - blocked in kids mode (parent-only)
        Route::middleware('not-in-kids-mode')->group(function () {
            Route::get('/slots/{childId}', [ReviewController::class, 'manageSlots'])->name('slots');
            Route::post('/slots', [ReviewController::class, 'storeSlot'])->name('slots.store');
            Route::put('/slots/{id}', [ReviewController::class, 'updateSlot'])->name('slots.update');
            Route::delete('/slots/{id}', [ReviewController::class, 'destroySlot'])->name('slots.destroy');
            Route::put('/slots/{id}/toggle', [ReviewController::class, 'toggleSlot'])->name('slots.toggle');
        });
    });

    // Subject management routes
    Route::prefix('subjects')->name('subjects.')->group(function () {
        // Allow viewing subjects in kids mode
        Route::get('/', [SubjectController::class, 'index'])->name('index');

        // Block creation and editing in kids mode (parent-only)
        // IMPORTANT: Define specific routes BEFORE wildcard {id} routes
        Route::middleware('not-in-kids-mode')->group(function () {
            Route::get('/create', [SubjectController::class, 'create'])->name('create');
            Route::post('/', [SubjectController::class, 'store'])->name('store');
            Route::get('/quick-start', [SubjectController::class, 'quickStartForm'])->name('quick-start.form');
            Route::post('/quick-start', [SubjectController::class, 'quickStartStore'])->name('quick-start.store');
            Route::get('/{id}/edit', [SubjectController::class, 'edit'])->name('edit');
            Route::put('/{id}', [SubjectController::class, 'update'])->name('update');
            Route::delete('/{id}', [SubjectController::class, 'destroy'])->name('destroy');
        });

        // Wildcard route MUST come AFTER specific routes
        Route::get('/{id}', [SubjectController::class, 'show'])->name('show');
    });

    // Unit management routes (nested under subjects)
    Route::prefix('subjects/{subjectId}/units')->name('units.')->group(function () {
        // Allow viewing units in kids mode
        Route::get('/', [UnitController::class, 'index'])->name('index');

        // Block creation and editing in kids mode (parent-only)
        // IMPORTANT: Define specific routes BEFORE wildcard {id} routes
        Route::middleware('not-in-kids-mode')->group(function () {
            Route::get('/create', [UnitController::class, 'create'])->name('create');
            Route::post('/', [UnitController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [UnitController::class, 'edit'])->name('edit');
            Route::put('/{id}', [UnitController::class, 'update'])->name('update');
            Route::delete('/{id}', [UnitController::class, 'destroy'])->name('destroy');
        });

        // Wildcard route MUST come AFTER specific routes
        Route::get('/{id}', [UnitController::class, 'show'])->name('show');
    });

    // Topic management routes (nested under subjects/units)
    Route::prefix('subjects/{subjectId}/units/{unitId}/topics')->name('topics.')->group(function () {
        // Allow viewing topics in kids mode
        Route::get('/', [TopicController::class, 'index'])->name('index');

        // Block creation and editing in kids mode (parent-only)
        // IMPORTANT: Define specific routes BEFORE wildcard {id} routes
        Route::middleware('not-in-kids-mode')->group(function () {
            Route::get('/create', [TopicController::class, 'create'])->name('create');
            Route::post('/', [TopicController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [TopicController::class, 'edit'])->name('edit');
            Route::put('/{id}', [TopicController::class, 'update'])->name('update');
            Route::delete('/{id}', [TopicController::class, 'destroy'])->name('destroy');
        });

        // Wildcard route MUST come AFTER specific routes
        Route::get('/{id}', [TopicController::class, 'show'])->name('show');
    });

    // Task routes (keeping original task functionality)
    Route::prefix('tasks')->name('tasks.')->group(function () {
        // Allow viewing and toggling tasks in kids mode
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::post('/{id}/toggle', [TaskController::class, 'toggle'])->name('toggle');

        // Block creation and editing in kids mode (parent-only)
        Route::middleware('not-in-kids-mode')->group(function () {
            Route::get('/create', [TaskController::class, 'create'])->name('create');
            Route::post('/', [TaskController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [TaskController::class, 'edit'])->name('edit');
            Route::put('/{id}', [TaskController::class, 'update'])->name('update');
            Route::delete('/{id}', [TaskController::class, 'destroy'])->name('destroy');
        });
    });

    // Kids Mode management routes
    Route::prefix('kids-mode')->name('kids-mode.')->group(function () {
        // Enter kids mode for specific child - blocked in kids mode (prevents re-entry)
        Route::post('/enter/{child_id}', [KidsModeController::class, 'enterKidsMode'])
            ->name('enter')
            ->middleware('not-in-kids-mode');

        // Exit kids mode - PIN validation (always accessible)
        Route::get('/exit', [KidsModeController::class, 'showExitScreen'])->name('exit');
        Route::post('/exit', [KidsModeController::class, 'validateExitPin'])->name('exit.validate');
        Route::get('/exit-test', function () {
            return view('kids-mode.exit-test');
        })->name('exit-test');

        // PIN management for parents - blocked in kids mode
        Route::middleware('not-in-kids-mode')->group(function () {
            Route::get('/settings/pin', [KidsModeController::class, 'showPinSettings'])->name('settings');
            Route::post('/settings/pin', [KidsModeController::class, 'updatePin'])->name('pin.update');
            Route::post('/settings/pin/reset', [KidsModeController::class, 'resetPin'])->name('pin.reset');
        });
    });
});

// Include Laravel Breeze auth routes
require __DIR__.'/auth.php';
