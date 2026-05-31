<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', [App\Http\Controllers\HealthController::class, 'health']);
Route::get('/metrics', [App\Http\Controllers\HealthController::class, 'metrics']);
Route::get('/status', [App\Http\Controllers\StatusController::class, 'index'])->name('status');

// Projess API - Status endpoint (public, not behind admin middleware)
Route::get('projess/api/ticket-status/{ticketNumber}', 'App\Admin\Controllers\AiControllers\AdvanceUploadController@checkStatus')->name('api.ticket.status');

// Review status polling (used by validate-ground-truth after submit; must match frontend /projess/api/review/status/{ticketNumber})
Route::get('projess/api/review/status/{ticketNumber}', 'App\Admin\Controllers\AiControllers\ReviewSubmissionController@getStatus')->name('api.review.status.projess');
