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

    // Inline edit device
    public $editingDeviceId = null;
    public $showEditNameModal = false;
    public $inputNamaDevice = '';

    // Fitur Search
    public $search = '';

    public function updatingViewingLogs()
    {
        $this->resetPage();
    }

    // Reset pagination ketika query pencarian berubah
    public function updatingSearch()
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

    /*
    |--------------------------------------------------------------------------
    | EDIT DEVICE NAME
    |--------------------------------------------------------------------------
    */

    public function startEditName($idDevice, $currentName = '')
    {
        $this->editingDeviceId = $idDevice;
        $this->inputNamaDevice = $currentName ?? '';
        $this->showEditNameModal = true; // Aktifkan modal saat tombol diklik
    }

    public function saveDeviceName()
    {
        if (!$this->editingDeviceId) {
            return;
        }

        $namaBaru = trim($this->inputNamaDevice) == '' ? null : trim($this->inputNamaDevice);

        Device::updateOrCreate(
            ['id_device' => $this->editingDeviceId],
            ['nama_device' => $namaBaru]
        );

        // Reset state dan tutup modal
        $this->showEditNameModal = false;
        $this->editingDeviceId = null;
        $this->inputNamaDevice = '';

        session()->flash('message', 'Nama perangkat berhasil diperbarui');
    }

    public function cancelEdit()
    {
        $this->showEditNameModal = false;
        $this->editingDeviceId = null;
        $this->inputNamaDevice = '';
    }

    /*
    |--------------------------------------------------------------------------
    | HAVERSINE
    |--------------------------------------------------------------------------
    */

    private function calculateHaversineDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo)
    {
        if (is_null($latitudeFrom) || is_null($longitudeFrom) || is_null($latitudeTo) || is_null($longitudeTo)) {
            return 0;
        }

        $earthRadius = 6371000;

        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }

    /*
    |--------------------------------------------------------------------------
    | MEDIAN HELPER
    |--------------------------------------------------------------------------
    */

    private function median(array $values)
    {
        sort($values);
        $count = count($values);

        if ($count == 0) {
            return 0;
        }

        $middle = floor($count / 2);

        if ($count % 2 == 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }

        return $values[$middle];
    }

    /*
    |--------------------------------------------------------------------------
    | HITUNG TITIK PUSAT
    |--------------------------------------------------------------------------
    */

    private function calculateCenter($logs)
    {
        return [
            'lat' => $this->median($logs->pluck('latitude')->toArray()),
            'lng' => $this->median($logs->pluck('longitude')->toArray())
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ANALISA PERGERAKAN DEVICE
    |
    | Menggunakan 10 log terakhir
    |--------------------------------------------------------------------------
    */

    private function analyzeMovement($deviceId)
    {
        $logs = SensorData::where('id_device', $deviceId)
            ->latest()
            ->take(20)
            ->get()
            ->reverse()
            ->values();

        if ($logs->count() < 20) {
            return 0;
        }

        // 10 data lama dan 10 data terbaru
        $oldCluster = $logs->slice(0, 10)->values();
        $newCluster = $logs->slice(10, 10)->values();

        // Hitung titik tengah masing-masing cluster
        $oldCenter = $this->calculateCenter($oldCluster);
        $newCenter = $this->calculateCenter($newCluster);

        // Jarak perpindahan pusat cluster
        $centerMovement = $this->calculateHaversineDistance(
            $oldCenter['lat'],
            $oldCenter['lng'],
            $newCenter['lat'],
            $newCenter['lng']
        );

        // Hitung penyebaran titik pada cluster terbaru
        $spreads = [];

        foreach ($newCluster as $point) {
            $spreads[] = $this->calculateHaversineDistance(
                $newCenter['lat'],
                $newCenter['lng'],
                $point->latitude,
                $point->longitude
            );
        }

        $medianSpread = $this->median($spreads);

        // Buang titik GPS yang terlalu jauh dari pola mayoritas
        $validSpreads = [];

        foreach ($spreads as $spread) {
            if ($spread <= ($medianSpread + 20)) {
                $validSpreads[] = $spread;
            }
        }

        // Data terlalu sedikit untuk keputusan
        if (count($validSpreads) < 5) {
            return 0;
        }

        $clusterSpread = max($validSpreads);


        /*
        |--------------------------------------------------------------------------
        | Analisa Status
        |--------------------------------------------------------------------------
        */


        // Kemungkinan GPS drift:
        // pusat tidak berpindah tetapi ada titik menyebar
        if ($centerMovement < 20 && $clusterSpread > 35) {
            return 0;
        }


        // Posisi tetap:
        // pusat stabil dan sebaran titik kecil
        if ($centerMovement < 15 && $clusterSpread < 20) {
            return 0;
        }


        // Perpindahan valid
        return round($centerMovement, 1);
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        /*
        |--------------------------------------------------------------------------
        | Ambil data terbaru setiap device
        |--------------------------------------------------------------------------
        */
        $subQuery = SensorData::select('id_device', DB::raw('MAX(created_at) as max_created_at'))
            ->groupBy('id_device');

        $devicesQuery = SensorData::with('device')
            ->joinSub($subQuery, 'latest_records', function ($join) {
                $join->on('sensor_data.id_device', '=', 'latest_records.id_device')
                    ->on('sensor_data.created_at', '=', 'latest_records.max_created_at');
            });

        // Filter pencarian berdasarkan ID Device atau Nama Device jika query diisi
        if (!empty(trim($this->search))) {
            $searchTerm = '%' . trim($this->search) . '%';
            $devicesQuery->where(function ($q) use ($searchTerm) {
                $q->where('sensor_data.id_device', 'like', $searchTerm)
                    ->orWhereHas('device', function ($subQ) use ($searchTerm) {
                        $subQ->where('nama_device', 'like', $searchTerm);
                    });
            });
        }

        $devices = $devicesQuery->orderBy('sensor_data.id_device', 'asc')->get();

        /*
        |--------------------------------------------------------------------------
        | Status movement
        |--------------------------------------------------------------------------
        */
        foreach ($devices as $deviceLatest) {
            $deviceLatest->distance_moved = $this->analyzeMovement($deviceLatest->id_device);
        }

        /*
        |--------------------------------------------------------------------------
        | HISTORY LOG
        |--------------------------------------------------------------------------
        */
        $deviceLogs = collect();

        if ($this->viewingLogs && $this->selectedDevice) {
            $paginatedLogs = SensorData::with('device')
                ->where('id_device', $this->selectedDevice)
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            $items = $paginatedLogs->items();

            foreach ($items as $index => $currentLog) {
                $previousLog = null;

                // 1. Cari previousLog dari collection $items terlebih dahulu (bukan 0,0)
                $searchIndex = $index + 1;
                while (isset($items[$searchIndex])) {
                    $tempLog = $items[$searchIndex];
                    // Jika koordinat valid (bukan 0,0), gunakan ini sebagai previousLog
                    if ($tempLog->latitude != 0 || $tempLog->longitude != 0) {
                        $previousLog = $tempLog;
                        break;
                    }
                    $searchIndex++; // Naikkan index untuk mencari data di belakangnya lagi jika masih 0,0
                }

                // 2. Jika tidak ditemukan di collection $items, cari langsung ke Database (bukan 0,0)
                if (!$previousLog) {
                    $previousLog = SensorData::where('id_device', $currentLog->id_device)
                        ->where('created_at', '<', $currentLog->created_at)
                        // Tambahkan kondisi agar database mengabaikan koordinat 0,0
                        ->where(function ($query) {
                            $query->where('latitude', '!=', 0)
                                ->orWhere('longitude', '!=', 0);
                        })
                        ->orderBy('created_at', 'desc')
                        ->first();
                }

                // 3. Kalkulasi Jarak Haversine
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
}