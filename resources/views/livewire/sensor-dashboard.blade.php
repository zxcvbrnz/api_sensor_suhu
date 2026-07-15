<div class="p-6 max-w-6xl mx-auto space-y-6 relative">

    {{-- HEADER --}}
    <div class="flex justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                Dashboard Monitoring
            </h1>
            <p class="text-sm text-gray-500">
                Analisis posisi berdasarkan 10 titik GPS terakhir
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <span class="relative flex h-3 w-3">
                <span
                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
            </span>
            <span class="text-xs text-gray-600 font-medium">
                Live Connected
            </span>
        </div>
    </div>

    {{-- FLASH MESSAGE --}}
    @if (session()->has('message'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    {{-- LEGEND STATUS --}}
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex flex-wrap gap-4 text-xs">
        <span class="font-semibold text-gray-700 w-full sm:w-auto">
            Status Pergerakan
            <br>
            <span class="text-[10px] text-gray-400">
                (Berdasarkan sebaran 10 titik GPS terakhir)
            </span>
        </span>

        <div
            class="flex items-center space-x-2 bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-full border border-emerald-100">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            <span>Posisi Stabil (0 - 19m)</span>
        </div>

        <div
            class="flex items-center space-x-2 bg-amber-50 text-amber-700 px-3 py-1.5 rounded-full border border-amber-200">
            <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
            <span>Kemungkinan Berpindah (20 - 99m)</span>
        </div>

        <div
            class="flex items-center space-x-2 bg-rose-50 text-rose-700 px-3 py-1.5 rounded-full border border-rose-200">
            <span class="h-2 w-2 rounded-full bg-rose-500 animate-pulse"></span>
            <span>Pindah Lokasi (≥100m)</span>
        </div>
    </div>

    @if (!$viewingLogs)

        {{-- DEVICE LIST --}}
        <div wire:poll.5s class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
            <div class="p-4 bg-gray-50 border-b">
                <h3 class="font-semibold text-gray-700">
                    Daftar Status Unit Aktif
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100 text-xs uppercase text-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left">ID Device / Nama</th>
                            <th class="px-6 py-3 text-center">Suhu</th>
                            <th class="px-6 py-3 text-left">Lokasi</th>
                            <th class="px-6 py-3 text-left">Status Pergerakan</th>
                            <th class="px-6 py-3 text-left">Update</th>
                            <th class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 text-sm">
                        @forelse($devices as $device)
                            <tr wire:key="device-{{ $device->id_device }}" class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div>
                                        <span class="block text-xs text-gray-400 font-mono">
                                            ID: {{ $device->id_device }}
                                        </span>

                                        @if ($device->device && $device->device->nama_device)
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-gray-800">
                                                    {{ $device->device->nama_device }}
                                                </span>
                                                <button
                                                    wire:click="startEditName('{{ $device->id_device }}', '{{ $device->device->nama_device }}')"
                                                    class="text-xs text-indigo-500 hover:text-indigo-700 underline">
                                                    Edit
                                                </button>
                                            </div>
                                        @else
                                            <button wire:click="startEditName('{{ $device->id_device }}', '')"
                                                class="text-xs px-2 py-1 border border-dashed border-indigo-300 text-indigo-600 rounded hover:bg-indigo-50">
                                                + Beri Nama
                                            </button>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="px-3 py-1 rounded-full font-bold text-sm {{ $device->suhu > 35 ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ $device->suhu }} °C
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="font-mono text-xs text-gray-500">
                                        Lat: {{ $device->latitude }} <br>
                                        Lng: {{ $device->longitude }}
                                    </div>
                                    <button
                                        wire:click="openMap({{ $device->latitude }}, {{ $device->longitude }}, '{{ $device->id_device }}')"
                                        class="mt-2 px-2 py-1 bg-emerald-50 text-emerald-600 border border-emerald-200 rounded text-xs hover:bg-emerald-100 transition">
                                        🗺 Map
                                    </button>
                                </td>

                                <td class="px-6 py-4">
                                    @if ($device->distance_moved >= 100)
                                        <span title="Pergeseran pusat 10 titik GPS terakhir"
                                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            <span class="h-2 w-2 bg-rose-500 rounded-full animate-pulse"></span>
                                            Pindah Lokasi ({{ round($device->distance_moved) }} m)
                                        </span>
                                    @elseif($device->distance_moved >= 20)
                                        <span title="Perubahan cluster posisi terdeteksi"
                                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="h-2 w-2 bg-amber-500 rounded-full animate-pulse"></span>
                                            Kemungkinan Pindah ({{ round($device->distance_moved) }} m)
                                        </span>
                                    @else
                                        <span title="10 titik terakhir masih berada pada area yang sama"
                                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="h-2 w-2 bg-emerald-500 rounded-full"></span>
                                            Posisi Stabil
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-xs text-gray-500">
                                    {{ $device->created_at->diffForHumans() }}
                                    <span class="block text-[10px] text-gray-400">
                                        {{ $device->created_at->format('d M Y H:i:s') }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <button wire:click="showLogs('{{ $device->id_device }}')"
                                        class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-semibold transition">
                                        History Log
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-10 text-gray-400">
                                    Belum ada data perangkat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        {{-- HISTORY LOG --}}
        <div class="bg-white rounded-xl shadow-md overflow-hidden border">
            <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
                <div>
                    <h3 class="font-semibold text-gray-700">
                        Riwayat Device:
                        <span class="font-mono text-indigo-600">
                            {{ $selectedDevice }}
                        </span>
                    </h3>
                    <p class="text-xs text-gray-400">
                        Perhitungan jarak antar rekaman GPS
                    </p>
                </div>

                <button wire:click="closeLogs"
                    class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 rounded text-xs transition">
                    ← Kembali
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100 text-xs uppercase text-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left">Waktu</th>
                            <th class="px-6 py-3 text-center">Suhu</th>
                            <th class="px-6 py-3 text-left">Koordinat / Pergerakan</th>
                            <th class="px-6 py-3 text-center">Maps</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 text-sm">
                        @forelse($deviceLogs as $log)
                            <tr wire:key="log-{{ $log->id }}" class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-xs">
                                    {{ $log->created_at->format('d-m-Y H:i:s') }}
                                    <br>
                                    <span class="text-gray-400">
                                        {{ $log->created_at->diffForHumans() }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="px-2 py-1 rounded text-xs font-bold {{ $log->suhu > 35 ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600' }}">
                                        {{ $log->suhu }} °C
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="space-y-2">
                                        <div class="font-mono text-xs bg-gray-100 rounded px-2 py-1 inline-block">
                                            {{ $log->latitude }}, {{ $log->longitude }}
                                        </div>

                                        @if (isset($log->distance_moved))
                                            <div>
                                                @if ($log->distance_moved >= 100)
                                                    <span
                                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-100">
                                                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                                        Pindah Lokasi ({{ round($log->distance_moved) }}m)
                                                    </span>
                                                @elseif($log->distance_moved >= 20)
                                                    <span
                                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-100">
                                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                                        Pindah Posisi ({{ round($log->distance_moved) }}m)
                                                    </span>
                                                @else
                                                    <span
                                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                        Tetap
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <button
                                        wire:click="openMap({{ $log->latitude }}, {{ $log->longitude }}, '{{ $log->id_device }}')"
                                        class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs transition">
                                        Lihat Titik
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-10 text-gray-400">
                                    Tidak ada history.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($deviceLogs->hasPages())
                <div class="p-4 bg-gray-50 border-t">
                    {{ $deviceLogs->links() }}
                </div>
            @endif
        </div>

    @endif

    {{-- =========================
         MODAL EDIT NAMA (DITAMBAHKAN)
         ========================= --}}
    @if (isset($showEditNameModal) && $showEditNameModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
                    <h3 class="font-bold text-gray-800">Edit Nama Perangkat</h3>
                    <button wire:click="$set('showEditNameModal', false)"
                        class="text-gray-400 hover:text-gray-600 font-bold">✕</button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">ID Device</label>
                        <input type="text" value="{{ $editingDeviceId }}"
                            class="w-full bg-gray-100 border border-gray-200 rounded px-3 py-2 text-sm text-gray-600 font-mono"
                            readonly>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Nama Perangkat</label>
                        <input type="text" wire:model.defer="newDeviceName"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
                <div class="p-3 bg-gray-50 flex justify-end gap-2 border-t">
                    <button wire:click="$set('showEditNameModal', false)"
                        class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded text-xs transition">Batal</button>
                    <button wire:click="saveDeviceName"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-semibold transition">Simpan</button>
                </div>
            </div>
        </div>
    @endif

    {{-- =========================
         MODAL MAP (DIPERBAIKI)
         ========================= --}}
    @if ($showMapModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-3xl overflow-hidden">
                <div class="p-4 bg-gray-50 border-b flex justify-between">
                    <div>
                        <h3 class="font-bold">Lokasi Perangkat</h3>
                        <p class="text-xs text-gray-400 font-mono">
                            {{ $mapLatitude }}, {{ $mapLongitude }}
                        </p>
                    </div>
                    <button wire:click="closeMap" class="text-gray-500 hover:text-gray-700 font-bold">
                        ✕
                    </button>
                </div>

                <div class="h-96">
                    {{-- DI-FIX: Menggunakan URL Embed Google Maps yang Valid --}}
                    <iframe width="100%" height="100%" frameborder="0" style="border:0;" allowfullscreen
                        src="https://maps.google.com/maps?q={{ $mapLatitude }},{{ $mapLongitude }}&hl=id&z=17&output=embed">
                    </iframe>
                </div>

                <div class="p-3 bg-gray-50 flex justify-end gap-2">
                    {{-- DI-FIX: Menggunakan URL Google Maps eksternal yang Valid --}}
                    <a target="_blank" href="https://www.google.com/maps?q={{ $mapLatitude }},{{ $mapLongitude }}"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold inline-flex items-center gap-1 transition">
                        Buka Google Maps
                    </a>
                    <button wire:click="closeMap"
                        class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded text-xs transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
