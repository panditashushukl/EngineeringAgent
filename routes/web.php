<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AppController;
use App\Http\Controllers\Auth\EmailAuthController;
use App\Http\Controllers\IntegrationController;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', AppController::class)->middleware('auth')->name('dashboard');
Route::view('/login', 'auth.login')->middleware('guest')->name('login');
Route::post('/login', [EmailAuthController::class, 'login'])
    ->middleware('guest')
    ->name('login.store');
Route::post('/logout', [EmailAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
Route::post('/logout-all-devices', [EmailAuthController::class, 'logoutAllDevices'])
    ->middleware('auth')
    ->name('logout.all_devices');

/*
|--------------------------------------------------------------------------
| OAuth Routes (Connect & Callback)
|--------------------------------------------------------------------------
*/

Route::prefix('oauth')->group(function () {
    Route::get('/{provider}/connect', [IntegrationController::class, 'connect'])->name('oauth.connect');
    Route::get('/{provider}/callback', [IntegrationController::class, 'callback'])->name('oauth.callback');
});

/*
|--------------------------------------------------------------------------
| Settings and Workflow Report Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/api/settings', [\App\Http\Controllers\SettingsController::class, 'getSettings']);
    Route::post('/api/settings', [\App\Http\Controllers\SettingsController::class, 'saveSettings']);
    Route::post('/api/settings/recalculate', [\App\Http\Controllers\SettingsController::class, 'recalculateMetrics']);
    
    Route::get('/api/workflow-report', [\App\Http\Controllers\WorkflowReportController::class, 'latest']);
    Route::post('/api/workflow-report/generate', [\App\Http\Controllers\WorkflowReportController::class, 'generate']);

    Route::post('/api/integrations/{integration}/sync', [\App\Http\Controllers\IntegrationController::class, 'sync']);
    Route::post('/api/repositories/{repository}/sync', [\App\Http\Controllers\RepositoryController::class, 'sync']);
    Route::post('/api/developers/{developer}/generate-insights', [\App\Http\Controllers\InsightController::class, 'generate']);
    Route::post('/api/insights/generate-all', [\App\Http\Controllers\InsightController::class, 'generateAll']);

    // Developer Mode API Key endpoints
    Route::get('/api/developer/tokens', [\App\Http\Controllers\DeveloperModeController::class, 'getTokens']);
    Route::post('/api/developer/tokens', [\App\Http\Controllers\DeveloperModeController::class, 'generateToken']);
    Route::delete('/api/developer/tokens/{id}', [\App\Http\Controllers\DeveloperModeController::class, 'revokeToken']);
});
/*
|--------------------------------------------------------------------------
| SPA Fallback
|--------------------------------------------------------------------------
*/



Route::get('/{any}', AppController::class)->middleware('auth')->where('any', '.*');
