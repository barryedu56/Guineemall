<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - GuinéeMall</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        .yellow-gradient {
            background: linear-gradient(135deg, #FFC107 0%, #FFA726 50%, #FF8F00 100%);
        }
        
        .yellow-gradient-text {
            background: linear-gradient(135deg, #FFC107 0%, #FFA726 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .animated-bg {
            background: linear-gradient(-45deg, #FFF8E1, #FFECB3, #FFE082, #FFD54F);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .fade-in {
            animation: fadeIn 0.8s ease-in;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .btn-alive {
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .btn-alive::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-alive:hover::before {
            width: 300px;
            height: 300px;
        }
    </style>
</head>
<body class="animated-bg min-h-screen antialiased overflow-x-hidden">
    <!-- Navigation avec effet verre -->
    <nav class="sticky top-0 z-50 backdrop-blur-md bg-white/80 shadow-lg">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 py-3">
                <!-- Logo Animé -->
                <div class="flex items-center space-x-3 group cursor-pointer" onclick="window.location.href='{{ route('home') }}'">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 yellow-gradient rounded-xl flex items-center justify-center shadow-lg floating group-hover:rotate-12 transition-transform">
                        <i class="fas fa-shopping-bag text-white text-base sm:text-lg"></i>
                    </div>
                    <span class="text-xl sm:text-2xl font-black text-gray-800">Guinée<span class="yellow-gradient-text">Mall</span></span>
                </div>
                
                <!-- Navigation Links -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-4">
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center text-gray-700 hover:text-yellow-600 font-semibold transition-all transform hover:scale-105 px-4 py-2 rounded-lg bg-white/70 sm:bg-transparent">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        <span>Connexion</span>
                    </a>
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 yellow-gradient text-white font-bold rounded-xl hover:opacity-90 transition-all shadow-lg btn-alive transform hover:scale-105">
                        <i class="fas fa-user-plus mr-2"></i>
                        <span>Inscription</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section avec titre -->
    <section class="relative overflow-hidden pt-16 pb-8">
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl opacity-20 floating"></div>
            <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-orange-300 rounded-full mix-blend-multiply filter blur-xl opacity-20 floating" style="animation-delay: 2s;"></div>
        </div>
        
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center fade-in">
            <div class="inline-flex items-center px-4 py-2 bg-yellow-100 rounded-full border border-yellow-200 mb-4">
                <span class="flex h-2 w-2 rounded-full bg-yellow-600 mr-2 animate-pulse"></span>
                <span class="text-xs font-bold text-yellow-700 uppercase tracking-wider">Information</span>
            </div>
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-gray-900 mb-4 leading-tight">
                @yield('title')
            </h1>
            <div class="w-24 h-1 bg-gradient-to-r from-yellow-500 to-orange-500 mx-auto rounded-full"></div>
        </div>
    </section>

    <!-- Contenu Principal -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-16 fade-in">
        <div class="bg-white/80 backdrop-blur-sm rounded-3xl shadow-2xl p-6 sm:p-8 md:p-12 border border-yellow-100">
            <div class="prose prose-lg max-w-none text-gray-700 leading-relaxed space-y-6">
                @yield('content')
            </div>
            
            <!-- CTA en bas de page -->
            <div class="mt-12 pt-8 border-t border-gray-200 text-center">
                <p class="text-gray-600 mb-4 font-semibold">Prêt à commencer ?</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-6 py-3 yellow-gradient text-white font-bold rounded-xl hover:opacity-90 transition-all shadow-lg btn-alive transform hover:scale-105">
                        <i class="fas fa-rocket mr-2"></i>
                        Créer un compte
                    </a>
                    <a href="{{ route('home') }}" class="inline-flex items-center justify-center px-6 py-3 bg-white text-gray-800 font-bold rounded-xl border-2 border-yellow-500 hover:bg-yellow-50 transition-all transform hover:scale-105">
                        <i class="fas fa-home mr-2"></i>
                        Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Moderne -->
    <footer class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 text-white py-12 mt-16">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                <!-- Logo et Description -->
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="w-10 h-10 yellow-gradient rounded-xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-shopping-bag text-white"></i>
                        </div>
                        <span class="text-2xl font-black">Guinée<span class="text-yellow-400">Mall</span></span>
                    </div>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        La marketplace N°1 en Guinée. Une plateforme locale fiable pour acheter et vendre.
                    </p>
                </div>
                
                <!-- Liens Rapides -->
                <div>
                    <h3 class="text-white font-bold text-sm mb-4 uppercase tracking-wider">Liens Rapides</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('pages.about') }}" class="text-gray-400 hover:text-yellow-400 transition-colors">À propos</a></li>
                        <li><a href="{{ route('pages.contact') }}" class="text-gray-400 hover:text-yellow-400 transition-colors">Contact</a></li>
                        <li><a href="{{ route('pages.faq') }}" class="text-gray-400 hover:text-yellow-400 transition-colors">FAQ</a></li>
                        <li><a href="{{ route('pages.become-vendor') }}" class="text-gray-400 hover:text-yellow-400 transition-colors">Devenir vendeur</a></li>
                    </ul>
                </div>
                
                <!-- Légal -->
                <div>
                    <h3 class="text-white font-bold text-sm mb-4 uppercase tracking-wider">Légal</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('pages.terms') }}" class="text-gray-400 hover:text-yellow-400 transition-colors">Conditions d'utilisation</a></li>
                        <li><a href="{{ route('pages.privacy') }}" class="text-gray-400 hover:text-yellow-400 transition-colors">Politique de confidentialité</a></li>
                        <li><a href="{{ route('pages.cookies') }}" class="text-gray-400 hover:text-yellow-400 transition-colors">Cookies</a></li>
                        <li><a href="{{ route('pages.legal') }}" class="text-gray-400 hover:text-yellow-400 transition-colors">Mentions légales</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Bottom Footer -->
            <div class="border-t border-gray-700 pt-6 text-center">
                <p class="text-gray-500 text-xs">
                    © {{ date('Y') }} GuinéeMall. Une plateforme locale fiable pour acheter et vendre.
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
