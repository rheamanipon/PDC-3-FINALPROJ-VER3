<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\ConcertController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Concerts listing and detail pages
Route::get('/concerts', [HomeController::class, 'concerts'])->name('concerts.index');

// More specific routes BEFORE generic ones
Route::middleware('auth')->group(function () {
    Route::get('/concerts/{concert}/book', [BookingController::class, 'create'])->name('bookings.create')->where('concert', '[0-9]+');
    Route::post('/concerts/{concert}/book', [BookingController::class, 'store'])->name('bookings.store')->where('concert', '[0-9]+');
});

// Generic concert show route
Route::get('/concerts/{concert}', [HomeController::class, 'show'])->name('concerts.show')->where('concert', '[0-9]+');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('bookings', BookingController::class, ['only' => ['index', 'show']]);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes
Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::resource('concerts', ConcertController::class);
    Route::resource('venues', VenueController::class);
    // Add more admin routes
});

require __DIR__.'/auth.php';
