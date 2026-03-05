@extends('admin.layouts.app')

@section('title', 'Gestion des vendeurs')
@section('page-title', 'Vendeurs')

@section('content')
<div class="slide-in">
    <!-- Header Stats -->
    <div class="admin-card mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Vendeurs</h2>
                <p class="text-sm text-gray-600 mt-1">Gestion de tous les vendeurs inscrits sur la plateforme.</p>
            </div>
            <div class="flex items-center space-x-3">
                <span class="admin-badge admin-badge-vendor">{{ $vendors->count() }} Total</span>
                <a href="{{ route('admin.vendors.create') }}" class="admin-btn admin-btn-primary">
                    <i class="fas fa-plus"></i>
                    Nouveau Vendeur
                </a>
            </div>
        </div>
    </div>

    <!-- Vendors Table -->
    <div class="admin-card">
        @if($vendors->isEmpty())
            <div class="text-center py-12">
                <i class="fas fa-store text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-500 font-medium">Aucun vendeur disponible</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="admin-table w-full">
                    <thead>
                        <tr>
                            <th>Vendeur</th>
                            <th>Email</th>
                            <th>Statut</th>
                            <th>Date d'inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vendors as $vendor)
                            <tr>
                                <td>
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                                            @if($vendor->image_url)
                                                <img src="{{ $vendor->image_url }}" alt="{{ $vendor->shop_name }}" class="w-full h-full rounded-full object-cover">
                                            @else
                                                <i class="fas fa-store text-gray-600"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $vendor->shop_name }}</p>
                                            <p class="text-sm text-gray-500">{{ $vendor->user?->name ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-envelope text-gray-400"></i>
                                        <span class="text-gray-700">{{ $vendor->user?->email ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="admin-badge 
                                        @if($vendor->status === 'approved')
                                            admin-badge-success
                                        @elseif($vendor->status === 'suspended')
                                            admin-badge-danger
                                        @else
                                            admin-badge-warning
                                        @endif
                                    ">
                                        {{ ucfirst($vendor->status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-sm text-gray-600">{{ $vendor->created_at->format('d/m/Y') }}</span>
                                </td>
                                <td>
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('admin.vendors.show', $vendor) }}" 
                                           class="admin-link">
                                            <i class="fas fa-eye"></i>
                                            Voir
                                        </a>
                                        
                                        @if($vendor->status === 'pending')
                                            <form method="POST" action="{{ route('admin.vendors.approve', $vendor) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="admin-link text-green-600 hover:text-green-700">
                                                    <i class="fas fa-check"></i>
                                                    Approuver
                                                </button>
                                            </form>
                                        @elseif($vendor->status === 'approved')
                                            <form method="POST" action="{{ route('admin.vendors.suspend', $vendor) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="admin-link text-yellow-600 hover:text-yellow-700">
                                                    <i class="fas fa-pause"></i>
                                                    Suspendre
                                                </button>
                                            </form>
                                        @elseif($vendor->status === 'suspended')
                                            <form method="POST" action="{{ route('admin.vendors.approve', $vendor) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="admin-link text-green-600 hover:text-green-700">
                                                    <i class="fas fa-play"></i>
                                                    Réactiver
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($vendors->hasPages())
                <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200">
                    <div class="text-sm text-gray-700">
                        Affichage de {{ $vendors->firstItem() }} à {{ $vendors->lastItem() }} 
                        sur {{ $vendors->total() }} résultats
                    </div>
                    <div class="flex items-center space-x-2">
                        @if($vendors->currentPage() > 1)
                            <a href="{{ $vendors->previousPageUrl() }}" 
                               class="admin-btn admin-btn-secondary">
                                <i class="fas fa-chevron-left"></i>
                                Précédent
                            </a>
                        @endif
                        
                        <span class="text-sm text-gray-600">
                            Page {{ $vendors->currentPage() }} / {{ $vendors->lastPage() }}
                        </span>
                        
                        @if($vendors->hasMorePages())
                            <a href="{{ $vendors->nextPageUrl() }}" 
                               class="admin-btn admin-btn-secondary">
                                Suivant
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
