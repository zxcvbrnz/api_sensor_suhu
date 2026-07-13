<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SensorData;
use Illuminate\Support\Facades\DB;

class SensorDashboard extends Component
{
    // State Navigasi Riwayat Log
    public $selectedDevice = null;
    public $viewingLogs = false;

    // State Baru: Kontrol Modal Google Maps
    public $showMapModal = false;
    public $mapLatitude = null;
    public $mapLongitude = null;
    public $mapDeviceTarget = null;

    // Aksi ketika tombol "History Log" diklik
    public function showLogs($idDevice)
    {
        $this->selectedDevice = $idDevice;
        $this->viewingLogs = true;
    }

    // Aksi kembali ke tabel utama
    public function closeLogs()
    {
        $this->selectedDevice = null;
        $this->viewingLogs = false;
    }

    // Aksi untuk memicu Modal Google Maps keluar (Bisa dipanggil dari mana saja)
    public function openMap($latitude, $longitude, $idDevice)
    {
        $this->mapLatitude = $latitude;
        $this->mapLongitude = $longitude;
        $this->mapDeviceTarget = $idDevice;
        $this->showMapModal = true;
    }

    // Aksi menutup Modal Google Maps
    public function closeMap()
    {
        $this->showMapModal = false;
        $this->mapLatitude = null;
        $this->mapLongitude = null;
        $this->mapDeviceTarget = null;
    }

    public function render()
    {
        // 1. QUERY LIST DEVICE (Terbaru per unit)
        $subQuery = SensorData::select('id_device', DB::raw('MAX(created_at) as max_created_at'))
            ->groupBy('id_device');

        $devices = SensorData::joinSub($subQuery, 'latest_records', function ($join) {
            $join->on('sensor_data.id_device', '=', 'latest_records.id_device')
                ->on('sensor_data.created_at', '=', 'latest_records.max_created_at');
        })
            ->orderBy('sensor_data.id_device', 'asc')
            ->get();

        // 2. QUERY HISTORY LOG PER DEVICE
        $deviceLogs = [];
        if ($this->viewingLogs && $this->selectedDevice) {
            $deviceLogs = SensorData::where('id_device', $this->selectedDevice)
                ->orderBy('created_at', 'desc')
                ->take(15)
                ->get();
        }

        return view('livewire.sensor-dashboard', [
            'devices' => $devices,
            'deviceLogs' => $deviceLogs
        ]);
    }
}