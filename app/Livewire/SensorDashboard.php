<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SensorData;

class SensorDashboard extends Component
{
    public function render()
    {
        // Mengambil 1 data paling baru berdasarkan waktu masuk
        $latestData = SensorData::latest()->first();

        return view('livewire.sensor-dashboard', [
            'latestData' => $latestData
        ]);
    }
}