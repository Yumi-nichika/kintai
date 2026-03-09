<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;

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

//Route::get('/', [AttendanceController::class, 'index']);

Route::middleware(['auth'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance/start', [AttendanceController::class, 'start']);
    Route::post('/attendance/end', [AttendanceController::class, 'end']);
    Route::post('/attendance/break/start', [AttendanceController::class, 'breakStart']);
    Route::post('/attendance/break/end', [AttendanceController::class, 'breakEnd']);

    Route::get('/attendance/list', [AttendanceController::class, 'show']);
});