<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\FeatureRegistryController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $analyses = \App\Models\Analysis::visibleTo(auth()->user())
        ->with('user')
        ->orderByDesc('created_at')
        ->get();

    $completed = $analyses->where('status', 'completed')->filter(fn($a) => is_array($a->results));

    return view('dashboard', [
        'recent' => $analyses->take(6),
        'stats' => [
            'analyses' => $analyses->count(),
            'completed' => $completed->count(),
            'high' => $completed->sum(fn($a) => $a->results['total_high'] ?? 0),
            'medium' => $completed->sum(fn($a) => $a->results['total_medium'] ?? 0),
            'low' => $completed->sum(fn($a) => $a->results['total_low'] ?? 0),
            'features' => \App\Models\FeatureRegistry::visibleTo(auth()->user())->count(),
        ],
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/retention', [ProfileController::class, 'updateRetention'])->name('profile.retention');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Write access: QA engineers and admins run analyses and maintain the registry.
// Registered first so that /analysis/create is matched before /analysis/{id}.
Route::middleware(['auth', 'role:qa_engineer,admin'])->group(function () {
    Route::get('/analysis/create', [AnalysisController::class, 'create'])->name('analysis.create');
    Route::post('/analysis', [AnalysisController::class, 'store'])->name('analysis.store');
    Route::resource('feature-registry', FeatureRegistryController::class)->except(['index', 'show']);
});

// Read access: all three roles. Developers are read-only (SRS FR2), so they
// reach reports, history and the registry, but none of the routes above.
Route::middleware(['auth', 'role:qa_engineer,developer,admin'])->group(function () {
    Route::get('/analysis', [AnalysisController::class, 'index'])->name('analysis.index');
    Route::get('/analysis/{analysis}', [AnalysisController::class, 'show'])->whereNumber('analysis')->name('analysis.show');
    Route::get('/analysis/{analysis}/pdf', [AnalysisController::class, 'pdf'])->whereNumber('analysis')->name('analysis.pdf');
    Route::get('/feature-registry', [FeatureRegistryController::class, 'index'])->name('feature-registry.index');
});

// SRS FR14: account and role management, admins only.
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->except(['show', 'destroy']);
    Route::post('/users/{user}/deactivate', [\App\Http\Controllers\Admin\UserController::class, 'deactivate'])->name('users.deactivate');
    Route::post('/users/{user}/reactivate', [\App\Http\Controllers\Admin\UserController::class, 'reactivate'])->name('users.reactivate');
});

require __DIR__ . '/auth.php';
