<div wire:poll.2s class="p-6 max-w-sm mx-auto bg-white rounded-xl shadow-md space-y-4">
    <div class="text-center">
        <h1 class="text-xl font-bold text-gray-800">Monitoring Kulkas</h1>
        <p class="text-sm text-gray-500">Live Update (Tiap 2 Detik)</p>
    </div>

    @if($latestData)
        <div class="p-4 rounded-lg {{ $latestData->suhu > 0 ? 'bg-red-100' : 'bg-blue-100' }}">
            <h2 class="text-3xl font-black text-center {{ $latestData->suhu > 0 ? 'text-red-600' : 'text-blue-600' }}">
                {{ $latestData->suhu }} °C
            </h2>
        </div>
        
        <div class="text-sm text-gray-700 space-y-1">
            <p><strong>Lat:</strong> {{ $latestData->latitude }}</p>
            <p><strong>Lng:</strong> {{ $latestData->longitude }}</p>
            <p class="text-xs text-gray-400 mt-2">
                Terakhir update: {{ $latestData->created_at->diffForHumans() }}
            </p>
        </div>
    @else
        <div class="text-center text-gray-500 p-4 border border-dashed border-gray-300 rounded">
            Menunggu data masuk dari alat...
        </div>
    @endif
</div>