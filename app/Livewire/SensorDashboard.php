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
    public $inputNamaDevice = '';


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



    /*
    |--------------------------------------------------------------------------
    | EDIT DEVICE NAME
    |--------------------------------------------------------------------------
    */


    public function startEditName($idDevice, $currentName = '')
    {
        $this->editingDeviceId = $idDevice;
        $this->inputNamaDevice = $currentName ?? '';
    }


    public function saveDeviceName()
    {
        if (!$this->editingDeviceId) {
            return;
        }


        $namaBaru = trim($this->inputNamaDevice) == ''
            ? null
            : trim($this->inputNamaDevice);



        Device::updateOrCreate(
            [
                'id_device' => $this->editingDeviceId
            ],
            [
                'nama_device' => $namaBaru
            ]
        );


        $this->editingDeviceId = null;
        $this->inputNamaDevice = '';


        session()->flash(
            'message',
            'Nama perangkat berhasil diperbarui'
        );
    }


    public function cancelEdit()
    {
        $this->editingDeviceId = null;
        $this->inputNamaDevice = '';
    }



    /*
    |--------------------------------------------------------------------------
    | HAVERSINE
    |--------------------------------------------------------------------------
    */


    private function calculateHaversineDistance(
        $latitudeFrom,
        $longitudeFrom,
        $latitudeTo,
        $longitudeTo
    ) {

        if (
            is_null($latitudeFrom) ||
            is_null($longitudeFrom) ||
            is_null($latitudeTo) ||
            is_null($longitudeTo)
        ) {
            return 0;
        }



        $earthRadius = 6371000;



        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);

        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);



        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;



        $angle =
            2 *
            asin(
                sqrt(
                    pow(sin($latDelta / 2), 2)
                        +
                        cos($latFrom)
                        *
                        cos($latTo)
                        *
                        pow(sin($lonDelta / 2), 2)
                )
            );



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

            return (
                $values[$middle - 1]
                +
                $values[$middle]
            ) / 2;
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

            'lat' => $this->median(
                $logs
                    ->pluck('latitude')
                    ->toArray()
            ),


            'lng' => $this->median(
                $logs
                    ->pluck('longitude')
                    ->toArray()
            )

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

        $logs = SensorData::where(
            'id_device',
            $deviceId
        )
            ->latest()
            ->take(10)
            ->get()
            ->reverse()
            ->values();



        // Belum cukup data
        if ($logs->count() < 10) {

            return 0;
        }




        /*
        |--------------------------------------------------------------------------
        | Pisahkan 5 data lama dan 5 data baru
        |--------------------------------------------------------------------------
        */


        $oldCluster = $logs
            ->slice(0, 5)
            ->values();



        $newCluster = $logs
            ->slice(5, 5)
            ->values();




        /*
        |--------------------------------------------------------------------------
        | Cari pusat masing-masing cluster
        |--------------------------------------------------------------------------
        */


        $oldCenter =
            $this->calculateCenter(
                $oldCluster
            );



        $newCenter =
            $this->calculateCenter(
                $newCluster
            );




        /*
        |--------------------------------------------------------------------------
        | Hitung perpindahan pusat
        |--------------------------------------------------------------------------
        */


        $centerMovement =
            $this->calculateHaversineDistance(

                $oldCenter['lat'],
                $oldCenter['lng'],

                $newCenter['lat'],
                $newCenter['lng']

            );





        /*
        |--------------------------------------------------------------------------
        | Hitung radius cluster baru
        |--------------------------------------------------------------------------
        */


        $radius = [];



        foreach ($newCluster as $point) {


            $radius[] =
                $this->calculateHaversineDistance(

                    $newCenter['lat'],
                    $newCenter['lng'],

                    $point->latitude,
                    $point->longitude

                );
        }





        $medianRadius =
            $this->median($radius);




        /*
        |--------------------------------------------------------------------------
        | Filter GPS Outlier
        |--------------------------------------------------------------------------
        */


        $validRadius = [];



        foreach ($radius as $r) {


            if ($r <= ($medianRadius + 15)) {

                $validRadius[] = $r;
            }
        }





        if (count($validRadius) < 3) {

            return 0;
        }





        $maxRadius =
            max($validRadius);





        /*
        |--------------------------------------------------------------------------
        | KEPUTUSAN
        |--------------------------------------------------------------------------
        */


        // GPS drift
        if (
            $centerMovement < 15
            &&
            $maxRadius > 25
        ) {

            return 0;
        }




        // Diam
        if (
            $centerMovement < 20
            &&
            $maxRadius < 20
        ) {

            return 0;
        }





        // Bergerak
        return round(
            $centerMovement,
            1
        );
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


        $subQuery =
            SensorData::select(
                'id_device',
                DB::raw(
                    'MAX(created_at) as max_created_at'
                )
            )
            ->groupBy('id_device');




        $devices =
            SensorData::with('device')

            ->joinSub(
                $subQuery,
                'latest_records',
                function ($join) {

                    $join
                        ->on(
                            'sensor_data.id_device',
                            '=',
                            'latest_records.id_device'
                        )

                        ->on(
                            'sensor_data.created_at',
                            '=',
                            'latest_records.max_created_at'
                        );
                }
            )

            ->orderBy(
                'sensor_data.id_device',
                'asc'
            )

            ->get();





        /*
        |--------------------------------------------------------------------------
        | Status movement
        |--------------------------------------------------------------------------
        */


        foreach ($devices as $deviceLatest) {


            $deviceLatest->distance_moved =
                $this->analyzeMovement(
                    $deviceLatest->id_device
                );
        }





        /*
        |--------------------------------------------------------------------------
        | HISTORY LOG
        |--------------------------------------------------------------------------
        */


        $deviceLogs = collect();



        if (
            $this->viewingLogs
            &&
            $this->selectedDevice
        ) {


            $paginatedLogs =
                SensorData::with('device')

                ->where(
                    'id_device',
                    $this->selectedDevice
                )

                ->orderBy(
                    'created_at',
                    'desc'
                )

                ->paginate(15);



            $items =
                $paginatedLogs->items();




            foreach ($items as $index => $currentLog) {


                if (isset($items[$index + 1])) {


                    $previousLog =
                        $items[$index + 1];
                } else {


                    $previousLog =
                        SensorData::where(
                            'id_device',
                            $currentLog->id_device
                        )

                        ->where(
                            'created_at',
                            '<',
                            $currentLog->created_at
                        )

                        ->orderBy(
                            'created_at',
                            'desc'
                        )

                        ->first();
                }




                if ($previousLog) {


                    $currentLog->distance_moved =
                        $this->calculateHaversineDistance(

                            $currentLog->latitude,
                            $currentLog->longitude,

                            $previousLog->latitude,
                            $previousLog->longitude

                        );
                } else {


                    $currentLog->distance_moved = 0;
                }
            }



            $deviceLogs =
                $paginatedLogs;
        }



        return view(
            'livewire.sensor-dashboard',
            [
                'devices' => $devices,
                'deviceLogs' => $deviceLogs
            ]
        );
    }
}
