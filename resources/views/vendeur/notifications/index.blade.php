@extends('vendeur.layouts.app')

@section('title', 'Notifications - Espace Vendeur')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="notificationsManager()">
    <!-- GUIDE VENDEUR INTÉGRÉ -->
    @include('vendeur.components.guide')
    
    <!-- Header Notifications Dynamique -->
    <div class="bg-gradient-to-r from-yellow-500 via-orange-500 to-orange-600 rounded-2xl p-6 text-white shadow-xl mb-8 relative overflow-hidden">
        <!-- Animation d'arrière-plan -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full -mr-32 -mt-32 animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-white rounded-full -ml-24 -mb-24 animate-pulse delay-75"></div>
        </div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="mb-4 md:mb-0">
                <h1 class="text-2xl md:text-3xl font-bold mb-2 flex items-center">
                    <i class="fas fa-bell mr-3 animate-bounce"></i>
                    Notifications
                    <span x-show="unreadCount > 0" 
                          x-text="'(' + unreadCount + ')'" 
                          class="ml-3 px-2 py-1 bg-white/20 rounded-full text-sm font-semibold animate-pulse"></span>
                </h1>
                <p class="text-yellow-100">Restez informé des activités importantes de votre boutique</p>
            </div>
            
            <!-- Actions Rapides -->
            <div class="flex gap-3">
                <button @click="markAllAsRead()" 
                        class="px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-lg font-medium hover:bg-white/30 transition-all transform hover:scale-105">
                    <i class="fas fa-check-double mr-2"></i>
                    Tout marquer comme lu
                </button>
                <button @click="refreshNotifications()" 
                        class="px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-lg font-medium hover:bg-white/30 transition-all transform hover:scale-105">
                    <i class="fas fa-sync-alt mr-2" :class="{'animate-spin': refreshing}"></i>
                    Actualiser
                </button>
            </div>
        </div>
    </div>

    <!-- Filtres Dynamiques -->
    <div class="bg-white rounded-xl shadow-lg p-4 mb-6 border border-gray-100">
        <div class="flex flex-wrap gap-2 items-center">
            <span class="text-sm font-medium text-gray-700 mr-2">Filtrer par :</span>
            <template x-for="filter in filters" :key="filter.key">
                <button @click="activeFilter = filter.key"
                        :class="activeFilter === filter.key ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-3 py-1 rounded-full text-sm font-medium transition-all transform hover:scale-105">
                    <i :class="filter.icon" class="mr-1"></i>
                    <span x-text="filter.label"></span>
                    <span x-show="filter.count > 0" 
                          x-text="'(' + filter.count + ')'" 
                          class="ml-1 text-xs"></span>
                </button>
            </template>
        </div>
    </div>

    <!-- Notifications List Dynamique -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <!-- Header avec compteur -->
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900 flex items-center">
                    <i class="fas fa-list text-blue-500 mr-3"></i>
                    <span x-text="getFilteredNotifications().length + ' notification(s)'"></span>
                </h2>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">
                        <span class="inline-block w-2 h-2 bg-yellow-500 rounded-full mr-1"></span>
                        <span x-text="unreadCount + ' non lue(s)'"></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Notifications Items -->
        <div class="divide-y divide-gray-100">
            <template x-for="notification in getFilteredNotifications()" :key="notification.id">
                <div @click="markAsRead(notification.id)"
                     :class="[
                         'p-4 transition-all duration-300 cursor-pointer hover:bg-gray-50',
                         notification.read ? 'bg-white' : 'bg-blue-50 border-l-4 border-blue-500'
                     ]"
                     class="group">
                    <div class="flex items-start">
                        <!-- Icône animée -->
                        <div :class="[
                            'w-12 h-12 rounded-full flex items-center justify-center mr-4 flex-shrink-0 transition-all duration-300',
                            notification.type === 'warning' ? 'bg-orange-100' : 
                            notification.type === 'success' ? 'bg-yellow-100' : 
                            notification.type === 'info' ? 'bg-blue-100' : 'bg-orange-100'
                        ]">
                            <i :class="[
                                notification.type === 'warning' ? 'fas fa-exclamation-triangle text-orange-600' : 
                                notification.type === 'success' ? 'fas fa-check-circle text-yellow-600' : 
                                notification.type === 'info' ? 'fas fa-info-circle text-blue-600' : 'fas fa-shopping-cart text-orange-600',
                                'text-lg group-hover:scale-110 transition-transform'
                            ]"></i>
                        </div>
                        
                        <!-- Contenu -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors" 
                                    x-text="notification.title"></h3>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500" x-text="notification.time"></span>
                                    <div x-show="!notification.read" 
                                         class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mb-2" x-text="notification.message"></p>
                            
                            <!-- Actions -->
                            <div class="flex items-center gap-3">
                                <a :href="notification.actionUrl" 
                                   @click.stop
                                   class="text-sm font-medium hover:underline transition-colors"
                                   :class="[
                                       notification.type === 'warning' ? 'text-orange-600 hover:text-orange-700' : 
                                       notification.type === 'success' ? 'text-yellow-600 hover:text-yellow-700' : 
                                       notification.type === 'info' ? 'text-blue-600 hover:text-blue-700' : 'text-orange-600 hover:text-orange-700'
                                   ]">
                                    <span x-text="notification.actionText"></span>
                                    <i class="fas fa-arrow-right ml-1 text-xs"></i>
                                </a>
                                
                                <button @click.stop="deleteNotification(notification.id)"
                                        class="text-sm text-gray-400 hover:text-red-600 transition-colors">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="getFilteredNotifications().length === 0" 
             class="text-center py-12 px-6">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-bell-slash text-gray-400 text-3xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                <span x-show="activeFilter === 'all'">Aucune notification</span>
                <span x-show="activeFilter !== 'all'">Aucune notification dans cette catégorie</span>
            </h3>
            <p class="text-gray-600 mb-4">
                <span x-show="activeFilter === 'all'">Vous êtes à jour avec toutes vos notifications</span>
                <span x-show="activeFilter !== 'all'">Essayez un autre filtre pour voir vos notifications</span>
            </p>
            <button @click="activeFilter = 'all'" 
                    x-show="activeFilter !== 'all'"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
                Voir toutes les notifications
            </button>
        </div>
    </div>
</div>

<script>
function notificationsManager() {
    return {
        notifications: [
            {
                id: 1,
                type: 'warning',
                title: 'Stock faible',
                message: 'Certains de vos produits ont un stock faible. Pensez à les réapprovisionner pour éviter les ruptures.',
                time: 'Il y a 2 heures',
                read: false,
                actionUrl: '{{ route("vendeur.products.index") }}',
                actionText: 'Voir les produits concernés'
            },
            {
                id: 2,
                type: 'success',
                title: 'Nouvelle commande',
                message: 'Vous avez reçu une nouvelle commande #12345. Préparez les articles pour la livraison.',
                time: 'Il y a 5 heures',
                read: false,
                actionUrl: '{{ route("vendeur.orders.show", 12345) }}',
                actionText: 'Voir la commande'
            },
            {
                id: 3,
                type: 'info',
                title: 'Produit approuvé',
                message: 'Votre produit "Téléphone Samsung Galaxy" a été approuvé et est maintenant visible sur la marketplace.',
                time: 'Hier',
                read: true,
                actionUrl: '{{ route("vendeur.products.show", 1) }}',
                actionText: 'Voir le produit'
            },
            {
                id: 4,
                type: 'message',
                title: 'Nouveau message client',
                message: 'Un client vous a envoyé un message concernant votre produit "Ordinateur Portable Dell".',
                time: 'Il y a 2 jours',
                read: true,
                actionUrl: '#',
                actionText: 'Lire le message'
            },
            {
                id: 5,
                type: 'info',
                title: 'Rapport hebdomadaire',
                message: 'Votre boutique a généré 15 ventes cette semaine pour un total de 2,500,000 GNF.',
                time: 'Il y a 3 jours',
                read: true,
                actionUrl: '{{ route("vendeur.dashboard") }}',
                actionText: 'Voir les statistiques'
            }
        ],
        activeFilter: 'all',
        refreshing: false,
        
        filters: [
            { key: 'all', label: 'Toutes', icon: 'fas fa-inbox', count: 5 },
            { key: 'unread', label: 'Non lues', icon: 'fas fa-envelope', count: 2 },
            { key: 'warning', label: 'Alertes', icon: 'fas fa-exclamation-triangle', count: 1 },
            { key: 'success', label: 'Succès', icon: 'fas fa-check-circle', count: 1 },
            { key: 'info', label: 'Infos', icon: 'fas fa-info-circle', count: 2 }
        ],
        
        get unreadCount() {
            return this.notifications.filter(n => !n.read).length;
        },
        
        getFilteredNotifications() {
            if (this.activeFilter === 'all') {
                return this.notifications;
            } else if (this.activeFilter === 'unread') {
                return this.notifications.filter(n => !n.read);
            } else {
                return this.notifications.filter(n => n.type === this.activeFilter);
            }
        },
        
        markAsRead(id) {
            const notification = this.notifications.find(n => n.id === id);
            if (notification) {
                notification.read = true;
            }
        },
        
        markAllAsRead() {
            this.notifications.forEach(n => n.read = true);
        },
        
        deleteNotification(id) {
            this.notifications = this.notifications.filter(n => n.id !== id);
        },
        
        refreshNotifications() {
            this.refreshing = true;
            setTimeout(() => {
                this.refreshing = false;
            }, 1000);
        }
    }
}
</script>
@endsection
