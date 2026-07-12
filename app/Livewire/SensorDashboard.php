<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SensorData;
use Illuminate\Support\Facades\DB;

class SensorDashboard extends Component
{
    // State untuk menyimpan device mana yang sedang dilihat log-nya
    public $selectedDevice = null;
    public $viewingLogs = false;

    // Aksi ketika tombol "History Log" diklik di view Blade
    public function showLogs($idDevice)
    {
        $this->selectedDevice = $idDevice;
        $this->viewingLogs = true;
    }

    // Aksi untuk kembali dari halaman log ke tabel utama list device
    public function closeLogs()
    {
        $this->selectedDevice = null;
        $this->viewingLogs = false;
    }

    public function render()
    {
        // 1. QUERY UNTUK TABEL UTAMA: Ambil data TERBARU dari MASING-MASING id_device
        // Subquery untuk mencari timestamp created_at paling akhir per id_device
        $subQuery = SensorData::select('id_device', DB::raw('MAX(created_at) as max_created_at'))
            ->groupBy('id_device');

        // Join data asli dengan subquery agar mendapatkan row suhu & koordinat yang sesuai
        $devices = SensorData::joinSub($subQuery, 'latest_records', function ($join) {
            $join->on('sensor_data.id_device', '=', 'latest_records.id_device')
                ->on('sensor_data.created_at', '=', 'latest_records.max_created_at');
        })
            ->orderBy('sensor_data.id_device', 'asc')
            ->get();

        // 2. QUERY UNTUK DETAIL LOG: Jika sedang melihat riwayat, ambil 15 log terakhir device tersebut
        $deviceLogs = [];
        if ($this->viewingLogs && $this->selectedDevice) {
            $deviceLogs = SensorData::where('id_device', $this->selectedDevice)
                ->orderBy('created_at', 'desc')
                ->take(15)
                ->get();
        }

        // Return semua variabel ke view Livewire Anda
        return view('livewire.sensor-dashboard', [
            'devices' => $devices,
            'deviceLogs' => $deviceLogs
        ]);
    }
}
