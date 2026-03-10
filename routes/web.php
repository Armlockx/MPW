<?php

use App\Http\Controllers\BatchController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('batches', BatchController::class)->except(['edit']);
    Route::post('batches/{batch}/process', [BatchController::class, 'process'])->name('batches.process');

    Route::post('batches/{batch}/videos', [VideoController::class, 'store'])->name('videos.store');
    Route::delete('videos/{video}', [VideoController::class, 'destroy'])->name('videos.destroy');
    Route::get('videos/{video}/download', [VideoController::class, 'download'])->name('videos.download');
    Route::get('batches/{batch}/download', [VideoController::class, 'downloadBatch'])->name('batches.download');
});

require __DIR__.'/settings.php';
