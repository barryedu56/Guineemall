@extends('admin.layouts.app')

@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')

@section('content')
<div class="slide-in">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="admin-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Produits</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $productsCount }}</p>
                    <div class="flex items-center mt-2">
                        <i class="fas fa-arrow-up text-green-500 text-xs mr-1"></i>
                        <span class="text-xs text-green-600 font-medium">+12.5%</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-box text-orange-600"></i>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Vendeurs</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $vendeursCount }}</p>
                    <div class="flex items-center mt-2">
                        <i class="fas fa-arrow-up text-green-500 text-xs mr-1"></i>
                        <span class="text-xs text-green-600 font-medium">+8.3%</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-store text-orange-600"></i>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Clients</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $clientsCount }}</p>
                    <div class="flex items-center mt-2">
                        <i class="fas fa-arrow-up text-green-500 text-xs mr-1"></i>
                        <span class="text-xs text-green-600 font-medium">+15.7%</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-orange-600"></i>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Commandes</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $ordersCount }}</p>
                    <div class="flex items-center mt-2">
                        <i class="fas fa-arrow-down text-red-500 text-xs mr-1"></i>
                        <span class="text-xs text-red-600 font-medium">-3.2%</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-orange-600"></i>
                </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Recent Activity -->
        <div class="admin-card">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Activité Récente</h3>
            <div class="space-y-4">
                @if(isset($recent_activities) && $recent_activities->count() > 0)
                    @foreach($recent_activities->take(5) as $activity)
                        <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                            <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                <i class="fas fa-bell text-orange-600 text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $activity->description ?? 'Activité système' }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $activity->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-bell-slash text-3xl text-gray-400 mb-3"></i>
                        <p class="text-gray-500">Aucune activité récente</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="admin-card">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Actions Rapides</h3>
            <div class="grid grid-cols-2 gap-4">
                <a href="{{ route('admin.products.index') }}" class="admin-btn admin-btn-primary">
                    <i class="fas fa-plus"></i>
                    Nouveau Produit
                </a>
                <a href="{{ route('admin.vendors.index') }}" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-eye"></i>
                    Voir Vendeurs
                </a>
                <a href="{{ route('admin.clients.index') }}" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-users"></i>
                    Gérer Clients
                </a>
                <a href="{{ route('admin.orders.index') }}" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-shopping-cart"></i>
                    Commandes
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Users & System Status -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Users -->
        <div class="admin-card">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Utilisateurs Récents</h3>
            <div class="space-y-4">
                @if(isset($recent_users) && $recent_users->count() > 0)
                    @foreach($recent_users->take(5) as $user)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-orange-600 text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                </div>
                            </div>
                            <div>
                                <span class="admin-badge admin-badge-client">Client</span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-users text-3xl text-gray-400 mb-3"></i>
                        <p class="text-gray-500">Aucun utilisateur récent</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- System Status -->
        <div class="admin-card">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">État du Système</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-server text-green-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Serveur</p>
                            <p class="text-xs text-gray-500">En ligne</p>
                        </div>
                    </div>
                    <div>
                        <span class="admin-badge admin-badge-success">Actif</span>
                    </div>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-database text-green-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Base de données</p>
                            <p class="text-xs text-gray-500">Connectée</p>
                        </div>
                    </div>
                    <div>
                        <span class="admin-badge admin-badge-success">OK</span>
                    </div>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-hdd text-yellow-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Stockage</p>
                            <p class="text-xs text-gray-500">75% utilisé</p>
                        </div>
                    </div>
                    <div>
                        <span class="admin-badge admin-badge-warning">Attention</span>
                    </div>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-shield-alt text-green-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Sécurité</p>
                            <p class="text-xs text-gray-500">Mis à jour</p>
                        </div>
                    </div>
                    <div>
                        <span class="admin-badge admin-badge-success">Sécurisé</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation des compteurs
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        const target = parseInt(counter.textContent.replace(/[^0-9]/g, ''));
        let current = 0;
        const increment = target / 100;
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            counter.textContent = Math.floor(current).toLocaleString();
        }, 20);
    });

    // Graphique d'activité
    const ctx = document.getElementById('activityChart').getContext('2d');
    const dailyStats = @json($dailyStats);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dailyStats.map(stat => stat.date),
            datasets: [{
                label: 'Commandes',
                data: dailyStats.map(stat => stat.orders),
                borderColor: 'rgba(255, 193, 7, 1)',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Utilisateurs',
                data: dailyStats.map(stat => stat.users),
                borderColor: 'rgba(255, 167, 38, 1)',
                backgroundColor: 'rgba(255, 167, 38, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Animation des progress bars
    setTimeout(() => {
        document.querySelectorAll('.progress-bar').forEach(bar => {
            bar.style.width = bar.style.width;
        });
    }, 500);
});
</script>
@endsection
