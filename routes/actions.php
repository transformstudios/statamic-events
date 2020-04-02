<?php

use Illuminate\Support\Facades\Route;

Route::name('events.')->middleware('auth')->group(function () {
    Route::get('calendar/{id}', [CalendarController::class, '__invoke'])->name('calendar.get');
    // Route::post('subscription', [SubscriptionController::class, 'store'])->name('store');
    // Route::patch('subscription/{subscription}', [SubscriptionController::class, 'update'])->name('update');
    // Route::delete('subscription/{subscription}', [SubscriptionController::class, 'destroy'])->name('destroy');
});
