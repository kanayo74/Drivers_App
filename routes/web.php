<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\TripController          as AdminTripController;
use App\Http\Controllers\Admin\DriverController        as AdminDriverController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Admin\FuelController          as AdminFuelController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\TrainingController;
use App\Http\Controllers\Admin\AwardController;
use App\Http\Controllers\Driver\DriverDashboardController;
use App\Http\Controllers\Driver\DriverTripController;
use App\Http\Controllers\Driver\DriverFuelController;
use App\Http\Controllers\Staff\StaffTripController;
use App\Http\Controllers\Staff\RatingController;

// ─── Public ───────────────────────────────────────────────────────────────────
Route::get('/', fn () => redirect()->route('login'));
Route::get('/login',  [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::post('/logout',[LoginController::class, 'logout'])->name('logout')->middleware('auth');

// ─── ADMIN ────────────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth','role:admin'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::prefix('trips')->name('trips.')->group(function () {
        Route::get('/',                [AdminTripController::class,'index'])->name('index');
        Route::post('/',               [AdminTripController::class,'store'])->name('store');
        Route::get('/{trip}',          [AdminTripController::class,'show'])->name('show');
        Route::post('/{trip}/approve', [AdminTripController::class,'approve'])->name('approve');
        Route::post('/{trip}/reject',  [AdminTripController::class,'reject'])->name('reject');
    });

    Route::prefix('drivers')->name('drivers.')->group(function () {
        Route::get('/',                 [AdminDriverController::class,'index'])->name('index');
        Route::post('/',                [AdminDriverController::class,'store'])->name('store');
        Route::get('/{driver}',         [AdminDriverController::class,'show'])->name('show');
        Route::post('/{driver}/status', [AdminDriverController::class,'updateStatus'])->name('status');
    });

    Route::prefix('vehicles')->name('vehicles.')->group(function () {
        Route::get('/',                         [VehicleController::class,'index'])->name('index');
        Route::post('/',                        [VehicleController::class,'store'])->name('store');
        Route::get('/{vehicle}',                [VehicleController::class,'show'])->name('show');
        Route::post('/{vehicle}/assign-driver', [VehicleController::class,'assignDriver'])->name('assign-driver');
    });

    Route::prefix('fuel')->name('fuel.')->group(function () {
        Route::get('/',                           [AdminFuelController::class,'index'])->name('index');
        Route::post('/{fuelRequest}/acknowledge', [AdminFuelController::class,'acknowledge'])->name('acknowledge');
    });

    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/',              [PaymentController::class,'index'])->name('index');
        Route::post('/pay-all',      [PaymentController::class,'payAll'])->name('pay-all');
        Route::post('/{driver}/pay', [PaymentController::class,'pay'])->name('pay');
    });

    Route::prefix('maintenance')->name('maintenance.')->group(function () {
        Route::get('/',  [MaintenanceController::class,'index'])->name('index');
        Route::post('/', [MaintenanceController::class,'store'])->name('store');
    });

    Route::prefix('training')->name('training.')->group(function () {
        Route::get('/',                     [TrainingController::class,'index'])->name('index');
        Route::post('/',                    [TrainingController::class,'store'])->name('store');
        Route::post('/{assignment}/status', [TrainingController::class,'updateStatus'])->name('status');
    });

    Route::prefix('awards')->name('awards.')->group(function () {
        Route::get('/',               [AwardController::class,'index'])->name('index');
        Route::post('/compute-month', [AwardController::class,'computeMonth'])->name('compute-month');
    });

    Route::get('/notifications', function () {
        return view('admin.notifications', [
            'notifications' => auth()->user()->notifications()->paginate(30),
        ]);
    })->name('notifications');

    Route::post('/notifications/read-all', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return back()->with('success', 'All notifications marked as read.');
    })->name('notifications.read-all');
});

// ─── DRIVER ───────────────────────────────────────────────────────────────────
Route::prefix('driver')->name('driver.')->middleware(['auth','role:driver'])->group(function () {
    Route::get('/dashboard', [DriverDashboardController::class,'index'])->name('dashboard');

    Route::prefix('trips')->name('trips.')->group(function () {
        Route::get('/',                     [DriverTripController::class,'index'])->name('index');
        Route::post('/{trip}/start',        [DriverTripController::class,'start'])->name('start');
        Route::post('/{trip}/complete',     [DriverTripController::class,'complete'])->name('complete');
        Route::post('/{trip}/log-location', [DriverTripController::class,'logLocation'])->name('log-location');
    });

    Route::post('/fuel/request',                [DriverFuelController::class,'request'])->name('fuel.request');
    Route::post('/fuel/{vehicle}/mark-fuelled', [DriverFuelController::class,'markFuelled'])->name('fuel.mark-fuelled');

    Route::get('/notifications', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return view('driver.notifications', [
            'notifications' => auth()->user()->notifications()->paginate(20),
        ]);
    })->name('notifications');
});

// ─── STAFF / MARKETER ────────────────────────────────────────────────────────
Route::prefix('staff')->name('staff.')->middleware(['auth','role:staff,marketer,admin'])->group(function () {
    Route::prefix('trips')->name('trips.')->group(function () {
        Route::get('/',               [StaffTripController::class,'index'])->name('index');
        Route::post('/',              [StaffTripController::class,'store'])->name('store');
        Route::post('/{trip}/cancel', [StaffTripController::class,'cancel'])->name('cancel');
        Route::post('/{trip}/rate',   [RatingController::class,'store'])->name('rate');
    });

    Route::get('/notifications', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return view('staff.notifications', [
            'notifications' => auth()->user()->notifications()->paginate(20),
        ]);
    })->name('notifications');
});
