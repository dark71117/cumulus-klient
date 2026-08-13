<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\IpAdminController;
use App\Http\Controllers\KlientController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/klient');

Route::prefix('klient')->group(function () {
    Route::get('/', [LoginController::class, 'show'])->name('klient.login');
    Route::post('/', [LoginController::class, 'store'])->name('klient.login.submit');

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [LoginController::class, 'destroy'])->name('klient.logout');
        Route::post('/setcustomer', [KlientController::class, 'setCustomer'])->name('klient.setcustomer');
        Route::post('/weathertabs', [KlientController::class, 'weatherTabs'])->name('klient.weathertabs');
        Route::post('/actualtabs', [KlientController::class, 'actualTabs'])->name('klient.actualtabs');
        Route::post('/tabstatus', [KlientController::class, 'tabStatus'])->name('klient.tabstatus');
        Route::post('/content', [KlientController::class, 'content'])->name('klient.content');
        Route::get('/pobierz/tv/{filename}', [KlientController::class, 'downloadTv'])->name('klient.tvfile')->where('filename', '.*');
        Route::get('/kamery/{variant}/{number}/{region?}', [KlientController::class, 'cameras'])->name('klient.cameras');
        Route::get('/mmregion/{id}/{b}/{c}', [KlientController::class, 'mmRegion']);
        Route::get('/mmregion', [KlientController::class, 'mmRegion']);
        Route::get('/mmchart/{id}/{product?}/{print?}', [KlientController::class, 'mmChart']);
        Route::get('/mmpng/{id}', [KlientController::class, 'mmPng']);
        Route::middleware(EnsureAdmin::class)->group(function () {
            Route::get('/ipadmin', [IpAdminController::class, 'index'])->name('klient.ipadmin');
            Route::post('/ipadmin', [IpAdminController::class, 'index']);
            Route::post('/ipadmin/active', [IpAdminController::class, 'saveActive'])->name('klient.ipadmin.active');
            Route::post('/ipadmin/ip', [IpAdminController::class, 'storeIp'])->name('klient.ipadmin.ip');
            Route::get('/ipdelete/{id}', [IpAdminController::class, 'destroyIp'])->name('klient.ipdelete');
            Route::get('/ipjournal/{id}', [IpAdminController::class, 'journal'])->name('klient.ipjournal');
        });
    });
});
