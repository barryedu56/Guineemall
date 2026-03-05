{{-- resources/views/vendeur/profile/password.blade.php --}}
@extends('vendeur.layouts.app')

@section('title', 'Changer le mot de passe - Espace Vendeur')

@section('content')
<div class="max-w-2xl mx-auto">
    {{-- En-tête --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Changer le mot de passe</h1>
        <p class="text-gray-600 mt-1">Mettez à jour votre mot de passe pour sécuriser votre compte</p>
    </div>

    <div class="bg-white shadow rounded-lg">
        <form action="{{ route('vendeur.profile.password.update') }}" method="POST"
              x-data="passwordForm()">
            @csrf
            
            <div class="p-6 space-y-6">
                {{-- Mot de passe actuel --}}
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                        Mot de passe actuel <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="current_password" 
                               name="current_password" 
                               required
                               x-model="currentPassword"
                               class="w-full px-3 py-2 pl-10 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                               placeholder="Entrez votre mot de passe actuel">
                        <i class="fas fa-lock absolute left-3 top-3 text-gray-400"></i>
                        <button type="button" 
                                @click="togglePassword('current_password')"
                                class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
                            <i class="fas" :class="showCurrentPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                    @error('current_password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Nouveau mot de passe --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Nouveau mot de passe <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required
                               minlength="8"
                               x-model="newPassword"
                               @input="checkPasswordStrength()"
                               class="w-full px-3 py-2 pl-10 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                               placeholder="Entrez votre nouveau mot de passe">
                        <i class="fas fa-lock absolute left-3 top-3 text-gray-400"></i>
                        <button type="button" 
                                @click="togglePassword('password')"
                                class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
                            <i class="fas" :class="showNewPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                    
                    {{-- Indicateur de force du mot de passe --}}
                    <div class="mt-2">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-600">Force du mot de passe:</span>
                            <span class="text-sm font-medium" 
                                  :class="passwordStrengthColor" x-text="passwordStrengthText"></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all duration-300" 
                                 :class="passwordStrengthColor.replace('text-', 'bg-')"
                                 :style="`width: ${passwordStrengthPercentage}%`"></div>
                        </div>
                    </div>
                    
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirmation du mot de passe --}}
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                        Confirmer le mot de passe <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               required
                               minlength="8"
                               x-model="passwordConfirmation"
                               @input="checkPasswordMatch()"
                               class="w-full px-3 py-2 pl-10 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                               placeholder="Confirmez votre nouveau mot de passe">
                        <i class="fas fa-lock absolute left-3 top-3 text-gray-400"></i>
                        <button type="button" 
                                @click="togglePassword('password_confirmation')"
                                class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
                            <i class="fas" :class="showConfirmationPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                    
                    {{-- Indicateur de correspondance --}}
                    <div x-show="passwordConfirmation" class="mt-2">
                        <div class="flex items-center space-x-2">
                            <i class="fas" 
                               :class="passwordsMatch ? 'fa-check-circle text-yellow-600' : 'fa-times-circle text-red-600'"></i>
                            <span class="text-sm" 
                                  :class="passwordsMatch ? 'text-yellow-600' : 'text-red-600'"
                                  x-text="passwordsMatch ? 'Les mots de passe correspondent' : 'Les mots de passe ne correspondent pas'"></span>
                        </div>
                    </div>
                    
                    @error('password_confirmation')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Conseils de sécurité -->
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <h4 class="text-sm font-medium text-blue-900 mb-3">
                        <i class="fas fa-shield-alt mr-2"></i>Conseils de sécurité
                    </h4>
                    <ul class="text-sm text-blue-800 space-y-1">
                        <li>• Au moins 8 caractères</li>
                        <li>• Mélange de lettres majuscules et minuscules</li>
                        <li>• Inclure des chiffres</li>
                        <li>• Ajouter des caractères spéciaux (!@#$%^&*)</li>
                        <li>• Éviter les informations personnelles évidentes</li>
                    </ul>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-4 rounded-b-lg">
                <a href="{{ route('vendeur.profile.index') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                    Annuler
                </a>
                <button type="submit" 
                        :disabled="isSubmitting || !passwordsMatch"
                        class="px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!isSubmitting">
                        <i class="fas fa-key mr-2"></i>Changer le mot de passe
                    </span>
                    <span x-show="isSubmitting" class="flex items-center">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Mise à jour...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function passwordForm() {
    return {
        currentPassword: '',
        newPassword: '',
        passwordConfirmation: '',
        isSubmitting: false,
        showCurrentPassword: false,
        showNewPassword: false,
        showConfirmationPassword: false,
        passwordStrength: 0,
        passwordStrengthText: 'Très faible',
        passwordStrengthColor: 'text-red-600',
        passwordStrengthPercentage: 0,
        
        init() {
            this.updatePasswordStrength();
        },
        
        get passwordsMatch() {
            return this.newPassword && this.passwordConfirmation && 
                   this.newPassword === this.passwordConfirmation;
        },
        
        updatePasswordStrength() {
            if (!this.newPassword) {
                this.passwordStrength = 0;
                this.passwordStrengthText = 'Très faible';
                this.passwordStrengthColor = 'text-red-600';
                this.passwordStrengthPercentage = 0;
                return;
            }
            
            let strength = 0;
            
            // Longueur
            if (this.newPassword.length >= 8) strength += 25;
            if (this.newPassword.length >= 12) strength += 25;
            
            // Complexité
            if (/[a-z]/.test(this.newPassword)) strength += 10;
            if (/[A-Z]/.test(this.newPassword)) strength += 10;
            if (/[0-9]/.test(this.newPassword)) strength += 10;
            if (/[^a-zA-Z0-9]/.test(this.newPassword)) strength += 20;
            
            this.passwordStrength = Math.min(strength, 100);
            this.passwordStrengthPercentage = this.passwordStrength;
            
            // Mettre à jour le texte et la couleur
            if (this.passwordStrength < 30) {
                this.passwordStrengthText = 'Très faible';
                this.passwordStrengthColor = 'text-red-600';
            } else if (this.passwordStrength < 50) {
                this.passwordStrengthText = 'Faible';
                this.passwordStrengthColor = 'text-orange-600';
            } else if (this.passwordStrength < 70) {
                this.passwordStrengthText = 'Moyen';
                this.passwordStrengthColor = 'text-yellow-600';
            } else if (this.passwordStrength < 90) {
                this.passwordStrengthText = 'Fort';
                this.passwordStrengthColor = 'text-blue-600';
            } else {
                this.passwordStrengthText = 'Très fort';
                this.passwordStrengthColor = 'text-yellow-600';
            }
        },
        
        togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const isPassword = field.type === 'password';
            
            if (fieldId === 'current_password') {
                this.showCurrentPassword = !isPassword;
            } else if (fieldId === 'password') {
                this.showNewPassword = !isPassword;
            } else if (fieldId === 'password_confirmation') {
                this.showConfirmationPassword = !isPassword;
            }
            
            field.type = isPassword ? 'text' : 'password';
        },
        
        checkPasswordStrength() {
            this.updatePasswordStrength();
        },
        
        checkPasswordMatch() {
            // Force la réactivité d'Alpine.js
            this.$nextTick(() => {});
        }
    };
}

// Fonction pour afficher les notifications
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 transform transition-all duration-300 translate-x-full ${
        type === 'success' ? 'bg-yellow-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
        notification.classList.add('translate-x-0');
    }, 100);
    
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}
</script>
@endpush
