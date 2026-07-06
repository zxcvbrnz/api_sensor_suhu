<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\SensorData;

Route::post('/sensor-suhu', function (Request $request) {
    // Validasi data yang masuk
    $request->validate([
        'suhu' => 'required|numeric',
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
    ]);

    // Simpan ke database
    $data = SensorData::create([
        'suhu' => $request->suhu,
        'latitude' => $request->latitude,
        'longitude' => $request->longitude,
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Data suhu dan lokasi berhasil disimpan',
        'data' => $data
    ], 201);
});

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__ . '/auth.php';