<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\RepositoryController;
use App\Http\Controllers\DeveloperController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InsightController;

/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
*/

Route::get('/health', function () {

    return response()->json([
        'status' => 'ok',
        'service' => 'engineering-agent',
        'timestamp' => now(),
    ]);
});

Route::middleware('auth:sanctum')
    ->prefix('engineering-agent')
    ->name('engineering-agent.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Queue Management
        |--------------------------------------------------------------------------
        */
        Route::prefix('queue')->group(function () {
            Route::get('/status', function () {
                $pendingJobs = \DB::table('jobs')->count();
                $failedJobs = \DB::table('failed_jobs')->count();
                return response()->json([
                    'success' => true,
                    'pending_jobs' => $pendingJobs,
                    'failed_jobs' => $failedJobs,
                    'connection' => config('queue.default'),
                ]);
            });

            Route::post('/work', function () {
                \App\Services\Queue\QueueHelper::runWorkerInBackground();
                return response()->json([
                    'success' => true,
                    'message' => 'Background queue worker started successfully.'
                ]);
            });

            Route::post('/clear', function () {
                \DB::table('jobs')->truncate();
                return response()->json([
                    'success' => true,
                    'message' => 'Queue cleared successfully.'
                ]);
            });
        });

        /*
        |--------------------------------------------------------------------------
        | Integrations
        |--------------------------------------------------------------------------
        */

        Route::prefix('integrations')->group(function () {

            Route::get('/', [IntegrationController::class, 'index']);
            Route::post('/', [IntegrationController::class, 'store']);


            Route::get('/{integration}', [
                IntegrationController::class,
                'show'
            ]);

            Route::put('/{integration}', [
                IntegrationController::class,
                'update'
            ]);

            Route::delete('/{integration}', [
                IntegrationController::class,
                'destroy'
            ]);

            Route::post('/{integration}/sync', [
                IntegrationController::class,
                'sync'
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Repositories
        |--------------------------------------------------------------------------
        */

        Route::prefix('repositories')->group(function () {

            Route::get('/', [
                RepositoryController::class,
                'index'
            ]);

            Route::get('/{repository}', [
                RepositoryController::class,
                'show'
            ]);

            Route::post('/{repository}/sync', [
                RepositoryController::class,
                'sync'
            ]);

            Route::post('/{repository}/sync-commits', [
                RepositoryController::class,
                'syncCommits'
            ]);

            Route::post('/{repository}/sync-pull-requests', [
                RepositoryController::class,
                'syncPullRequests'
            ]);

            Route::post('/{repository}/sync-reviews', [
                RepositoryController::class,
                'syncReviews'
            ]);

            Route::post('/{repository}/sync-tasks', [
                RepositoryController::class,
                'syncTasks'
            ]);

            Route::post('/{repository}/sync-deployments', [
                RepositoryController::class,
                'syncDeployments'
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Developers
        |--------------------------------------------------------------------------
        */

        Route::prefix('developers')->group(function () {

            Route::get('/', [
                DeveloperController::class,
                'index'
            ]);

            Route::get('/{developer}', [
                DeveloperController::class,
                'show'
            ]);

            Route::get('/{developer}/metrics', [
                DeveloperController::class,
                'metrics'
            ]);

            Route::post('/{developer}/calculate', [
                DeveloperController::class,
                'calculate'
            ]);

            Route::get('/{developer}/repositories', [
                DeveloperController::class,
                'repositories'
            ]);

            Route::get('/{developer}/commits', [
                DeveloperController::class,
                'commits'
            ]);

            Route::get('/{developer}/pull-requests', [
                DeveloperController::class,
                'pullRequests'
            ]);

            Route::get('/{developer}/reviews', [
                DeveloperController::class,
                'reviews'
            ]);

            Route::get('/{developer}/tasks', [
                DeveloperController::class,
                'tasks'
            ]);

            Route::get('/{developer}/deployments', [
                DeveloperController::class,
                'deployments'
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::prefix('dashboard')->group(function () {

            Route::get('/overview', [
                DashboardController::class,
                'overview'
            ]);

            Route::get('/leaderboard', [
                DashboardController::class,
                'leaderboard'
            ]);

            Route::get('/repositories', [
                DashboardController::class,
                'repositories'
            ]);

            Route::get('/developers', [
                DashboardController::class,
                'developers'
            ]);

            Route::get('/metrics', [
                DashboardController::class,
                'metrics'
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Metrics
        |--------------------------------------------------------------------------
        */

        Route::prefix('metrics')->group(function () {

            Route::post('/calculate', [
                DeveloperController::class,
                'calculateAll'
            ]);

            Route::get('/leaderboard', [
                DashboardController::class,
                'leaderboard'
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | AI Insights
        |--------------------------------------------------------------------------
        */

        Route::prefix('insights')->group(function () {

            Route::post('/{developer}/generate', [
                InsightController::class,
                'generate'
            ]);

            Route::post('/{developer}/regenerate', [
                InsightController::class,
                'regenerate'
            ]);

            Route::get('/{developer}', [
                InsightController::class,
                'show'
            ]);
        });
    });

/*
|--------------------------------------------------------------------------
| Authenticated User
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get(
    '/user',
    function ($request) {
        return $request->user();
    }
);
