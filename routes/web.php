<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DistributionController;
use App\Http\Controllers\Admin\EligibilityRuleController;
use App\Http\Controllers\Admin\FarmerController as AdminFarmerController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\SmsNotificationController;
use App\Http\Controllers\Admin\WeatherController;
use App\Http\Controllers\Farmer\AnnouncementController as FarmerAnnouncementController;
use App\Http\Controllers\Farmer\DashboardController as FarmerDashboardController;
use App\Http\Controllers\Farmer\EligibilityController;
use App\Http\Controllers\Farmer\ProfileController as FarmerProfileController;
use App\Http\Controllers\Farmer\RecommendationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return Auth::user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('farmer.dashboard');
    }

    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return Auth::user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('farmer.dashboard');
    })->name('dashboard');

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/farmers', [AdminFarmerController::class, 'index'])->name('farmers.index');
        Route::get('/farmers/{farmer}', [AdminFarmerController::class, 'show'])->name('farmers.show');
        Route::delete('/farmers/{farmer}', [AdminFarmerController::class, 'destroy'])->name('farmers.destroy');
        Route::get('/programs', [ProgramController::class, 'index'])->name('programs.index');
        Route::post('/programs', [ProgramController::class, 'store'])->name('programs.store');
        Route::put('/programs/{program}', [ProgramController::class, 'update'])->name('programs.update');
        Route::delete('/programs/{program}', [ProgramController::class, 'destroy'])->name('programs.destroy');
        Route::patch('/programs/{program}/toggle', [ProgramController::class, 'toggle'])->name('programs.toggle');
        Route::get('/eligibility', [EligibilityRuleController::class, 'index'])->name('eligibility.index');
        Route::post('/eligibility', [EligibilityRuleController::class, 'store'])->name('eligibility.store');
        Route::delete('/eligibility/{eligibilityRule}', [EligibilityRuleController::class, 'destroy'])->name('eligibility.destroy');
        Route::get('/distributions', [DistributionController::class, 'index'])->name('distributions.index');
        Route::get('/distributions/scan', [DistributionController::class, 'scan'])->name('distributions.scan');
        Route::post('/distributions/verify', [DistributionController::class, 'verify'])->name('distributions.verify');
        Route::post('/distributions/release', [DistributionController::class, 'release'])->name('distributions.release');
        Route::get('/announcements', [AdminAnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/announcements', [AdminAnnouncementController::class, 'store'])->name('announcements.store');
        Route::put('/announcements/{announcement}', [AdminAnnouncementController::class, 'update'])->name('announcements.update');
        Route::delete('/announcements/{announcement}', [AdminAnnouncementController::class, 'destroy'])->name('announcements.destroy');
        Route::get('/sms', [SmsNotificationController::class, 'index'])->name('sms.index');
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/weather', [WeatherController::class, 'index'])->name('weather.index');
        Route::post('/weather/refresh', [WeatherController::class, 'refresh'])->name('weather.refresh');
    });

    Route::prefix('farmer')->name('farmer.')->middleware('farmer')->group(function () {
        Route::get('/dashboard', [FarmerDashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [FarmerProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [FarmerProfileController::class, 'update'])->name('profile.update');
        Route::get('/qr-code', [FarmerProfileController::class, 'qrCode'])->name('qr-code');
        Route::get('/qr-code/download', [FarmerProfileController::class, 'downloadQr'])->name('qr-code.download');
        Route::get('/eligibility', [EligibilityController::class, 'index'])->name('eligibility');
        Route::get('/recommendations', [RecommendationController::class, 'index'])->name('recommendations');
        Route::get('/announcements', [FarmerAnnouncementController::class, 'index'])->name('announcements');
    });
});

require __DIR__.'/auth.php';
