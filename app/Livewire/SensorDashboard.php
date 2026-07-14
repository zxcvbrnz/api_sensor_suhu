<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SensorData; // Sesuaikan dengan nama model IoT Anda
use Illuminate\Support\Facades\DB;

class SensorDashboard extends Component
{
    use WithPagination;

    // === DEKLARASI PROPERTI PUBLIK (Tambahkan/Pastikan baris ini ada) ===
    public $viewingLogs = false; // Default bernilai false agar tidak langsung membuka halaman log
    public $selectedDevice = null;

    // Edit nama device
    public $editingDeviceId = null;
    public $inputNamaDevice = '';

    // Modal Map
    public $showMapModal = false;
    public $mapLatitude = 0;
    public $mapLongitude = 0;
    public $mapDeviceTarget = '';
    // ====================================================================

    protected $listeners = ['refreshComponent' => '$refresh'];

    /**
     * Menghitung jarak antara dua titik koordinat menggunakan Formula Haversine
     */
    private function calculateHaversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        if (empty($lat1) || empty($lon1) || empty($lat2) || empty($lon2)) {
            return 0;
        }

        if (($lat1 == $lat2) && ($lon1 == $lon2)) {
            return 0;
        }

        $earthRadius = 6371000; // Radius bumi dalam Meter

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($angle * $earthRadius, 2);
    }

    public function showLogs($deviceId)
    {
        $this->selectedDevice = $deviceId;
        $this->viewingLogs = true;
        $this->resetPage();
    }

    public function closeLogs()
    {
        $this->viewingLogs = false;
        $this->selectedDevice = null;
    }

    public function startEditName($deviceId, $currentName)
    {
        $this->editingDeviceId = $deviceId;
        $this->inputNamaDevice = $currentName;
    }

    public function cancelEdit()
    {
        $this->editingDeviceId = null;
        $this->inputNamaDevice = '';
    }

    public function saveDeviceName()
    {
        if ($this->editingDeviceId) {
            DB::table('devices')->updateOrInsert(
                ['id_device' => $this->editingDeviceId],
                ['nama_device' => $this->inputNamaDevice, 'updated_at' => now()]
            );

            $this->editingDeviceId = null;
            $this->inputNamaDevice = '';

            session()->flash('message', 'Nama perangkat berhasil diperbarui.');
        }
    }

    public function openMap($latitude, $longitude, $deviceId)
    {
        $this->mapLatitude = $latitude;
        $this->mapLongitude = $longitude;
        $this->mapDeviceTarget = $deviceId;
        $this->showMapModal = true;
    }

    public function closeMap()
    {
        $this->showMapModal = false;
    }

    public function render()
    {
        // 1. Ambil data koordinat paling terakhir untuk setiap Device
        $subQuery = SensorData::select('id_device', DB::raw('MAX(created_at) as max_created_at'))
            ->groupBy('id_device');

        $devices = SensorData::with('device')
            ->joinSub($subQuery, 'latest_records', function ($join) {
                $join->on('sensor_data.id_device', '=', 'latest_records.id_device')
                    ->on('sensor_data.created_at', '=', 'latest_records.max_created_at');
            })
            ->orderBy('sensor_data.id_device', 'asc')
            ->get();

        // 2. Ambil data log jika user sedang membuka detail riwayat device tertentu
        $deviceLogs = collect();
        if ($this->viewingLogs && $this->selectedDevice) {
            $paginatedLogs = SensorData::with('device')
                ->where('id_device', $this->selectedDevice)
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            $paginatedLogs->getCollection()->transform(function ($currentLog) {
                $previousLog = SensorData::where('id_device', $currentLog->id_device)
                    ->where('created_at', '<', $currentLog->created_at)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($previousLog) {
                    $currentLog->distance_moved = $this->calculateHaversineDistance(
                        $currentLog->latitude,
                        $currentLog->longitude,
                        $previousLog->latitude,
                        $previousLog->longitude
                    );
                } else {
                    $currentLog->distance_moved = 0;
                }

                return $currentLog;
            });

            $deviceLogs = $paginatedLogs;
        }

        return view('livewire.sensor-dashboard', [
            'viewingLogs' => $this->viewingLogs,
            'selectedDevice' => $this->selectedDevice,
            'devices' => $devices,
            'deviceLogs' => $deviceLogs
        ]);
    }
}
