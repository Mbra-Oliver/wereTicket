<?php

use App\Http\Controllers\ScannerApi\AdminScannerController;
use App\Http\Controllers\ScannerApi\BasicController;
use App\Http\Controllers\ScannerApi\OrganizerScannerController;
use Illuminate\Support\Facades\Route;

Route::prefix('/scanner')->group(function () {
  Route::get('/get-basic', [BasicController::class, 'getBasic'])->name('api.getBasic');
  Route::get('/get-lang/{code}', [BasicController::class, 'getLang'])->name('api.getLang');
  
  Route::prefix('/organizer')->group(function () {
    Route::post('/login/submit', [OrganizerScannerController::class, 'loginSubmit'])->name('api.organizer.login_submit');
    Route::get('/authentication-fail', [OrganizerScannerController::class, 'authentication_fail'])->name('api.organizer.authentication.fail');
    Route::middleware('auth:organizer_sanctum')->group(function () {
      Route::get('/events', [OrganizerScannerController::class, 'events'])->name('api.organizer.events');
      Route::post('/ticket/scanned-status-change', [OrganizerScannerController::class, 'ticketScanStatusChanged'])->name('api.organizer.scanned_status_change');
      Route::post('/check-qrcode', [OrganizerScannerController::class, 'check_qrcode'])->name('api.organizer.check-qrcode');
      Route::post('/logout', [OrganizerScannerController::class, 'logoutSubmit'])->name('api.organizer.logout');
    });
  });
  Route::prefix('/admin')->group(function () {
    Route::post('/login/submit', [AdminScannerController::class, 'loginSubmit'])->name('api.admin.login_submit');
    Route::get('/authentication-fail', [AdminScannerController::class, 'authentication_fail'])->name('api.admin.authentication.fail');
    Route::group(['middleware' => 'auth:admin_sanctum'], function ($e) {
      Route::get('/events', [AdminScannerController::class, 'events'])->name('api.admin.events');
      Route::post('/ticket/scanned-status-change', [AdminScannerController::class, 'ticketScanStatusChanged'])->name('api.admin.scanned_status_change');
      Route::post('/check-qrcode', [AdminScannerController::class, 'check_qrcode'])->name('api.admin.check-qrcode');
      Route::post('/logout', [AdminScannerController::class, 'logoutSubmit'])->name('api.admin.logout');
    });
  });
});