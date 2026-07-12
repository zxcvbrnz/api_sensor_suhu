<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\SensorData;

Route::post('/sensor-suhu', function (Request $request) {
    // Validasi data yang masuk
    $request->validate([
        'id_device' => 'required|string',
        'suhu' => 'required|numeric',
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
    ]);

    // Simpan ke database
    $data = SensorData::create([
        'id_device' => $request->id_device,
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

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');