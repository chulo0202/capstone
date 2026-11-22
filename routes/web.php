<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DistributionBatchController;
use App\Http\Controllers\Admin\DistributionController;
use App\Http\Controllers\Admin\EligibilityRuleController;
use App\Http\Controllers\Admin\FarmerController as AdminFarmerController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SmsNotificationController;
use App\Http\Controllers\Admin\AdminRegistrationController;
use App\Http\Controllers\Auth\AdminRegistrationController as AuthAdminRegistrationController;
use App\Http\Controllers\Auth\RsbsaRegistrationController;
use App\Http\Controllers\Farmer\ApplicationController;
use App\Http\Controllers\Farmer\DashboardController as FarmerDashboardController;
use App\Http\Controllers\Farmer\EligibilityController;
use App\Http\Controllers\Farmer\NotificationController;
use App\Http\Controllers\Farmer\ProfileController as FarmerProfileController;
use App\Http\Controllers\Farmer\RecommendationController;
use App\Models\Farmer;
use App\Models\Program;
use App\Models\Distribution;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {

    if (Auth::check()) {
        return Auth::user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('farmer.dashboard');
    }

    $stats = [
        [
            'icon' => 'bi-people-fill',
            'value' => Farmer::count(),
            'suffix' => '+',
            'label' => 'Registered Farmers',
        ],

        [
            'icon' => 'bi-clipboard2-check',
            'value' => Program::where('is_active', 1)->count(),
            'suffix' => '',
            'label' => 'Active Programs',
        ],

        [
            'icon' => 'bi-box-seam',
            'value' => Distribution::whereNotNull('distributed_at')->count(),
            'suffix' => '+',
            'label' => 'Completed Distributions',
        ],
    ];

    return view('home', compact('stats'));
});

Route::get('/admin/register', [AuthAdminRegistrationController::class, 'create'])
    ->name('admin.register');

Route::post('/admin/register', [AuthAdminRegistrationController::class, 'store'])
    ->name('admin.register.store')
    ->middleware('throttle:5,1');

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        return Auth::user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('farmer.dashboard');
    })->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('admin')->name('admin.')->middleware(['admin', 'verified'])->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('dashboard');
         Route::get('/admin-registrations', [
            AdminRegistrationController::class,
            'index'
        ])->name('admin-registrations.index');

        Route::patch('/admin-registrations/{user}/approve', [
            AdminRegistrationController::class,
            'approve'
        ])->name('admin-registrations.approve');

        Route::patch('/admin-registrations/{user}/decline', [
            AdminRegistrationController::class,
            'decline'
        ])->name('admin-registrations.decline');

        Route::get('/applications', [\App\Http\Controllers\Admin\ApplicationController::class, 'index'])
            ->name('applications.index');

        // Admin - View submitted farmer application details
        Route::get(
            '/applications/{application}',
            [\App\Http\Controllers\Admin\ApplicationController::class, 'show']
        )->name('applications.show');

        Route::post('/farmers/rsbsa-lookup', \App\Http\Controllers\Admin\RsbsaFarmerLookupController::class)
            ->middleware('throttle:20,1')
            ->name('farmers.rsbsa-lookup');

        Route::post('/farmers/national-id/ocr', \App\Http\Controllers\Admin\NationalIdOcrController::class)
            ->middleware('throttle:6,1')
            ->name('farmers.national-id.ocr');

        Route::post('/applications/{application}/pcic-tracking', [\App\Http\Controllers\Admin\PcicTrackingController::class, 'store'])
            ->name('applications.pcic-tracking');

        // PCIC Damage Incident Management
        Route::get(
            '/pcic-incidents/{incident}/damage-photo',
            [\App\Http\Controllers\Admin\PcicDamageIncidentController::class, 'damagePhoto']
        )->name('pcic-incidents.damage-photo');

        Route::patch(
            '/pcic-incidents/{incident}/verify',
            [\App\Http\Controllers\Admin\PcicDamageIncidentController::class, 'verify']
        )->name('pcic-incidents.verify');

        Route::patch(
            '/pcic-incidents/{incident}/reject',
            [\App\Http\Controllers\Admin\PcicDamageIncidentController::class, 'reject']
        )->name('pcic-incidents.reject');

        Route::get(
            '/pcic-incidents',
            [\App\Http\Controllers\Admin\PcicDamageIncidentController::class, 'index']
        )->name('pcic-incidents.index');

        Route::patch('/applications/{application}/approve', [\App\Http\Controllers\Admin\ApplicationController::class, 'approve'])
            ->name('applications.approve');

        Route::patch('/applications/{application}/reject', [\App\Http\Controllers\Admin\ApplicationController::class, 'reject'])
            ->name('applications.reject');

        Route::get('/farmers', [AdminFarmerController::class, 'index'])
            ->name('farmers.index');

        Route::get('/farmers/create', [AdminFarmerController::class, 'create'])
            ->name('farmers.create');

        Route::post('/farmers', [AdminFarmerController::class, 'store'])
            ->name('farmers.store');

        Route::get('/farmers/{farmer}/edit', [AdminFarmerController::class, 'edit'])
            ->name('farmers.edit');

        Route::put('/farmers/{farmer}', [AdminFarmerController::class, 'update'])
            ->name('farmers.update');

        Route::patch('/farmers/{farmer}/verify', [AdminFarmerController::class, 'verify'])
            ->name('farmers.verify');

        Route::get('/farmers/{farmer}/valid-id', [AdminFarmerController::class, 'viewValidId'])
            ->name('farmers.valid-id');

        Route::get('/farmers/{farmer}', [AdminFarmerController::class, 'show'])
            ->name('farmers.show');

        Route::post('/farmers/{farmer}/deactivate', [AdminFarmerController::class, 'deactivate'])
            ->name('farmers.deactivate');

        Route::post('/farmers/{farmer}/reactivate', [AdminFarmerController::class, 'reactivate'])
            ->name('farmers.reactivate');

       Route::get('/programs', [ProgramController::class, 'index'])
            ->name('programs.index');

        Route::get('/programs/create', [ProgramController::class, 'create'])
            ->name('programs.create');

        Route::post('/programs', [ProgramController::class, 'store'])
            ->name('programs.store');

        Route::get('/programs/{program}', [ProgramController::class, 'show'])
            ->name('programs.show');

        Route::put('/programs/{program}', [ProgramController::class, 'update'])
            ->name('programs.update');

        Route::delete('/programs/{program}', [ProgramController::class, 'destroy'])
            ->name('programs.destroy');

        Route::patch('/programs/{program}/toggle', [ProgramController::class, 'toggle'])
            ->name('programs.toggle');

        Route::patch('/programs/{program}/archive', [ProgramController::class, 'archive'])
            ->name('programs.archive');

        Route::get('/recommendations', [ProgramController::class, 'recommendations'])
            ->name('recommendations.index');

        Route::get('/eligibility', [EligibilityRuleController::class, 'index'])
            ->name('eligibility.index');

        Route::post('/eligibility', [EligibilityRuleController::class, 'store'])
            ->name('eligibility.store');

        Route::delete('/eligibility/{eligibilityRule}', [EligibilityRuleController::class, 'destroy'])
            ->name('eligibility.destroy');

        Route::resource('distribution-batches', DistributionBatchController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update'])
            ->parameters(['distribution-batches' => 'batch']);
        Route::post('/distribution-batches/{batch}/publish', [DistributionBatchController::class, 'publish'])
            ->name('distribution-batches.publish');
        Route::post('/distribution-batches/{batch}/cancel', [DistributionBatchController::class, 'cancel'])
            ->name('distribution-batches.cancel');

        Route::get('/distributions', [DistributionController::class, 'index'])
            ->name('distributions.index');

        Route::post('/distributions/verify', [DistributionController::class, 'verify'])
            ->name('distributions.verify');

        Route::post('/distributions/release', [DistributionController::class, 'release'])
            ->name('distributions.release');

        Route::get('/announcements', [AdminAnnouncementController::class, 'index'])
            ->name('announcements.index');

        Route::post('/announcements', [AdminAnnouncementController::class, 'store'])
            ->name('announcements.store');

        Route::put('/announcements/{announcement}', [AdminAnnouncementController::class, 'update'])
            ->name('announcements.update');

        Route::delete('/announcements/{announcement}', [AdminAnnouncementController::class, 'destroy'])
            ->name('announcements.destroy');

        Route::get('/sms', [SmsNotificationController::class, 'index'])
            ->name('sms.index');

        Route::post('/sms/{notification}/retry', [SmsNotificationController::class, 'retry'])
            ->name('sms.retry');

        Route::get('/analytics', [AnalyticsController::class, 'index'])
            ->name('analytics.index');

        Route::get('/reports', [ReportController::class, 'index'])
            ->name('reports.index');

        Route::get('/reports/pdf', [ReportController::class, 'exportPdf'])
            ->name('reports.pdf');

        Route::get('/reports/excel', [ReportController::class, 'exportExcel'])
            ->name('reports.excel');

        Route::get('/audit', [AuditController::class, 'index'])
            ->name('audit.index');

        Route::get('/settings', [SettingsController::class, 'index'])
            ->name('settings.index');

        Route::post('/settings', [SettingsController::class, 'update'])
            ->name('settings.update');
    });

    /*
    |--------------------------------------------------------------------------
    | Farmer Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('farmer')->name('farmer.')->middleware('farmer')->group(function () {

        Route::get('/dashboard', [FarmerDashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/profile', [FarmerProfileController::class, 'show'])
            ->name('profile');

        Route::get('/profile/valid-id', [FarmerProfileController::class, 'viewValidId'])
            ->name('profile.valid-id');

        Route::put('/profile', [FarmerProfileController::class, 'update'])
            ->name('profile.update');

        Route::get('/qr-code', [FarmerProfileController::class, 'qrCode'])
            ->name('qr-code');

        Route::get('/qr-code/download', [FarmerProfileController::class, 'downloadQr'])
            ->name('qr-code.download');

        Route::get('/eligibility', [EligibilityController::class, 'index'])
            ->name('eligibility');

        Route::get('/recommendations', [RecommendationController::class, 'index'])
            ->name('recommendations');

        Route::post('/applications/{program}', [ApplicationController::class, 'store'])
            ->name('applications.store');

        Route::get('/applications', [ApplicationController::class, 'index'])
            ->name('applications.index');

        Route::get('/notifications', [NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::get('/notifications/distribution-schedules/{assignment}', [NotificationController::class, 'distributionSchedule'])
            ->whereNumber('assignment')->name('notifications.distribution-schedule');
        Route::get('/notifications/distributions/{distribution}', [NotificationController::class, 'distributionReceipt'])
            ->whereNumber('distribution')->name('notifications.distribution-receipt');

        Route::get('/announcements', fn () => to_route('farmer.notifications.index', ['category' => 'announcement']))
            ->name('announcements');
    });
});

/*
|--------------------------------------------------------------------------
| RSBSA Registration Check
|--------------------------------------------------------------------------
*/

Route::post('/register/check-rsbsa', [RsbsaRegistrationController::class, 'check'])
    ->name('register.check-rsbsa');

require __DIR__.'/auth.php';