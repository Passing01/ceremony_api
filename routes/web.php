<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TemplateBuilderController;

Route::get('/', function () {
    return view('welcome');
});

Route::group(['prefix' => 'builder', 'as' => 'builder.'], function () {
    Route::get('/', [TemplateBuilderController::class, 'index'])->name('index');
    Route::get('/edit/{id}', [TemplateBuilderController::class, 'edit'])->name('edit');
    Route::post('/save', [TemplateBuilderController::class, 'save'])->name('save');
    Route::get('/setup', [TemplateBuilderController::class, 'setup'])->name('setup');
    Route::post('/store', [TemplateBuilderController::class, 'store'])->name('store');
});
