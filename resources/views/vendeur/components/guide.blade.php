<!-- MINI GUIDE VENDEUR INTÉGRÉ -->
@php
    $progression = App\Services\Vendeur\ProgressionService::getProgression(auth()->user());
@endphp

<div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-2xl p-6 border border-yellow-200 mb-8">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-yellow-100 rounded-xl flex items-center justify-center mr-3">
                <i class="fas fa-compass text-yellow-600 text-lg"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900">Guide vendeur</h3>
                <p class="text-sm text-gray-600">Votre progression vers le succès</p>
            </div>
        </div>
        <div class="text-sm text-yellow-600 font-medium">
            Étape {{ $progression['current_step'] }} / {{ $progression['total_steps'] }}
        </div>
    </div>
    
    <!-- Barre de progression -->
    <div class="mb-6">
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="bg-gradient-to-r from-yellow-500 to-orange-500 h-3 rounded-full transition-all duration-500" 
                 style="width: {{ $progression['percentage'] }}%"></div>
        </div>
        <div class="mt-2 text-xs text-gray-600 text-center">
            {{ $progression['completed_steps'] }} étape(s) complétée(s) • {{ number_format($progression['percentage'], 0) }}%
        </div>
    </div>
    
    <!-- Étapes -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        @foreach($progression['steps'] as $step)
            <div class="text-center">
                <div class="w-12 h-12 
                    @if($step['status'] === 'completed') bg-yellow-100 border-yellow-300
                    @elseif($step['status'] === 'current') bg-orange-100 border-orange-300
                    @else bg-gray-100 border-gray-300 @endif 
                    rounded-full flex items-center justify-center mx-auto mb-2 border-2 transition-all duration-300">
                    @if($step['status'] === 'completed')
                        <i class="fas fa-check text-yellow-600"></i>
                    @elseif($step['status'] === 'current')
                        <span class="text-orange-600 font-bold">{{ $step['id'] }}</span>
                    @else
                        <span class="text-gray-400 font-bold">{{ $step['id'] }}</span>
                    @endif
                </div>
                <p class="text-xs font-medium 
                    @if($step['status'] === 'completed') text-yellow-600
                    @elseif($step['status'] === 'current') text-orange-600
                    @else text-gray-500 @endif">
                    {{ $step['title'] }}
                </p>
                @if(isset($step['details']) && $step['details'])
                    <p class="text-xs text-gray-500 mt-1">{{ $step['details'] }}</p>
                @endif
            </div>
        @endforeach
    </div>
    
    <!-- Message motivant et action -->
    <div class="mt-4 text-center">
        <p class="text-sm font-medium text-gray-700 mb-3">
            {{ $progression['message'] }}
        </p>
        
        <!-- Statistiques rapides -->
        @if($progression['stats']['products_count'] > 0)
            <div class="flex justify-center space-x-4 mb-3 text-xs text-gray-600">
                <span>📦 {{ $progression['stats']['products_count'] }} produit(s)</span>
                <span>✅ {{ $progression['stats']['active_products_count'] }} activé(s)</span>
                @if($progression['stats']['completed_orders_count'] > 0)
                    <span>🛒 {{ $progression['stats']['completed_orders_count'] }} vente(s)</span>
                @endif
            </div>
        @endif
        
        @if($progression['next_action'])
            <a href="{{ $progression['next_action']['url'] }}" 
               class="inline-flex items-center bg-yellow-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-yellow-700 transition-colors">
                <i class="{{ $progression['next_action']['icon'] }} mr-2"></i>
                {{ $progression['next_action']['text'] }}
            </a>
        @endif
    </div>
</div>
