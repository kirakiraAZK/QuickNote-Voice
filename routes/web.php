<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MeetingController;

// Route Halaman Utama
Route::get('/', [MeetingController::class, 'index'])->name('home');
Route::get('/quicknote', [MeetingController::class, 'index']);

// Route Halaman Riwayat
Route::get('/history', [MeetingController::class, 'history'])->name('history');

// Route API Simpan Data (Method POST)
Route::post('/meetings/store', [MeetingController::class, 'store'])->name('meetings.store');

Route::delete('/meetings/{id}', [MeetingController::class, 'destroy'])->name('meetings.destroy');