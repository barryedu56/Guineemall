{{-- resources/views/vendeur/partials/sidebar.blade.php --}}
<div class="p-6 h-full">
    <!-- Logo vendeur -->
    <div class="text-center mb-8">
        @if(auth()->user()->vendor && auth()->user()->vendor->image_url)
            <img src="{{ auth()->user()->vendor->image_url }}" 
                 alt="{{ auth()->user()->vendor->shop_name }}" 
                 class="w-20 h-20 rounded-full mx-auto mb-4 object-cover ring-4 ring-yellow-100">
        @else
            <div class="w-20 h-20 bg-gradient-to-br from-yellow-100 to-orange-100 rounded-full mx-auto mb-4 flex items-center justify-center ring-4 ring-yellow-100">
                <i class="fas fa-store text-yellow-600 text-2xl"></i>
            </div>
        @endif
        
        <h3 class="font-black text-lg text-gray-800">{{ auth()->user()->vendor->shop_name ?? 'Ma Boutique' }}</h3>
        <p class="text-sm text-gray-500 mt-1">{{ auth()->user()->email }}</p>
        @if(auth()->user()->vendor)
            <span class="inline-block mt-2 px-3 py-1 text-xs font-bold rounded-full 
                @if(auth()->user()->vendor->status == 'approved') bg-yellow-100 text-yellow-800
                @elseif(auth()->user()->vendor->status == 'pending') bg-orange-100 text-orange-800
                @else bg-red-100 text-red-800 @endif">
                {{ auth()->user()->vendor->status == 'approved' ? 'Validée' : (auth()->user()->vendor->status == 'pending' ? 'En attente' : 'Suspendue') }}
            </span>
        @endif
    </div>

    <!-- Navigation -->
    <nav class="space-y-2 mb-8">
        <!-- Tableau de bord -->
        <a href="{{ route('vendeur.dashboard') }}" 
           class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-xl {{ request()->routeIs('vendeur.dashboard*') ? 'active text-white' : 'text-gray-700 hover:text-yellow-600' }} transition-all">
            <i class="fas fa-tachometer-alt w-5"></i>
            <span class="font-medium">Tableau de bord</span>
            @if(request()->routeIs('vendeur.dashboard*'))
                <i class="fas fa-chevron-right ml-auto text-yellow-300"></i>
            @endif
        </a>

        <!-- Produits -->
        <div class="space-y-1">
            <a href="{{ route('vendeur.products.index') }}" 
               class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-xl {{ request()->routeIs('vendeur.products*') ? 'active text-white' : 'text-gray-700 hover:text-yellow-600' }} transition-all">
                <i class="fas fa-box w-5"></i>
                <span class="font-medium">Produits</span>
                @if(request()->routeIs('vendeur.products*'))
                    <i class="fas fa-chevron-right ml-auto text-yellow-300"></i>
                @endif
            </a>
            
            @if(request()->routeIs('vendeur.products*'))
                <div class="ml-8 space-y-1">
                    <a href="{{ route('vendeur.products.create') }}" 
                       class="flex items-center space-x-2 px-3 py-2 text-sm rounded-lg bg-yellow-50 text-yellow-600 hover:bg-yellow-100 transition-all">
                        <i class="fas fa-plus w-4"></i>
                        <span>Ajouter un produit</span>
                    </a>
                </div>
            @endif
        </div>

        <!-- Commandes -->
        <div class="space-y-1">
            <a href="{{ route('vendeur.orders.index') }}" 
               class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-xl {{ request()->routeIs('vendeur.orders*') ? 'active text-white' : 'text-gray-700 hover:text-yellow-600' }} transition-all">
                <i class="fas fa-shopping-cart w-5"></i>
                <span class="font-medium">Commandes</span>
                @if($pendingCount = auth()->user()->vendor ? auth()->user()->vendor->vendorOrders()->where('status', 'pending')->count() : 0)
                    <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1 animate-pulse">
                        {{ $pendingCount }}
                    </span>
                @endif
                @if(request()->routeIs('vendeur.orders*'))
                    <i class="fas fa-chevron-right ml-auto text-yellow-300"></i>
                @endif
            </a>
            
            @if(request()->routeIs('vendeur.orders*'))
                <div class="ml-8 space-y-1">
                    <a href="{{ route('vendeur.orders.sales-report') }}" 
                       class="flex items-center space-x-2 px-3 py-2 text-sm rounded-lg bg-yellow-50 text-yellow-600 hover:bg-yellow-100 transition-all">
                        <i class="fas fa-chart-line w-4"></i>
                        <span>Rapport de ventes</span>
                    </a>
                </div>
            @endif
        </div>

        <!-- Messages -->
        <a href="{{ route('chat.index') }}" 
           class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-xl text-gray-700 hover:text-yellow-600 transition-all">
            <i class="fas fa-comments w-5"></i>
            <span class="font-medium">Messages clients</span>
        </a>

        <!-- Profil -->
        <div class="space-y-1">
            <a href="{{ route('vendeur.profile.index') }}" 
               class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-xl {{ request()->routeIs('vendeur.profile*') ? 'active text-white' : 'text-gray-700 hover:text-yellow-600' }} transition-all">
                <i class="fas fa-store w-5"></i>
                <span class="font-medium">Ma boutique</span>
                @if(request()->routeIs('vendeur.profile*'))
                    <i class="fas fa-chevron-right ml-auto text-yellow-300"></i>
                @endif
            </a>
            
            @if(request()->routeIs('vendeur.profile*'))
                <div class="ml-8 space-y-1">
                    <a href="{{ route('vendeur.profile.edit') }}" 
                       class="flex items-center space-x-2 px-3 py-2 text-sm rounded-lg bg-yellow-50 text-yellow-600 hover:bg-yellow-100 transition-all">
                        <i class="fas fa-edit w-4"></i>
                        <span>Modifier profil</span>
                    </a>
                    <a href="{{ route('vendeur.profile.password.edit') }}" 
                       class="flex items-center space-x-2 px-3 py-2 text-sm rounded-lg bg-yellow-50 text-yellow-600 hover:bg-yellow-100 transition-all">
                        <i class="fas fa-key w-4"></i>
                        <span>Mot de passe</span>
                    </a>
                </div>
            @endif
        </div>
    </nav>

    <!-- Statistiques rapides -->
    @if(auth()->user()->vendor)
        <div class="p-4 bg-gradient-to-br from-yellow-50 to-orange-50 rounded-2xl border border-yellow-100">
            <h4 class="font-black text-sm mb-4 text-gray-800">📊 Aperçu rapide</h4>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Produits actifs:</span>
                    <span class="font-black text-yellow-600">{{ auth()->user()->vendor->products()->where('status', 'active')->count() }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Stock faible:</span>
                    <span class="font-black text-orange-600">{{ auth()->user()->vendor->products()->where('stock', '<=', 5)->where('stock', '>', 0)->count() }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Rupture stock:</span>
                    <span class="font-black text-red-600">{{ auth()->user()->vendor->products()->where('stock', '<=', 0)->count() }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Commandes en attente:</span>
                    <span class="font-black text-orange-600">{{ auth()->user()->vendor->vendorOrders()->where('status', 'pending')->count() }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Ventes du mois:</span>
                    <span class="font-black text-yellow-600 text-xs">
                        {{ number_format(auth()->user()->vendor->vendorOrders()->where('status', 'delivered')->whereMonth('created_at', now()->month)->sum('total_amount'), 0, ',', ' ') }} GNF
                    </span>
                </div>
            </div>
        </div>
    @endif
</div>
