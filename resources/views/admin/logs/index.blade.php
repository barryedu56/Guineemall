@extends('admin.layouts.app')

@section('title', 'Logs Administrateur')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Logs Administrateur</h1>
                <p class="text-gray-600 mt-1">Historique des actions administratives sur la plateforme</p>
            </div>
            <div class="flex items-center space-x-2 text-sm">
                <div class="bg-yellow-50 text-yellow-700 px-3 py-1 rounded-full font-medium">
                    Total: {{ $logs->total() }} actions
                </div>
                <div class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full font-medium">
                    Aujourd'hui: {{ $logs->where('created_at', '>=', now()->startOfDay())->count() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <form method="GET" id="filterForm" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">Filtrer par admin</label>
                <select name="admin_id" id="adminFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 bg-white">
                    <option value="">Tous les admins</option>
                    @foreach($admins ?? [] as $admin)
                        <option value="{{ $admin->id }}" {{ request('admin_id') == $admin->id ? 'selected' : '' }}>
                            {{ $admin->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="flex-1 min-w-64">
                <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher une action</label>
                <input type="text" name="action" id="actionFilter" value="{{ request('action') }}" 
                       placeholder="Ex: création, suppression, blocage..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
            </div>
            
            <div class="flex gap-2">
                <button type="submit" onclick="console.log('Bouton filtrer cliqué')" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors flex items-center">
                    <i class="fas fa-search mr-2"></i>Filtrer
                </button>
                <button type="button" id="resetBtn" onclick="resetFilters()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors flex items-center">
                    <i class="fas fa-times mr-2"></i>Réinitialiser
                </button>
            </div>
        </form>
    </div>

    <!-- Logs -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @if($logs->isEmpty())
            <div class="p-12 text-center">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-clipboard-list text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">Aucun log trouvé</h3>
                <p class="text-gray-500 max-w-md mx-auto">
                    @if(request()->hasAny(['admin_id', 'action']))
                        Essayez de modifier les filtres pour voir plus de résultats.
                    @else
                        Les actions administratives apparaîtront ici automatiquement dès qu'elles seront effectuées.
                    @endif
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Il y a</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($logs as $log)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center mr-3">
                                            <i class="fas fa-user-shield text-white text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900">{{ optional($log->admin)->name ?? 'Admin inconnu' }}</div>
                                            <div class="text-xs text-gray-500">{{ optional($log->admin)->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="max-w-lg">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium mb-2
                                            {{ \App\Http\Controllers\Admin\AdminLogController::getActionBadgeClass($log->action) }}">
                                            {{ \App\Http\Controllers\Admin\AdminLogController::getActionType($log->action) }}
                                        </span>
                                        <div class="text-sm text-gray-900 break-words">{{ formatActionDescription($log->action) }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $log->created_at->format('d/m/Y H:i') }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-500">{{ $log->created_at->diffForHumans() }}</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($logs->hasPages())
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    {{ $logs->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection

@php
    function formatActionDescription($action) {
        // Nettoyer et formater la description pour l'affichage
        $action = html_entity_decode($action, ENT_QUOTES, 'UTF-8');
        
        // Si c'est du JSON, extraire juste l'essentiel
        if (str_contains($action, '{"id":')) {
            // Remplacer les JSON complets par des descriptions simples
            $action = preg_replace('/#\{[^}]+\}/', '#[détails]', $action);
        }
        
        // Limiter la longueur pour l'affichage
        if (strlen($action) > 200) {
            $action = mb_substr($action, 0, 200, 'UTF-8') . '...';
        }
        
        return $action;
    }
@endphp

@section('scripts')
<script>
// Fonction simple pour réinitialiser les filtres
function resetFilters() {
    console.log('resetFilters appelé');
    window.location.href = '{{ route("admin.logs.index") }}';
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('JavaScript chargé pour les filtres');
    
    const filterForm = document.getElementById('filterForm');
    const resetBtn = document.getElementById('resetBtn');
    const adminFilter = document.getElementById('adminFilter');
    const actionFilter = document.getElementById('actionFilter');
    
    console.log('Éléments trouvés:', {
        filterForm: !!filterForm,
        resetBtn: !!resetBtn,
        adminFilter: !!adminFilter,
        actionFilter: !!actionFilter
    });
    
    // Gérer le bouton de réinitialisation (backup)
    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            console.log('Bouton réinitialiser cliqué via addEventListener');
            e.preventDefault();
            window.location.href = '{{ route("admin.logs.index") }}';
        });
    }
    
    // Filtrage automatique lors du changement de sélection
    if (adminFilter) {
        adminFilter.addEventListener('change', function() {
            console.log('Admin filter changed:', this.value);
            if (filterForm) {
                filterForm.submit();
            }
        });
    }
});
</script>
@endsection
