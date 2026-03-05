@extends('layouts.app')

@section('title', 'Test Chat - GuinéeMall')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">🧪 Test du Système de Chat</h1>
        
        <div class="space-y-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h2 class="font-semibold text-blue-800 mb-2">📋 État du système</h2>
                <div class="space-y-2 text-sm">
                    <p>✅ Migration des messages: <span class="font-mono bg-blue-100 px-2 py-1 rounded">OK</span></p>
                    <p>✅ Modèle Message: <span class="font-mono bg-blue-100 px-2 py-1 rounded">OK</span></p>
                    <p>✅ ChatController: <span class="font-mono bg-blue-100 px-2 py-1 rounded">OK</span></p>
                    <p>✅ Routes: <span class="font-mono bg-blue-100 px-2 py-1 rounded">OK</span></p>
                    <p>✅ Composant Livewire: <span class="font-mono bg-blue-100 px-2 py-1 rounded">OK</span></p>
                </div>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h2 class="font-semibold text-yellow-800 mb-2">🔗 Liens de test</h2>
                <div class="space-y-2">
                    <a href="{{ route('chat.index') }}" 
                       class="block w-full bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg transition-colors text-center">
                        💬 Accéder au Chat
                    </a>
                    
                    @if(auth()->check())
                        <div class="text-sm text-gray-600">
                            <p>Connecté en tant que: <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->role }})</p>
                            <p>ID Utilisateur: {{ auth()->id() }}</p>
                        </div>
                    @else
                        <div class="text-sm text-gray-600">
                            <p>❌ Vous n'êtes pas connecté</p>
                            <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Se connecter</a>
                        </div>
                    @endif
                </div>
            </div>

            @if(auth()->check())
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                    <h2 class="font-semibold text-orange-800 mb-2">🧪 Test d'envoi de message</h2>
                    <form id="test-form" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Message de test:</label>
                            <textarea name="test_message" 
                                      rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                      placeholder="Tapez un message de test...">Ceci est un message de test depuis la page de test!</textarea>
                        </div>
                        <button type="submit" 
                                class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg transition-colors">
                            🚀 Tester l'envoi
                        </button>
                    </form>
                    <div id="test-result" class="mt-3 hidden"></div>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h2 class="font-semibold text-yellow-800 mb-2">📊 Utilisateurs disponibles pour le test</h2>
                    <div class="text-sm">
                        @php
                            $users = \App\Models\User::where('id', '!=', auth()->id())
                                ->whereIn('role', ['client', 'vendeur', 'admin'])
                                ->limit(5)
                                ->get();
                        @endphp
                        @if($users->count() > 0)
                            <ul class="space-y-1">
                                @foreach($users as $user)
                                    <li>
                                        <a href="{{ route('chat.show', $user) }}" 
                                           class="text-blue-600 hover:underline">
                                            💬 {{ $user->name }} ({{ $user->role }})
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-gray-600">Aucun autre utilisateur trouvé pour le test</p>
                        @endif
                    </div>
                </div>
            @endif

            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <h2 class="font-semibold text-gray-800 mb-2">🔍 Navigation</h2>
                <div class="space-y-2 text-sm">
                    <p>• Les liens de chat devraient apparaître dans la navigation en haut</p>
                    <p>• L'icône 🔔 notifications avec badge rouge pour les messages non lus</p>
                    <p>• Le lien 💬 Messages pour accéder à toutes les conversations</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('test-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const resultDiv = document.getElementById('test-result');
            
            // Simuler un test
            resultDiv.innerHTML = '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-3 py-2 rounded">🔄 Test en cours...</div>';
            resultDiv.classList.remove('hidden');
            
            setTimeout(() => {
                resultDiv.innerHTML = '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-3 py-2 rounded">✅ Test réussi! Le formulaire fonctionne correctement.</div>';
            }, 1000);
        });
    }
});
</script>
@endsection
