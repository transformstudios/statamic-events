<?php

use Illuminate\Support\Facades\Route;
use TransformStudios\Events\Http\Controllers\IcsController;

Route::name('events.')->group(function () {
    Route::get('ics', [IcsController::class, '__invoke'])->name('ics.show');
});
