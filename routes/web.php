<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\FeatureRegistryController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $analyses = \App\Models\Analysis::where('user_id', auth()->id())
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
            'features' => \App\Models\FeatureRegistry::where('user_id', auth()->id())->count(),
        ],
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/test-github', function () {
    $service = new \App\Services\GitHubService();
    $diff = $service->fetchDiff('https://github.com/spatie/laravel-permission/pull/2000');
    return response($diff)->header('Content-Type', 'text/plain');
});

Route::middleware(['auth', 'role:qa_engineer,admin'])->group(function () {
    Route::get('/analysis/create', [AnalysisController::class, 'create'])->name('analysis.create');
    Route::post('/analysis', [AnalysisController::class, 'store'])->name('analysis.store');
    Route::get('/analysis/{analysis}', [AnalysisController::class, 'show'])->name('analysis.show');
    Route::get('/analysis', [AnalysisController::class, 'index'])->name('analysis.index');
});

Route::middleware(['auth', 'role:qa_engineer,admin'])->group(function () {
    Route::resource('feature-registry', FeatureRegistryController::class);
});

Route::get('/test-openai', function () {
    $service = new \App\Services\LLMService();
    $result = $service->analyseWithZeroShot(
        "Changed files: AuthController.php\nModified functions: login, logout",
        [["feature" => "User Auth", "code_areas" => ["AuthController"], "endpoints" => ["/api/login"]]]
    );
    return response()->json($result);
});

require __DIR__ . '/auth.php';
