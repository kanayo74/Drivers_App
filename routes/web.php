<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\{AdminDashboardController, TripController as AdminTripController, DriverController as AdminDriverController, VehicleController, FuelController as AdminFuelController, PaymentController, MaintenanceController, TrainingController, AwardController};
use App\Http\Controllers\Driver\{DriverDashboardController, DriverMaintenanceController, DriverTripController, DriverFuelController};
use App\Http\Controllers\Staff\{StaffTripController, RatingController};


Route::get('/', fn () => redirect()->route('login'));
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// ── ADMIN ─────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
	Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
	
	// Driver routes
	Route::post('drivers/{driver}/license', [AdminDriverController::class, 'updateLicense'])
		->name('drivers.updateLicense');
	Route::post('drivers/{driver}/remove', [AdminDriverController::class, 'remove'])
		->name('drivers.remove');
	Route::get('drivers/{driver}/training', [AdminDriverController::class, 'training'])
		->name('drivers.training');
	
	// Maintenance routes (explicit)
	Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
	Route::post('/maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
	Route::post('/maintenance/{vehicle}/done', [MaintenanceController::class, 'markDone'])->name('maintenance.done');
	Route::post('/maintenance/{vehicle}/report', [MaintenanceController::class, 'reportIssue'])->name('maintenance.report');

// ...existing code...
	
	Route::prefix('trips')->name('trips.')->group(function () {
		Route::get('/', [AdminTripController::class, 'index'])->name('index');
		Route::post('/', [AdminTripController::class, 'store'])->name('store');
		Route::get('/{trip}', [AdminTripController::class, 'show'])->name('show');
		Route::post('/{trip}/approve', [AdminTripController::class, 'approve'])->name('approve');
		Route::post('/{trip}/reject', [AdminTripController::class, 'reject'])->name('reject');
	});
	
	Route::prefix('drivers')->name('drivers.')->group(function () {
		Route::get('/', [AdminDriverController::class, 'index'])->name('index');
		Route::post('/', [AdminDriverController::class, 'store'])->name('store');
		Route::get('/{driver}', [AdminDriverController::class, 'show'])->name('show');
		Route::put('/{driver}', [AdminDriverController::class, 'update'])->name('update');
		Route::post('/{driver}/status', [AdminDriverController::class, 'updateStatus'])->name('status');
		Route::post('/{driver}/deactivate', [AdminDriverController::class, 'deactivate'])->name('deactivate');
		Route::post('/{driver}/reactivate', [AdminDriverController::class, 'reactivate'])->name('reactivate');
	});
	
	Route::get('/holidays', [AdminDriverController::class, 'holidays'])->name('holidays');
	Route::post('/holidays', [AdminDriverController::class, 'storeHoliday'])->name('holidays.store');
	
	Route::prefix('vehicles')->name('vehicles.')->group(function () {
		Route::get('/', [VehicleController::class, 'index'])->name('index');
		Route::post('/', [VehicleController::class, 'store'])->name('store');
		Route::get('/{vehicle}', [VehicleController::class, 'show'])->name('show');
		Route::post('/{vehicle}/assign-driver', [VehicleController::class, 'assignDriver'])->name('assign-driver');
	});
	
	Route::prefix('fuel')->name('fuel.')->group(function () {
		Route::get('/', [AdminFuelController::class, 'index'])->name('index');
		Route::post('/{fuelRequest}/acknowledge', [AdminFuelController::class, 'acknowledge'])->name('acknowledge');
	});
	
	Route::prefix('payments')->name('payments.')->group(function () {
		Route::get('/', [PaymentController::class, 'index'])->name('index');
		Route::post('/pay-all', [PaymentController::class, 'payAll'])->name('pay-all');
		Route::post('/{driver}/pay', [PaymentController::class, 'pay'])->name('pay');
	});
	
	Route::prefix('maintenance')->name('maintenance.')->group(function () {
		Route::get('/', [MaintenanceController::class, 'index'])->name('index');
		Route::post('/', [MaintenanceController::class, 'store'])->name('store');
		Route::post('/needs/{need}/ack', [MaintenanceController::class, 'acknowledgeNeed'])->name('needs.ack');
		Route::post('/needs/{need}/resolve', [MaintenanceController::class, 'resolveNeed'])->name('needs.resolve');
	});
	
	Route::prefix('training')->name('training.')->group(function () {
		Route::get('/', [TrainingController::class, 'index'])->name('index');
		Route::post('/', [TrainingController::class, 'store'])->name('store');
		Route::post('/{assignment}/status', [TrainingController::class, 'updateStatus'])->name('status');
	});
	
	Route::prefix('awards')->name('awards.')->group(function () {
		Route::get('/', [AwardController::class, 'index'])->name('index');
		Route::post('/compute-month', [AwardController::class, 'computeMonth'])->name('compute-month');
	});
	
	Route::get('/notifications', function () {
		return view('admin.notifications', ['notifications' => auth()->user()->notifications()->paginate(30)]);
	})->name('notifications');
	Route::post('/notifications/read-all', function () {
		auth()->user()->unreadNotifications->markAsRead();
		return back()->with('success', 'All notifications marked as read.');
	})->name('notifications.read-all');
});

// ── DRIVER ────────────────────────────────────────────────────────────
Route::prefix('driver')->name('driver.')->middleware(['auth', 'role:driver'])->group(function () {
	Route::get('/dashboard', [DriverDashboardController::class, 'index'])->name('dashboard');
	
	Route::prefix('maintenance')->name('maintenance.')->group(function () {
		Route::get('/', [DriverMaintenanceController::class, 'index'])->name('index');
		Route::post('/', [DriverMaintenanceController::class, 'store'])->name('store');
		Route::post('/{vehicle}/report', [DriverMaintenanceController::class, 'reportIssue'])->name('maintenance.report');
	});
	
	Route::prefix('trips')->name('trips.')->group(function () {
		Route::get('/', [DriverTripController::class, 'index'])->name('index');
		Route::post('/{trip}/start', [DriverTripController::class, 'start'])->name('start');
		Route::post('/{trip}/complete', [DriverTripController::class, 'complete'])->name('complete');
		Route::match(['get', 'post'], '/{trip}/complete', [DriverTripController::class, 'complete'])->name('complete');
		Route::post('/{trip}/log-location', [DriverTripController::class, 'logLocation'])->name('log-location');
	});
	
	Route::post('/fuel/request', [DriverFuelController::class, 'request'])->name('fuel.request');
	Route::post('/fuel/{vehicle}/mark-fuelled', [DriverFuelController::class, 'markFuelled'])->name('fuel.mark-fuelled');
	
	Route::post('/maintenance/done/{log}', [DriverTripController::class, 'markMaintenanceDone'])->name('maintenance.done');
	Route::post('/maintenance/report-need', [DriverTripController::class, 'reportMaintenanceNeed'])->name('maintenance.report-need');
	
	Route::get('/training', function () {
		$assignments = auth()->user()->trainingAssignments()
			->with('assignedBy')->orderByDesc('training_date')->paginate(15);
		return view('driver.training', compact('assignments'));
	})->name('training');
	
	Route::get('/notifications', function () {
		auth()->user()->unreadNotifications->markAsRead();
		return view('driver.notifications', ['notifications' => auth()->user()->notifications()->paginate(20)]);
	})->name('notifications');
});

// ── STAFF / MARKETER ──────────────────────────────────────────────────
Route::prefix('staff')->name('staff.')->middleware(['auth', 'role:staff,marketer,admin'])->group(function () {
	Route::prefix('trips')->name('trips.')->group(function () {
		Route::get('/', [StaffTripController::class, 'index'])->name('index');
		Route::post('/', [StaffTripController::class, 'store'])->name('store');
		Route::post('/{trip}/cancel', [StaffTripController::class, 'cancel'])->name('cancel');
		Route::post('/{trip}/rate', [RatingController::class, 'store'])->name('rate');
	});
	Route::get('/notifications', function () {
		auth()->user()->unreadNotifications->markAsRead();
		return view('staff.notifications', ['notifications' => auth()->user()->notifications()->paginate(20)]);
	})->name('notifications');
});
