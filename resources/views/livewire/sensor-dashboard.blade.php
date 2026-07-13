<div wire:poll.5s class="p-6 max-w-6xl mx-auto space-y-6 relative">

    <div class="flex justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Dashboard Monitoring Kulkas</h1>
            <p class="text-sm text-gray-500">Pembaruan otomatis data lapangan secara berkala</p>
        </div>
        <div class="flex items-center space-x-2">
            <span class="relative flex h-3 w-3">
                <span
                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
            </span>
            <span class="text-xs text-gray-600 font-medium">Live Connected</span>
        </div>
    </div>

    @if (!$viewingLogs)
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
            <div class="p-4 bg-gray-50 border-b border-gray-200">
                <h3 class="font-semibold text-gray-700">Daftar Status Unit Kulkas Aktif</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left">
                    <thead class="bg-gray-100 text-xs text-gray-700 uppercase font-semibold">
                        <tr>
                            <th class="px-6 py-3">ID Device</th>
                            <th class="px-6 py-3 text-center">Suhu Terakhir</th>
                            <th class="px-6 py-3">Koordinat Lokasi</th>
                            <th class="px-6 py-3">Terakhir Diperbarui</th>
                            <th class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm text-gray-600">
                        @forelse($devices as $device)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    @if ($editingDeviceId === $device->id_device)
                                        <div class="flex items-center space-x-1"
                                            wire:key="edit-{{ $device->id_device }}">
                                            <input type="text" wire:model="inputNamaDevice"
                                                class="px-2 py-1 text-xs border border-indigo-400 rounded focus:outline-none focus:ring-1 focus:ring-indigo-500 w-40"
                                                placeholder="Nama Kulkas (cth: Kulkas Sayur)">
                                            <button wire:click="saveDeviceName"
                                                class="p-1 px-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-semibold">
                                                Simpan
                                            </button>
                                            <button wire:click="cancelEdit"
                                                class="p-1 px-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded text-xs">
                                                Batal
                                            </button>
                                        </div>
                                    @else
                                        <div class="space-y-0.5">
                                            <span class="block font-mono text-xs font-bold text-gray-400">ID:
                                                {{ $device->id_device }}</span>
                                            @if ($device->device && $device->device->nama_device)
                                                <div class="flex items-center space-x-2">
                                                    <span
                                                        class="text-gray-900 font-bold text-sm">{{ $device->device->nama_device }}</span>
                                                    <button
                                                        wire:click="startEditName('{{ $device->id_device }}', '{{ $device->device->nama_device }}')"
                                                        class="text-xs text-gray-400 hover:text-indigo-600 underline">
                                                        Edit
                                                    </button>
                                                </div>
                                            @else
                                                <button wire:click="startEditName('{{ $device->id_device }}', '')"
                                                    class="inline-flex items-center px-2 py-0.5 border border-dashed border-indigo-300 text-indigo-600 hover:bg-indigo-50 text-[11px] font-medium rounded transition">
                                                    ➕ Beri Nama
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="inline-block px-3 py-1 rounded-full font-black text-sm {{ $device->suhu > 0 ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ $device->suhu }} °C
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <div class="font-mono text-xs">
                                            <span class="block text-gray-400">Lat: {{ $device->latitude }}</span>
                                            <span class="block text-gray-400">Lng: {{ $device->longitude }}</span>
                                        </div>
                                        <button
                                            wire:click="openMap({{ $device->latitude }}, {{ $device->longitude }}, '{{ $device->id_device }}')"
                                            class="p-1 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded border border-emerald-200 transition text-xs font-semibold px-2">
                                            🗺️ Map
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500">
                                    {{ $device->created_at->diffForHumans() }}
                                    <span
                                        class="block text-[10px] text-gray-400">({{ $device->created_at->format('d M Y H:i:s') }})</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button wire:click="showLogs('{{ $device->id_device }}')"
                                        class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded shadow-sm transition">
                                        History Log
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5"
                                    class="px-6 py-10 text-center text-gray-400 border border-dashed border-gray-200">
                                    Belum ada data unit IoT yang terdaftar di sistem.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
            <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <h3 class="font-semibold text-gray-700">Riwayat Aktivitas: <span
                            class="font-mono font-bold text-indigo-600">{{ $selectedDevice }}</span></h3>
                    <p class="text-xs text-gray-400">Menampilkan hingga 15 data pemantauan log terakhir</p>
                </div>
                <button wire:click="closeLogs"
                    class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold rounded transition">
                    ← Kembali ke Daftar
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left">
                    <thead class="bg-gray-100 text-xs text-gray-700 uppercase font-semibold">
                        <tr>
                            <th class="px-6 py-3">Waktu Data Diterima</th>
                            <th class="px-6 py-3 text-center">Nilai Suhu</th>
                            <th class="px-6 py-3">Koordinat GPS (Lat, Lng)</th>
                            <th class="px-6 py-3 text-center">Peta</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm text-gray-600">
                        @forelse($deviceLogs as $log)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-xs font-semibold text-gray-700">
                                    {{ $log->created_at->format('d-m-Y | H:i:s') }}
                                    <span
                                        class="text-gray-400 font-normal ml-2">({{ $log->created_at->diffForHumans() }})</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="inline-block px-2 py-0.5 rounded font-bold text-xs {{ $log->suhu > 0 ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600' }}">
                                        {{ $log->suhu }} °C
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-gray-500">
                                    {{ $log->latitude }}, {{ $log->longitude }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button
                                        wire:click="openMap({{ $log->latitude }}, {{ $log->longitude }}, '{{ $log->id_device }}')"
                                        class="inline-flex items-center px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium rounded transition">
                                        Lihat Titik
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                    Tidak ada data log tersimpan untuk unit ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($showMapModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50 overflow-y-auto animate-fade-in">
            <div
                class="bg-white rounded-xl shadow-2xl max-w-2xl w-full overflow-hidden border border-gray-100 transform transition-all">

                <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-800 text-base">Lokasi Geografis Perangkat</h3>
                        <p class="text-xs text-gray-400 font-mono">Device Target: {{ $mapDeviceTarget }}
                            ({{ $mapLatitude }}, {{ $mapLongitude }})</p>
                    </div>
                    <button wire:click="closeMap"
                        class="text-gray-400 hover:text-gray-600 font-bold text-lg p-1 px-2 rounded hover:bg-gray-200 transition">
                        ✕
                    </button>
                </div>

                <div class="w-full h-96 bg-gray-100">
                    <iframe width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0"
                        marginwidth="0"
                        src="https://maps.google.com/maps?q={{ $mapLatitude }},{{ $mapLongitude }}&hl=id&z=15&output=embed">
                    </iframe>
                </div>

                <div class="p-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $mapLatitude }},{{ $mapLongitude }}"
                        target="_blank"
                        class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded shadow transition">
                        Buka di Google Maps Apps ↗
                    </a>
                    <button wire:click="closeMap"
                        class="px-4 py-1.5 bg-gray-300 hover:bg-gray-400 text-gray-700 text-xs font-semibold rounded transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
