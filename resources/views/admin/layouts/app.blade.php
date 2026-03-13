<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Administration') - {{ app_name('admin', 'title') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            font-family: 'Poppins', sans-serif !important;
        }
        
        body { 
            background-color: #FFFFFF;
            color: #1a1a1a;
        }
        
        /* Layout Structure */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            width: 260px;
            background: #FFFFFF;
            border-right: 1px solid #e5e7eb;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 40;
        }
        
        .admin-main {
            flex: 1;
            margin-left: 260px;
            background: #FFFFFF;
            min-height: 100vh;
        }
        
        .admin-header {
            background: #FFFFFF;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 30px;
            position: sticky;
            top: 0;
            z-index: 30;
        }
        
        .admin-content {
            padding: 30px;
            background: #FFFFFF;
        }
        
        /* Sidebar Styles */
        .sidebar-brand {
            padding: 24px 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .sidebar-nav {
            padding: 16px 12px;
        }
        
        .sidebar-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            margin: 4px 0;
            border-radius: 8px;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 14px;
        }
        
        .sidebar-item:hover {
            background: #f9fafb;
            color: #374151;
        }
        
        .sidebar-item.active {
            background: #fef3c7;
            color: #92400e;
            font-weight: 600;
        }
        
        .sidebar-item.active i {
            color: #92400e !important;
        }
        
        .sidebar-item i {
            width: 20px;
            margin-right: 12px;
            font-size: 16px;
            color: #6b7280;
            transition: color 0.2s ease;
        }
        
        .sidebar-item:hover i {
            color: #92400e;
        }
        
        /* Cards Uniformes */
        .admin-card {
            background: #FFFFFF;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }
        
        .admin-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        
        /* Tableaux Modernes */
        .admin-table {
            background: #FFFFFF;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .admin-table thead {
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .admin-table th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .admin-table td {
            padding: 16px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
            color: #374151;
        }
        
        .admin-table tbody tr:hover {
            background: #f9fafb;
        }
        
        .admin-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Boutons Uniformes */
        .admin-btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .admin-btn-primary {
            background: #F59E0B;
            color: #FFFFFF;
        }
        
        .admin-btn-primary:hover {
            background: #d97706;
            transform: translateY(-1px);
        }
        
        .admin-btn-secondary {
            background: #6b7280;
            color: #FFFFFF;
        }
        
        .admin-btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }
        
        .admin-btn-danger {
            background: #ef4444;
            color: #FFFFFF;
        }
        
        .admin-btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }
        
        .admin-btn-success {
            background: #10b981;
            color: #FFFFFF;
        }
        
        .admin-btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        
        /* Badges Stylisés */
        .admin-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .admin-badge-admin {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .admin-badge-vendor {
            background: #fef3c7;
            color: #92400e;
        }
        
        .admin-badge-client {
            background: #d1fae5;
            color: #065f46;
        }
        
        .admin-badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .admin-badge-warning {
            background: #fed7aa;
            color: #92400e;
        }
        
        .admin-badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Liens */
        .admin-link {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }
        
        .admin-link:hover {
            color: #4338ca;
            text-decoration: underline;
        }
        
        /* Formulaires */
        .admin-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background: #FFFFFF;
            transition: all 0.2s ease;
        }
        
        .admin-input:focus {
            outline: none;
            border-color: #F59E0B;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }
        
        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .slide-in {
            animation: slideIn 0.3s ease-out;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .admin-sidebar.open {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .admin-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-brand">
                <h1 class="text-xl font-bold text-gray-900">
                    Guinée<span class="text-indigo-600">Mall</span>
                </h1>
                <p class="text-sm text-gray-500 mt-1">Administration</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="{{ route('admin.dashboard') }}" 
                   class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    Tableau de bord
                </a>
                
                <a href="{{ route('admin.vendors.index') }}" 
                   class="sidebar-item {{ request()->routeIs('admin.vendors.*') ? 'active' : '' }}">
                    <i class="fas fa-store"></i>
                    Vendeurs
                </a>
                
                <a href="{{ route('admin.clients.index') }}" 
                   class="sidebar-item {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    Clients
                </a>
                
                <a href="{{ route('admin.products.index') }}" 
                   class="sidebar-item {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                    <i class="fas fa-box"></i>
                    Produits
                </a>
                
                <a href="{{ route('admin.categories.index') }}" 
                   class="sidebar-item {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <i class="fas fa-tags"></i>
                    Catégories
                </a>
                
                <a href="{{ route('admin.orders.index') }}" 
                   class="sidebar-item {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                    <i class="fas fa-shopping-cart"></i>
                    Commandes
                </a>
                
                <a href="{{ route('admin.logs.index') }}" 
                   class="sidebar-item {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                    <i class="fas fa-file-alt"></i>
                    Logs
                </a>
                
                <div class="border-t border-gray-200 pt-4 mt-4">
                    <a href="{{ route('admin.profile.edit') }}" 
                       class="sidebar-item">
                        <i class="fas fa-user-cog"></i>
                        Profil
                    </a>
                    
                    <form method="POST" action="{{ route('logout') }}" class="block">
                        @csrf
                        <button type="submit" class="sidebar-item w-full text-left">
                            <i class="fas fa-sign-out-alt"></i>
                            Déconnexion
                        </button>
                    </form>
                </div>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button class="lg:hidden text-gray-500 hover:text-gray-700" onclick="toggleSidebar()">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h2 class="text-xl font-semibold text-gray-900">@yield('page-title', 'Administration')</h2>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500">Administrateur</p>
                        </div>
                        <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-indigo-600"></i>
                        </div>
                    </div>
                </div>
            </header>

        <!-- Page Content -->
        <div class="p-4 sm:p-6 lg:p-8">
            @yield('content')
        </div>
    </main>

</div>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation des sidebar items
    const sidebarItems = document.querySelectorAll('.sidebar-item');
    sidebarItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(8px)';
        });
        item.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = 'translateX(0)';
            }
        });
    });

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Toggle sidebar mobile
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('adminOverlay');
    const menuButton = document.getElementById('adminMenuButton');

    const openSidebar = () => {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    const closeSidebar = () => {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    if (menuButton) {
        menuButton.addEventListener('click', openSidebar);
    }
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
});
</script>

@yield('scripts')

</body>
</html>
