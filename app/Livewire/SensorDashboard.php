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

        // === RANGKUMAN STATUS PERGERAKAN DARI 10 DATA TERBARU UNTUK LIST UTAMA ===
        foreach ($devices as $deviceLatest) {
            // Ambil maksimal 10 data koordinat terbaru untuk device ini (diurutkan dari yang terlama ke terbaru untuk mempermudah tracking)
            $recentLogs = SensorData::where('id_device', $deviceLatest->id_device)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get()
                ->reverse(); // Balik urutan agar index kecil = data lebih lama, index besar = data terbaru

            if ($recentLogs->count() > 1) {
                $totalDistance = 0;
                $validIntervals = 0;
                $previousPoint = null;

                foreach ($recentLogs as $log) {
                    if (is_null($previousPoint)) {
                        $previousPoint = $log;
                        continue;
                    }

                    // Hitung jarak dari titik sebelumnya ke titik saat ini secara berurutan (Rantai)
                    $distance = $this->calculateHaversineDistance(
                        $previousPoint->latitude,
                        $previousPoint->longitude,
                        $log->latitude,
                        $log->longitude
                    );

                    // FILTER DRIFT: Abaikan lompatan micro jika jarak antar log sangat kecil (< 3 meter dianggap noise getaran GPS)
                    // Namun jika jarak sekali lompat > 25 meter secara instan dalam interval pendek, itu juga indikasi drift.
                    if ($distance > 3) {
                        $totalDistance += $distance;
                        $validIntervals++;
                    }

                    $previousPoint = $log;
                }

                // Jika total akumulasi pergerakan rantai dari 10 log terakhir sangat kecil, paksa ke 0 (Posisi Tetap)
                // Ambang batas akumulasi 10 data diset di angka 20-25 meter untuk menyaring total akumulasi drift saat diam
                if ($totalDistance < 25) {
                    $deviceLatest->distance_moved = 0;
                } else {
                    // Gunakan nilai jarak akumulatif rata-rata atau total pergeseran ujung ke ujung
                    // Di sini kita ambil titik paling awal dari 10 log dengan titik paling akhir untuk melihat perpindahan riil sejati
                    $firstPoint = $recentLogs->first();
                    $lastPoint = $recentLogs->last();

                    $realDisplacement = $this->calculateHaversineDistance(
                        $firstPoint->latitude,
                        $firstPoint->longitude,
                        $lastPoint->latitude,
                        $lastPoint->longitude
                    );

                    // Jika perpindahan bersih dari ujung ke ujung log di atas 20 meter, maka fix bergerak riil
                    $deviceLatest->distance_moved = $realDisplacement > 20 ? $realDisplacement : 0;
                }
            } else {
                $deviceLatest->distance_moved = 0;
            }
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
}
