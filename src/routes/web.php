<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\StampCorrectionRequestController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

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

Route::redirect('/', '/login');

//メール認証誘導画面
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware(['auth', 'redirect.admin'])->name('verification.notice');

//メール認証クリック
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/attendance');
})->middleware(['auth', 'signed', 'redirect.admin'])->name('verification.verify');

//認証メール再送
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('resent', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1', 'redirect.admin'])->name('verification.send');



Route::middleware(['auth', 'verified', 'redirect.admin'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance/start', [AttendanceController::class, 'start']);
    Route::post('/attendance/end', [AttendanceController::class, 'end']);
    Route::post('/attendance/break/start', [AttendanceController::class, 'breakStart']);
    Route::post('/attendance/break/end', [AttendanceController::class, 'breakEnd']);

    Route::get('/attendance/list', [AttendanceController::class, 'showAttendanceList']);

    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'showDetail']);
    Route::post('/attendance/detail/{id}', [AttendanceController::class, 'store']);

    Route::get('/attendance/create', [AttendanceController::class, 'showCreate']);
    Route::post('/attendance/create', [AttendanceController::class, 'create']);
});



Route::prefix('admin')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin']);
});

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/attendance/list', [AdminAttendanceController::class, 'showAttendanceList']);

    Route::get('/attendance/create/{user_id}', [AdminAttendanceController::class, 'showCreate']);
    Route::post('/attendance/create/{user_id}', [AdminAttendanceController::class, 'create']);

    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'showDetail']);
    Route::post('/attendance/{id}', [AdminAttendanceController::class, 'update']);

    Route::get('/staff/list', [AdminAttendanceController::class, 'showStaffList']);
    Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'showStaffDetail']);
    Route::get('/attendance/staff/export/{id}', [AdminAttendanceController::class, 'export']);
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminAttendanceController::class, 'showApprove']);
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminAttendanceController::class, 'approve']);
});

Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'index'])
    ->middleware(['auth', 'check.admin']);
