<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Frontend\FrontendController;
use App\Http\Controllers\Restaurant\RestaurantController;
use App\Http\Controllers\Restaurant\RecurringDonationController;
use App\Http\Controllers\NGO\NGOController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\HandoverController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public Frontend Routes
Route::get('/', [FrontendController::class, 'home'])->name('home');
Route::get('/about', [FrontendController::class, 'about'])->name('about');
Route::get('/services', [FrontendController::class, 'services'])->name('services');
Route::get('/how-it-works', [FrontendController::class, 'howItWorks'])->name('how-it-works');
Route::get('/faq', [FrontendController::class, 'faq'])->name('faq');
Route::get('/contact', [FrontendController::class, 'contact'])->name('contact');
Route::post('/contact', [FrontendController::class, 'storeContact'])->name('contact.store');
Route::get('/privacy', [FrontendController::class, 'privacy'])->name('privacy');
Route::get('/terms', [FrontendController::class, 'terms'])->name('terms');

// Public Certificate Verification & Handover QR routes
Route::get('/certificates/verify/{token}', [CertificateController::class, 'verify'])->name('certificates.verify');
Route::get('/handover/verify-qr/{token}', [HandoverController::class, 'verifyQr'])->middleware('throttle:qr-verify')->name('handover.verify.qr');

// Authenticated Routes
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Global Dashboard Redirection Route based on Role
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        } elseif ($user->role === 'restaurant') {
            return redirect()->route('restaurant.dashboard');
        } elseif ($user->role === 'ngo') {
            return redirect()->route('ngo.dashboard');
        }
        abort(403, 'Unauthorized role.');
    })->name('dashboard');

    // Profile Management Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notification Read route
    Route::post('/notifications/{id}/read', [FrontendController::class, 'readNotification'])->name('notifications.read');

    // Handover Verification Routes (OTP / QR Handover)
    Route::post('/handover/{id}/start-pickup', [HandoverController::class, 'startPickup'])->name('handover.start');
    Route::get('/handover/{id}/verification-data', [HandoverController::class, 'getVerificationData'])->name('handover.data');
    Route::post('/handover/{id}/verify-otp', [HandoverController::class, 'verifyOtp'])->middleware('throttle:otp-verify')->name('handover.verify.otp');
    Route::post('/handover/{id}/resend-otp', [HandoverController::class, 'resendOtp'])->middleware('throttle:otp-resend')->name('handover.resend.otp');
    Route::post('/handover/{id}/complete', [HandoverController::class, 'completeDonation'])->name('handover.complete');

    // Pickup Coordination Real-Time Chat Routes
    Route::get('/chat/{id}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{id}/send', [ChatController::class, 'send'])->name('chat.send');
    Route::get('/chat/{id}/poll', [ChatController::class, 'poll'])->name('chat.poll');

    // Certificate Download
    Route::get('/certificates/{id}/download', [CertificateController::class, 'download'])->name('certificates.download');

    // Two-Way Ratings and Reviews
    Route::post('/reviews/{pickupRequestId}', [ReviewController::class, 'store'])->name('reviews.store');

    // ----------------------------------------------------
    // RESTAURANT ROUTES (Role: restaurant)
    // ----------------------------------------------------
    Route::middleware(['role:restaurant'])->prefix('restaurant')->name('restaurant.')->group(function () {
        Route::get('/dashboard', [RestaurantController::class, 'dashboard'])->name('dashboard');
        
        // Food Donations CRUD
        Route::resource('donations', RestaurantController::class)->except(['show']);
        Route::get('/history', [RestaurantController::class, 'history'])->name('history');
        Route::post('/requests/{id}/status', [RestaurantController::class, 'updateRequestStatus'])->name('requests.status');

        // Recurring Donations
        Route::get('/recurring', [RecurringDonationController::class, 'index'])->name('recurring.index');
        Route::post('/recurring', [RecurringDonationController::class, 'store'])->name('recurring.store');
        Route::post('/recurring/{id}/toggle', [RecurringDonationController::class, 'toggle'])->name('recurring.toggle');
        Route::delete('/recurring/{id}', [RecurringDonationController::class, 'destroy'])->name('recurring.destroy');

        // Certificate History
        Route::get('/certificates', [CertificateController::class, 'restaurantIndex'])->name('certificates.index');
        
        // Profile update
        Route::get('/profile', [RestaurantController::class, 'profile'])->name('profile');
        Route::put('/profile', [RestaurantController::class, 'updateProfile'])->name('profile.update');
    });

    // ----------------------------------------------------
    // NGO ROUTES (Role: ngo)
    // ----------------------------------------------------
    Route::middleware(['role:ngo'])->prefix('ngo')->name('ngo.')->group(function () {
        Route::get('/dashboard', [NGOController::class, 'dashboard'])->name('dashboard');
        
        // Food listings & search
        Route::get('/listings', [NGOController::class, 'listings'])->name('listings');
        Route::get('/donations/{id}', [NGOController::class, 'showDonation'])->name('donations.show');
        
        // Pickup requests CRUD
        Route::post('/donations/{id}/request', [NGOController::class, 'requestPickup'])->name('requests.store');
        Route::post('/requests/{id}/cancel', [NGOController::class, 'cancelRequest'])->name('requests.cancel');
        Route::post('/requests/{id}/status', [NGOController::class, 'updateRequestStatus'])->name('requests.status');
        Route::get('/history', [NGOController::class, 'history'])->name('history');
        
        // Profile update
        Route::get('/profile', [NGOController::class, 'profile'])->name('profile');
        Route::put('/profile', [NGOController::class, 'updateProfile'])->name('profile.update');
    });

    // ----------------------------------------------------
    // ADMIN ROUTES (Role: admin)
    // ----------------------------------------------------
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        
        // Management resources
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::post('/users/{id}/toggle', [AdminController::class, 'toggleUserStatus'])->name('users.toggle');
        
        Route::get('/restaurants', [AdminController::class, 'restaurants'])->name('restaurants');
        Route::post('/restaurants/{id}/approve', [AdminController::class, 'approveRestaurant'])->name('restaurants.approve');
        
        Route::get('/ngos', [AdminController::class, 'ngos'])->name('ngos');
        Route::post('/ngos/{id}/approve', [AdminController::class, 'approveNGO'])->name('ngos.approve');
        
        // Document preview
        Route::get('/documents/{type}/{id}', [AdminController::class, 'viewDocument'])->name('documents.view');
        
        Route::get('/donations', [AdminController::class, 'donations'])->name('donations');
        Route::get('/requests', [AdminController::class, 'requests'])->name('requests');
        
        // Contact messages & notifications
        Route::get('/contacts', [AdminController::class, 'contacts'])->name('contacts');
        Route::post('/contacts/{id}/reply', [AdminController::class, 'replyContact'])->name('contacts.reply');
        
        // Analytics & Reports
        Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
        Route::get('/reports/pdf', [AdminController::class, 'exportPDF'])->name('reports.pdf');
        Route::get('/reports/excel', [AdminController::class, 'exportExcel'])->name('reports.excel');

        // Reviews Moderation
        Route::get('/reviews', [ReviewController::class, 'adminIndex'])->name('reviews.index');
        Route::post('/reviews/{id}/toggle', [ReviewController::class, 'toggleStatus'])->name('reviews.toggle');
    });

});

require __DIR__.'/auth.php';
