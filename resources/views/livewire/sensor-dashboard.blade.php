<div wire:poll.5s class="p-6 max-w-6xl mx-auto space-y-6">

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
                            <th class="px-6 py-3">Latitude</th>
                            <th class="px-6 py-3">Longitude</th>
                            <th class="px-6 py-3">Terakhir Diperbarui</th>
                            <th class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm text-gray-600">
                        @forelse($devices as $device)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-mono font-bold text-gray-900">
                                    {{ $device->id_device }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="inline-block px-3 py-1 rounded-full font-black text-sm {{ $device->suhu > 0 ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ $device->suhu }} °C
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs">{{ $device->latitude }}</td>
                                <td class="px-6 py-4 font-mono text-xs">{{ $device->longitude }}</td>
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
                                <td colspan="6"
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
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200 animate-fade-in">
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
                                <td class="px-6 py-4 font-mono text-xs">
                                    {{ $log->latitude }}, {{ $log->longitude }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-gray-400">
                                    Tidak ada data log tersimpan untuk unit ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
