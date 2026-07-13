<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination; // Tambahkan ini untuk pagination async
use App\Models\SensorData;
use App\Models\Device;
use Illuminate\Support\Facades\DB;

class SensorDashboard extends Component
{
    use WithPagination; // Gunakan trait pagination

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
        $this->resetPage(); // Reset page ke 1 saat buka log baru
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

        $deviceLogs = collect();
        if ($this->viewingLogs && $this->selectedDevice) {
            // Eager load relasi 'device' juga di riwayat log agar bisa memunculkan nama
            $deviceLogs = SensorData::with('device')
                ->where('id_device', $this->selectedDevice)
                ->orderBy('created_at', 'desc')
                ->paginate(15); // Menggunakan pagination, isi 15 data per halaman
        }

        return view('livewire.sensor-dashboard', [
            'devices' => $devices,
            'deviceLogs' => $deviceLogs
        ]);
    }
}