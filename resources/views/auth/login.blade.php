<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - GuinéeMall</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap');
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        /* Gradient Background */
        .gradient-bg {
            background: linear-gradient(135deg, #f66831ff 0%, #efa365ff 100%);
            position: relative;
        }
        
        .gradient-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        /* Animations Tailwind-style */
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(3deg); }
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes bounce-in {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        
        .animate-float-delayed {
            animation: float 6s ease-in-out infinite;
            animation-delay: 1s;
        }
        
        .animate-float-delayed-2 {
            animation: float 6s ease-in-out infinite;
            animation-delay: 2s;
        }
        
        .animate-slide-left {
            animation: slideInLeft 0.8s ease-out;
        }
        
        .animate-slide-right {
            animation: slideInRight 0.8s ease-out;
        }
        
        .animate-fade-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .animate-fade-up-delay-1 {
            animation: fadeInUp 0.6s ease-out 0.1s both;
        }
        
        .animate-fade-up-delay-2 {
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }
        
        .animate-fade-up-delay-3 {
            animation: fadeInUp 0.6s ease-out 0.3s both;
        }
        
        .animate-bounce-in {
            animation: bounce-in 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        /* Input Effects */
        .input-wrapper {
            position: relative;
            overflow: hidden;
        }
        
        .input-wrapper::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(90deg, #e88b3bff, #e3cf8cff);
            transition: width 0.4s ease;
        }
        
        .input-wrapper:focus-within::after {
            width: 100%;
        }
        
        .input-field {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .input-field:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px -10px rgba(244, 196, 74, 0.4);
        }
        
        /* Button Effects */
        .btn-gradient {
            background: linear-gradient(135deg, #edc283ff 0%, #da9754ff 100%);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .btn-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(220, 188, 99, 0.89), transparent);
            transition: left 0.5s;
        }
        
        .btn-gradient:hover::before {
            left: 100%;
        }
        
        .btn-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px -10px rgba(249, 221, 41, 0.78);
        }
        
        .btn-gradient:active {
            transform: translateY(-1px);
        }
        
        /* Card Hover */
        .feature-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        /* Social Button */
        .social-btn {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .social-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(217, 197, 43, 0.1);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .social-btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .social-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(226, 142, 39, 0.1);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-yellow-50 via-orange-50 to-yellow-100 min-h-screen">
    
    <!-- Floating decorative elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute top-20 left-10 w-72 h-72 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-float"></div>
        <div class="absolute top-40 right-20 w-72 h-72 bg-orange-300 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-float-delayed"></div>
        <div class="absolute -bottom-8 left-1/3 w-72 h-72 bg-yellow-200 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-float-delayed-2"></div>
    </div>

    <div class="min-h-screen flex items-center justify-center p-4 relative z-10">
        <div class="w-full max-w-6xl">
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
                <div class="flex flex-col lg:flex-row">
                    
                    <!-- Left Panel - E-commerce Visual -->
                    <div class="lg:w-1/2 gradient-bg p-12 lg:p-16 text-white relative animate-slide-left">
                        <div class="relative z-10 h-full flex flex-col justify-between">
                            
                            <!-- Logo -->
                            <div class="animate-bounce-in">
                                <div class="flex items-center space-x-3">
                                    <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center shadow-2xl transform hover:rotate-12 transition-transform">
                                        <svg class="w-8 h-8 text-yellow-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h1 class="text-3xl font-black tracking-tight">GuinéeMall</h1>
                                        <p class="text-yellow-200 text-xs font-semibold">Votre marketplace #1</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Main Content -->
                            <div class="my-12">
                                <h2 class="text-5xl lg:text-6xl font-black leading-tight mb-6 animate-fade-up">
                                    Shopping<br/>
                                    en toute<br/>
                                    <span class="text-yellow-300">simplicité</span>
                                </h2>
                                <p class="text-yellow-100 text-lg lg:text-xl leading-relaxed max-w-md animate-fade-up-delay-1">
                                    Découvrez des milliers de produits, des vendeurs de confiance et profitez d'une expérience d'achat exceptionnelle.
                                </p>
                            </div>

                            <!-- Features -->
                            <div class="grid grid-cols-3 gap-4 animate-fade-up-delay-2">
                                <div class="feature-card bg-white/10 backdrop-blur-sm rounded-2xl p-4 text-center hover:bg-white/20 cursor-pointer">
                                    <div class="flex justify-center mb-2">
                                        <svg class="w-8 h-8 text-yellow-300" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                                        </svg>
                                    </div>
                                    <div class="text-2xl font-black mb-1">100%</div>
                                    <div class="text-xs font-semibold text-yellow-200">Sécurisé</div>
                                </div>
                                
                                <div class="feature-card bg-white/10 backdrop-blur-sm rounded-2xl p-4 text-center hover:bg-white/20 cursor-pointer">
                                    <div class="flex justify-center mb-2">
                                        <svg class="w-8 h-8 text-yellow-300" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                        </svg>
                                    </div>
                                    <div class="text-2xl font-black mb-1">24h</div>
                                    <div class="text-xs font-semibold text-yellow-200">Livraison</div>
                                </div>
                                
                                <div class="feature-card bg-white/10 backdrop-blur-sm rounded-2xl p-4 text-center hover:bg-white/20 cursor-pointer">
                                    <div class="flex justify-center mb-2">
                                        <svg class="w-8 h-8 text-yellow-300" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                        </svg>
                                    </div>
                                    <div class="text-2xl font-black mb-1">6</div>
                                    <div class="text-xs font-semibold text-yellow-200">Note client</div>
                                </div>
                            </div>

                            <!-- Floating Icons -->
                            <div class="absolute top-1/4 right-12 opacity-10">
                                <svg class="w-32 h-32 animate-float" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/>
                                </svg>
                            </div>
                            
                            <div class="absolute bottom-1/3 left-12 opacity-10">
                                <svg class="w-24 h-24 animate-float-delayed" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M15.55 13c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.37-.66-.11-1.48-.87-1.48H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.48 17 7 17h12v-2H7l1.1-2h7.45zM6.16 6h12.15l-2.76 5H8.53L6.16 6zM7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Right Panel - Login Form -->
                    <div class="lg:w-1/2 p-8 lg:p-16 animate-slide-right">
                        <div class="max-w-md mx-auto">
                            
                            <!-- Mobile Logo -->
                            <div class="lg:hidden flex justify-center mb-8 animate-bounce-in">
                                <div class="flex items-center space-x-2">
                                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                                        </svg>
                                    </div>
                                    <span class="text-2xl font-black bg-gradient-to-r from-yellow-500 to-orange-600 bg-clip-text text-transparent">GuinéeMall</span>
                                </div>
                            </div>

                            <!-- Header -->
                            <div class="mb-10 animate-fade-up">
                                <h2 class="text-4xl lg:text-5xl font-black text-gray-900 mb-3">
                                    Bienvenue ! 
                                </h2>
                                <p class="text-gray-600 text-lg font-medium">
                                    Connectez-vous pour accéder à votre compte
                                </p>
                            </div>

                            <!-- Message d'erreur global -->
                            @if ($errors->any())
                                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg animate-fade-up">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                                        </svg>
                                        <p class="text-red-600 font-semibold">{{ $errors->first() }}</p>
                                    </div>
                                </div>
                            @endif

                            <!-- Session Status (success messages) -->
                            @if (session('status'))
                                <div class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded-lg animate-fade-up">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-yellow-500 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                        </svg>
                                        <p class="text-yellow-700 font-semibold">{{ session('status') }}</p>
                                    </div>
                                </div>
                            @endif

                            <!-- Form -->
                            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                                @csrf
                                
                                <!-- Email -->
                                <div class="animate-fade-up-delay-1">
                                    <label for="email" class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-yellow-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                        </svg>
                                        Adresse Email
                                    </label>
                                    <div class="input-wrapper">
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                                </svg>
                                            </div>
                                            <input 
                                                id="email"
                                                name="email"
                                                type="email"
                                                value="{{ old('email') }}"
                                                required
                                                autofocus
                                                autocomplete="email"
                                                placeholder="Votre adresse email"
                                                class="input-field w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:border-yellow-500 focus:bg-white font-medium @error('email') border-red-500 @enderror"
                                            />
                                        </div>
                                    </div>
                                    @error('email')
                                        <p class="mt-2 text-sm text-red-600 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                                            </svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <!-- Password -->
                                <div class="animate-fade-up-delay-2">
                                    <label for="password" class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-yellow-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                                        </svg>
                                        Mot de passe
                                    </label>
                                    <div class="input-wrapper">
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                </svg>
                                            </div>
                                            <input 
                                                id="password"
                                                name="password"
                                                type="password"
                                                required
                                                autocomplete="current-password"
                                                placeholder="Votre mot de passe"
                                                class="input-field w-full pl-12 pr-12 py-4 bg-gray-50 border-2 border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:border-yellow-500 focus:bg-white font-medium @error('password') border-red-500 @enderror"
                                            />
                                            <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-yellow-600 transition-colors">
                                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    @error('password')
                                        <p class="mt-2 text-sm text-red-600 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                                            </svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <!-- Remember & Forgot -->
                                <div class="flex items-center justify-between animate-fade-up-delay-3">
                                    <label class="flex items-center cursor-pointer group">
                                        <div class="relative">
                                            <input 
                                                type="checkbox" 
                                                name="remember"
                                                id="remember"
                                                {{ old('remember') ? 'checked' : '' }}
                                                class="sr-only peer"
                                            >
                                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-yellow-500 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                        </div>
                                        <span class="ml-3 text-sm font-semibold text-gray-700">Se souvenir</span>
                                    </label>
                                    <a href="{{ route('password.request') }}" class="text-sm font-bold text-yellow-600 hover:text-yellow-800 transition-colors flex items-center group">
                                        Mot de passe oublié?
                                        <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>

                                <!-- Submit Button -->
                                <button type="submit" class="btn-gradient w-full py-4 text-white font-black text-lg rounded-xl shadow-xl flex items-center justify-center space-x-2 group animate-fade-up-delay-3">
                                    <span>Se connecter</span>
                                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                    </svg>
                                </button>

                                <!-- Divider -->
                                <div class="relative my-8 animate-fade-up-delay-3">
                                    <div class="absolute inset-0 flex items-center">
                                        <div class="w-full border-t-2 border-gray-200"></div>
                                    </div>
                                    <div class="relative flex justify-center">
                                        <span class="px-4 bg-white text-sm font-bold text-gray-500">OU CONTINUER AVEC</span>
                                    </div>
                                </div>

                                <!-- Social Login -->
                                <div class="grid grid-cols-2 gap-4 animate-fade-up-delay-3">
                                    <a href="{{ route('login.google') }}" class="social-btn relative flex items-center justify-center space-x-2 py-3 border-2 border-gray-200 rounded-xl font-semibold text-gray-700 hover:border-yellow-300">
                                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                                            <path fill="#df7035ff" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                            <path fill="#a86e34ff" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                            <path fill="#be5e38ff" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                        </svg>
                                        <span>Google</span>
                                    </a>
                                    
                                    <a href="{{ route('login.facebook') }}" class="social-btn relative flex items-center justify-center space-x-2 py-3 border-2 border-gray-200 rounded-xl font-semibold text-gray-700 hover:border-yellow-300">
                                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                        </svg>
                                        <span>Facebook</span>
                                    </a>
                                </div>

                                <!-- Sign Up Link -->
                                <div class="text-center pt-6 border-t border-gray-100 animate-fade-up-delay-3">
                                    <p class="text-gray-600 font-medium">
                                        Pas encore de compte?
                                        <a href="{{ route('register') }}" class="font-black bg-gradient-to-r from-yellow-500 to-orange-600 bg-clip-text text-transparent hover:from-yellow-600 hover:to-orange-700 transition-all ml-1 inline-flex items-center group">
                                            Créer un compte
                                            <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                            </svg>
                                        </a>
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="mt-8 text-center animate-fade-up-delay-3">
                <div class="flex flex-wrap justify-center items-center gap-4 text-sm text-gray-600">
                    <a href="{{ route('home') }}" class="font-semibold hover:text-yellow-600 transition-colors flex items-center group">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                        </svg>
                        Accueil
                    </a>
                    <span class="text-gray-300">•</span>
                    <a href="{{ route('pages.support') }}" class="font-semibold hover:text-yellow-600 transition-colors flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                        </svg>
                        Aide
                    </a>
                    <span class="text-gray-300">•</span>
                    <a href="{{ route('pages.terms') }}" class="font-semibold hover:text-yellow-600 transition-colors flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                        Conditions
                    </a>
                    <span class="text-gray-300">•</span>
                    <a href="{{ route('pages.privacy') }}" class="font-semibold hover:text-yellow-600 transition-colors flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                        </svg>
                        Confidentialité
                    </a>
                </div>
                <p class="mt-4 text-sm text-gray-500 font-medium">© {{ date('Y') }} GuinéeMall</p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            this.querySelector('svg').innerHTML = type === 'password' 
                ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>'
                : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
        });

        // Auto-fill email field animation on error
        const emailInput = document.getElementById('email');
        if (emailInput && emailInput.value.trim() !== '') {
            emailInput.parentElement.parentElement.classList.add('focused');
        }

        // Add focused class on input focus
        document.querySelectorAll('.input-field').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.parentElement.classList.remove('focused');
                }
            });
        });
    </script>
</body>
</html>