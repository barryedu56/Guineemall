<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - GuinéeMall</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        .gradient-primary {
            background: linear-gradient(135deg, #FFC107 0%, #FFA726 50%, #FF8F00 100%);
        }
        
        .gradient-card {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.05) 0%, rgba(255, 167, 38, 0.1) 100%);
        }
        
        .input-field {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .input-field:focus {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 193, 7, 0.15);
        }
        
        .btn-primary {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(255, 193, 7, 0.35);
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }
        
        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .icon-bounce {
            animation: iconBounce 2s ease-in-out infinite;
        }
        
        @keyframes iconBounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .shine {
            position: relative;
            overflow: hidden;
        }
        
        .shine::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            bottom: -50%;
            left: -50%;
            background: linear-gradient(to bottom, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            animation: shine 3s infinite;
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .circle-decoration {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
        }
        
        .pulse-ring {
            animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse-ring {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.5;
                transform: scale(1.05);
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-yellow-50 via-orange-50 to-yellow-100 min-h-screen">
    
    <div class="min-h-screen flex items-center justify-center p-4 lg:p-8">
        <div class="w-full max-w-7xl flex flex-col lg:flex-row bg-white rounded-3xl shadow-2xl overflow-hidden min-h-[800px]">
            
            <!-- Section gauche - Design moderne -->
            <div class="hidden lg:flex lg:w-5/12 gradient-primary p-12 flex-col justify-between text-white relative overflow-hidden">
                <!-- Décorations de fond -->
                <div class="circle-decoration w-96 h-96 -top-48 -right-48"></div>
                <div class="circle-decoration w-72 h-72 -bottom-36 -left-36"></div>
                <div class="circle-decoration w-48 h-48 top-1/3 -right-24"></div>
                
                <!-- Logo et nom -->
                <div class="flex items-center space-x-4 z-10">
                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-2xl floating shine">
                        <svg class="w-9 h-9 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-3xl font-black tracking-tight">GuinéeMall</span>
                        <p class="text-yellow-200 text-xs font-medium">Marketplace de Guinée</p>
                    </div>
                </div>

                <!-- Contenu central -->
                <div class="z-10 space-y-8">
                    <div>
                        <h2 class="text-6xl font-black leading-tight mb-6">
                            Rejoignez<br/>
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-200 via-white to-orange-100">
                                GuinéeMall
                            </span>
                        </h2>
                        <p class="text-yellow-100 text-lg leading-relaxed max-w-md font-medium">
                            La plateforme de commerce en ligne qui connecte acheteurs et vendeurs à travers toute la Guinée.
                        </p>
                    </div>
                    
                    <!-- Features -->
                    <div class="space-y-4 max-w-md">
                        <div class="flex items-start space-x-3 pulse-ring">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-white">Inscription Gratuite</h4>
                                <p class="text-yellow-200 text-sm">Créez votre compte en quelques secondes</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start space-x-3 pulse-ring">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-white">Paiement Sécurisé</h4>
                                <p class="text-yellow-200 text-sm">Vos transactions sont protégées</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start space-x-3 pulse-ring">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-white">Livraison Rapide</h4>
                                <p class="text-yellow-200 text-sm">Partout en Guinée sous 48h</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-xs text-yellow-200/70 uppercase tracking-widest z-10 flex items-center justify-between">
                    <span>© GuinéeMall 2026</span>
                    <span class="flex items-center space-x-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM8 9a1 1 0 112 0 1 1 0 01-2 0zm5 0a1 1 0 11-2 0 1 1 0 012 0z"/>
                        </svg>
                        <span>Confiance & Sécurité</span>
                    </span>
                </div>
            </div>

            <!-- Section droite - Formulaire -->
            <div class="w-full lg:w-7/12 bg-white p-6 sm:p-8 md:p-12 lg:p-16 flex flex-col justify-center fade-in">
                <div class="w-full max-w-lg mx-auto">
                    
                    <!-- En-tête mobile -->
                    <div class="lg:hidden flex items-center justify-center mb-8">
                        <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                    </div>
                    
                    <div class="mb-10">
                        <h1 class="text-4xl font-black text-gray-900 mb-3 bg-gradient-to-r from-yellow-500 to-orange-600 bg-clip-text text-transparent">
                            Créer un compte
                        </h1>
                        <p class="text-gray-600 font-medium text-lg">
                            Rejoignez des milliers de vendeurs et acheteurs
                        </p>
                    </div>

                    <form method="POST" action="{{ route('register') }}" class="space-y-5">
                        @csrf
                        
                        <!-- Nom complet -->
                        <div class="relative group">
                            <label class="text-sm font-bold text-gray-700 mb-2 block">Nom complet</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400 group-focus-within:text-yellow-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <input 
                                    id="name"
                                    name="name"
                                    type="text" 
                                    value="{{ old('name') }}"
                                    class="input-field block w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl focus:border-yellow-500 focus:ring-4 focus:ring-yellow-100 focus:bg-white transition-all text-gray-900 placeholder-gray-400"
                                    placeholder="Votre nom complet"
                                    required
                                />
                                @error('name')
                                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="relative group">
                            <label class="text-sm font-bold text-gray-700 mb-2 block">Adresse email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400 group-focus-within:text-yellow-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <input 
                                    id="email"
                                    name="email"
                                    type="email" 
                                    value="{{ old('email') }}"
                                    class="input-field block w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl focus:border-yellow-500 focus:ring-4 focus:ring-yellow-100 focus:bg-white transition-all text-gray-900 placeholder-gray-400"
                                    placeholder="Votre adresse email"
                                    required
                                />
                                @error('email')
                                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Type de compte -->
                        <div class="relative group">
                            <label class="text-sm font-bold text-gray-700 mb-2 block">Type de compte</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                    <svg class="h-5 w-5 text-gray-400 group-focus-within:text-yellow-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <select id="role" name="role" class="input-field block w-full pl-12 pr-10 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl focus:border-yellow-500 focus:ring-4 focus:ring-yellow-100 focus:bg-white transition-all text-gray-900 appearance-none cursor-pointer" required>
                                    <option value="" disabled @selected(old('role') === null)>Choisissez un rôle</option>
                                    <option value="client" @selected(old('role') === 'client')>🛒 Client (Acheteur)</option>
                                    <option value="vendeur" @selected(old('role') === 'vendeur')>🏪 Vendeur (Boutique)</option>
                                </select>
                                @error('role')
                                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                @enderror
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Mot de passe -->
                        <div class="relative group">
                            <label class="text-sm font-bold text-gray-700 mb-2 block">Mot de passe</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400 group-focus-within:text-yellow-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <input 
                                    id="password"
                                    name="password"
                                    type="password" 
                                    class="input-field block w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl focus:border-yellow-500 focus:ring-4 focus:ring-yellow-100 focus:bg-white transition-all text-gray-900 placeholder-gray-400"
                                    placeholder="Minimum 8 caractères"
                                    required
                                />
                                @error('password')
                                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Confirmation mot de passe -->
                        <div class="relative group">
                            <label class="text-sm font-bold text-gray-700 mb-2 block">Confirmer le mot de passe</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400 group-focus-within:text-yellow-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                                <input 
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password" 
                                    class="input-field block w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl focus:border-yellow-500 focus:ring-4 focus:ring-yellow-100 focus:bg-white transition-all text-gray-900 placeholder-gray-400"
                                    placeholder="Répéter le mot de passe"
                                    required
                                />
                                @error('password_confirmation')
                                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Bouton d'inscription -->
                        <div class="pt-6">
                            <button type="submit" class="btn-primary w-full bg-gradient-to-r from-yellow-500 via-orange-500 to-yellow-600 hover:from-yellow-600 hover:via-orange-600 hover:to-yellow-700 text-white py-5 rounded-2xl font-bold text-lg shadow-xl flex items-center justify-center space-x-3 group">
                                <span>S'inscrire sur GuinéeMall</span>
                                <svg class="w-6 h-6 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Lien de connexion -->
                        <div class="text-center pt-6 border-t border-gray-100">
                            <p class="text-gray-600 text-base">
                                Déjà membre de GuinéeMall ?
                                <a href="{{ route('login') }}" class="text-yellow-600 font-bold hover:text-orange-600 hover:underline ml-1 inline-flex items-center group">
                                    Connectez-vous
                                    <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>