<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\QrScannerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\AttendanceController as StudentAttendanceController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\QrController as StudentQrController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $user = request()->user();

    if ($user->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('student.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'role:admin'])->group(function (): void {
    Route::get('/admin/dashboard', AdminDashboardController::class)->name('admin.dashboard');
    Route::get('/admin/attendance', [AdminAttendanceController::class, 'index'])->name('admin.attendance.index');
    Route::post('/admin/events/{event}/attendance/lock', [AdminAttendanceController::class, 'lock'])->name('admin.attendance.lock');
    Route::post('/admin/events/{event}/attendance/unlock', [AdminAttendanceController::class, 'unlock'])->name('admin.attendance.unlock');
    Route::get('/admin/qr-scanner', [QrScannerController::class, 'index'])->name('admin.qr-scanner.index');
    Route::post('/admin/qr-scanner', [QrScannerController::class, 'store'])->name('admin.qr-scanner.store');
    Route::resource('/admin/events', EventController::class)
        ->except(['show'])
        ->names('admin.events');
});

Route::middleware(['auth', 'verified', 'role:student'])->group(function (): void {
    Route::get('/student/dashboard', StudentDashboardController::class)->name('student.dashboard');
    Route::get('/student/attendance', [StudentAttendanceController::class, 'index'])->name('student.attendance.index');
    Route::post('/student/attendance', [StudentAttendanceController::class, 'store'])->name('student.attendance.store');
    Route::get('/student/qr', [StudentQrController::class, 'show'])->name('student.qr.show');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
