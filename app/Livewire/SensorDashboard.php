<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SensorData;
use App\Models\Device;
use Illuminate\Support\Facades\DB;

class SensorDashboard extends Component
{
    use WithPagination;

    public $selectedDevice = null;
    public $viewingLogs = false;
    public $showMapModal = false;
    public $mapLatitude = null;
    public $mapLongitude = null;
    public $mapDeviceTarget = null;

    // State Inline Editing
    public $editingDeviceId = null;
    public $inputNamaDevice = '';

    // Reset pagination ketika beralih ke halaman log atau menutupnya
    public function updatingViewingLogs()
    {
        $this->resetPage();
    }

    public function showLogs($idDevice)
    {
        $this->resetPage();
        $this->selectedDevice = $idDevice;
        $this->viewingLogs = true;
    }

    public function closeLogs()
    {
        $this->selectedDevice = null;
        $this->viewingLogs = false;
        $this->resetPage();
    }

    public function openMap($latitude, $longitude, $idDevice)
    {
        $this->mapLatitude = $latitude;
        $this->mapLongitude = $longitude;
        $this->mapDeviceTarget = $idDevice;
        $this->showMapModal = true;
    }

    public function closeMap()
    {
        $this->showMapModal = false;
    }

    public function startEditName($idDevice, $currentName = '')
    {
        $this->editingDeviceId = $idDevice;
        $this->inputNamaDevice = $currentName ?? '';
    }

    public function saveDeviceName()
    {
        if (empty($this->editingDeviceId)) return;

        $namaBaru = trim($this->inputNamaDevice) === '' ? null : trim($this->inputNamaDevice);

        Device::updateOrCreate(
            ['id_device' => $this->editingDeviceId],
            ['nama_device' => $namaBaru]
        );

        $this->editingDeviceId = null;
        $this->inputNamaDevice = '';

        session()->flash('message', 'Nama perangkat berhasil diperbarui di tabel master!');
    }

    public function cancelEdit()
    {
        $this->editingDeviceId = null;
        $this->inputNamaDevice = '';
    }

    /**
     * Hitung Jarak dengan rumus Haversine (Hasil dalam satuan meter)
     */
    private function calculateHaversineDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo)
    {
        if (is_null($latitudeFrom) || is_null($longitudeFrom) || is_null($latitudeTo) || is_null($longitudeTo)) {
            return 0;
        }

        $earthRadius = 6371000; // Radius bumi dalam Meter

        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }

    public function render()
    {
        // 1. Ambil data log terakhir per device
        $subQuery = SensorData::select('id_device', DB::raw('MAX(created_at) as max_created_at'))
            ->groupBy('id_device');

        // 2. Join dengan subquery DAN muat relasi master 'device' secara efisien
        $devices = SensorData::with('device')
            ->joinSub($subQuery, 'latest_records', function ($join) {
                $join->on('sensor_data.id_device', '=', 'latest_records.id_device')
                    ->on('sensor_data.created_at', '=', 'latest_records.max_created_at');
            })
            ->orderBy('sensor_data.id_device', 'asc')
            ->get();

        foreach ($devices as $deviceLatest) {

            $deviceLatest->distance_moved = $this->analyzeMovement(
                $deviceLatest->id_device
            );
        }

        $deviceLogs = collect();
        if ($this->viewingLogs && $this->selectedDevice) {
            $paginatedLogs = SensorData::with('device')
                ->where('id_device', $this->selectedDevice)
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            $items = $paginatedLogs->items();

            foreach ($items as $index => $currentLog) {
                // Sesuai permintaan: History log TETAP berdasarkan 1 data sebelumnya saja
                if (isset($items[$index + 1])) {
                    $previousLog = $items[$index + 1];
                } else {
                    $previousLog = SensorData::where('id_device', $currentLog->id_device)
                        ->where('created_at', '<', $currentLog->created_at)
                        ->orderBy('created_at', 'desc')
                        ->first();
                }

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
            }

            $deviceLogs = $paginatedLogs;
        }

        return view('livewire.sensor-dashboard', [
            'devices' => $devices,
            'deviceLogs' => $deviceLogs
        ]);
    }

    private function analyzeMovement($deviceId)
    {
        $logs = SensorData::where('id_device', $deviceId)
            ->latest()
            ->take(10)
            ->get()
            ->reverse()
            ->values();

        if ($logs->count() < 2) {
            return 0;
        }

        $segments = [];

        for ($i = 1; $i < $logs->count(); $i++) {

            $distance = $this->calculateHaversineDistance(
                $logs[$i - 1]->latitude,
                $logs[$i - 1]->longitude,
                $logs[$i]->latitude,
                $logs[$i]->longitude
            );

            // abaikan noise kecil
            if ($distance < 5) {
                continue;
            }

            $segments[] = $distance;
        }

        if (count($segments) == 0) {
            return 0;
        }

        /*
    |--------------------------------------------------------------------------
    | Median Filter
    |--------------------------------------------------------------------------
    */

        sort($segments);

        $median = $segments[(int) floor(count($segments) / 2)];

        /*
    |--------------------------------------------------------------------------
    | Hitung berapa data yang mendekati median
    |--------------------------------------------------------------------------
    */

        $valid = 0;
        $sum = 0;

        foreach ($segments as $distance) {

            if (abs($distance - $median) <= 15) {

                $valid++;

                $sum += $distance;
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Kalau cuma 1-2 data berarti kemungkinan GPS drift
    |--------------------------------------------------------------------------
    */

        if ($valid < 3) {
            return 0;
        }

        return $sum / $valid;
    }
}
