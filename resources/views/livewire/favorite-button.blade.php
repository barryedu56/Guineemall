@php
    $toggleUrl = route('client.favorites.toggle', $product->id);
@endphp

@if($compact ?? false)
    <button type="button"
            x-data="{ isFavorite: @js($isFavorite), loading: false }"
            @click="loading = true; fetch('{{ $toggleUrl }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } }).then(r => r.json()).then(data => { if (data && data.success) { isFavorite = data.is_favorite; } }).catch(() => {}).finally(() => { loading = false; })"
            :class="loading ? 'opacity-70 cursor-wait' : ''"
            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition"
            :title="isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'">
        <svg class="w-5 h-5" :class="isFavorite ? 'text-red-500' : 'text-gray-500'" :fill="isFavorite ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
        </svg>
    </button>
@else
    <button type="button"
            x-data="{ isFavorite: @js($isFavorite), loading: false }"
            @click="loading = true; fetch('{{ $toggleUrl }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } }).then(r => r.json()).then(data => { if (data && data.success) { isFavorite = data.is_favorite; } }).catch(() => {}).finally(() => { loading = false; })"
            :class="loading ? 'opacity-70 cursor-wait' : ''"
            class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
        <svg class="w-4 h-4 inline mr-2" :class="isFavorite ? 'text-red-500' : 'text-gray-600'" :fill="isFavorite ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
        </svg>
        <span x-text="isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'"></span>
    </button>
@endif
