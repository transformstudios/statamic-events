<?php

use Illuminate\Support\Facades\Route;
use TransformStudios\Events\Http\Controllers\CalendarController;

Route::name('events.')->middleware('auth')->group(function () {
    Route::get('calendar/{id}', [CalendarController::class, '__invoke'])->name('calendar.get');
});
