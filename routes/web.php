<?php

use App\Http\Controllers\AnalizaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\GfsMapController;
use App\Http\Controllers\IpAdminController;
use App\Http\Controllers\KlientController;
use App\Http\Controllers\UserAdminController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureModule;
use App\Http\Middleware\RefreshPanelUser;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/klient');

Route::prefix('klient')->group(function () {
    Route::get('/', [LoginController::class, 'show'])->name('klient.login');
    Route::post('/', [LoginController::class, 'store'])->name('klient.login.submit');

    Route::middleware(['auth', RefreshPanelUser::class])->group(function () {
        Route::post('/logout', [LoginController::class, 'destroy'])->name('klient.logout');
        Route::post('/setcustomer', [KlientController::class, 'setCustomer'])->name('klient.setcustomer');
        Route::post('/weathertabs', [KlientController::class, 'weatherTabs'])->name('klient.weathertabs');
        Route::post('/actualtabs', [KlientController::class, 'actualTabs'])->name('klient.actualtabs');
        Route::post('/tabstatus', [KlientController::class, 'tabStatus'])->name('klient.tabstatus');
        Route::post('/content', [KlientController::class, 'content'])->name('klient.content');
        Route::post('/analiza', [AnalizaController::class, 'query'])->middleware(EnsureModule::class.':analiza')->name('klient.analiza');
        Route::post('/maplimit', [KlientController::class, 'saveMapLimit'])->name('klient.maplimit');
        Route::get('/pobierz/tv/{filename}', [KlientController::class, 'downloadTv'])->name('klient.tvfile')->where('filename', '.*');
        Route::get('/kamery/{variant}/{number}/{region?}', [KlientController::class, 'cameras'])->middleware(EnsureModule::class.':dodatki')->name('klient.cameras');
        Route::get('/mmregion/{id}/{b}/{c}', [KlientController::class, 'mmRegion'])->middleware(EnsureModule::class.':dodatki');
        Route::get('/mmregion', [KlientController::class, 'mmRegion'])->middleware(EnsureModule::class.':dodatki');
        Route::get('/mmchart/{id}/{product?}/{print?}', [KlientController::class, 'mmChart'])->middleware(EnsureModule::class.':dodatki');
        Route::get('/mmpng/{id}', [KlientController::class, 'mmPng'])->middleware(EnsureModule::class.':dodatki');
        Route::get('/gfs/klatki/{run}/{region}/{product}', [GfsMapController::class, 'frames'])
            ->middleware(EnsureModule::class.':gfs')
            ->where(['run' => '\d{4}\.\d{2}\.\d{2}\.\d{2}', 'region' => 'Polska|Europa', 'product' => '[A-Za-z0-9_]+']);
        Route::get('/gfs/obraz/{run}/{region}/{product}/{hour}', [GfsMapController::class, 'image'])
            ->middleware(EnsureModule::class.':gfs')
            ->where(['run' => '\d{4}\.\d{2}\.\d{2}\.\d{2}', 'region' => 'Polska|Europa', 'product' => '[A-Za-z0-9_]+', 'hour' => '\d{3}']);
        Route::get('/uzytkownicy', [UserAdminController::class, 'index'])->name('klient.users');
        Route::post('/uzytkownicy', [UserAdminController::class, 'save'])->name('klient.users.save');
        Route::post('/uzytkownicy/usun', [UserAdminController::class, 'destroy'])->name('klient.users.delete');
        Route::get('/profil', [UserAdminController::class, 'profile'])->name('klient.profile');
        Route::post('/profil', [UserAdminController::class, 'updateProfile'])->name('klient.profile.save');
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
