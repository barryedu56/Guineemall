@extends('client.layout')

@section('title', 'Contactez-nous - GuinéeMall')

@section('content')
<div class="min-h-screen bg-white">
    <!-- Header Hero -->
    <div class="bg-gradient-to-r from-yellow-500 to-orange-500 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-headset text-4xl"></i>
                </div>
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Contactez l'équipe GuinéeMall</h1>
                <p class="text-xl text-yellow-100 max-w-2xl mx-auto">
                    Notre équipe locale est disponible pour vous accompagner à chaque étape de votre expérience d'achat.
                </p>
                <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                    <div class="flex items-center justify-center">
                        <i class="fas fa-clock mr-2"></i>
                        <span>Lun-Ven: 8h-18h</span>
                    </div>
                    <div class="flex items-center justify-center">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        <span>Basé en Guinée</span>
                    </div>
                    <div class="flex items-center justify-center">
                        <i class="fas fa-bolt mr-2"></i>
                        <span>Réponse sous 24h</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenu Principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Colonne Gauche: Informations de Contact -->
            <div class="space-y-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-info-circle text-yellow-600 mr-3"></i>
                        Moyens de contact
                    </h2>
                    
                    <!-- Cartes de Contact -->
                    <div class="space-y-4">
                        <!-- Email -->
                        <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow">
                            <div class="flex items-start">
                                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                    <i class="fas fa-envelope text-yellow-600 text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 mb-1">Email Support</h3>
                                    <p class="text-yellow-600 font-medium mb-2">support@guineemall.com</p>
                                    <p class="text-sm text-gray-600">
                                        Pour toute question technique, problème de commande ou information générale.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Téléphone -->
                        <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow">
                            <div class="flex items-start">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                    <i class="fas fa-phone text-blue-600 text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 mb-1">Téléphone</h3>
                                    <p class="text-blue-600 font-medium mb-2">+224 622 12 34 56</p>
                                    <p class="text-sm text-gray-600">
                                        Assistance téléphonique du lundi au vendredi, 8h à 18h.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- WhatsApp -->
                        <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow">
                            <div class="flex items-start">
                                <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                    <i class="fab fa-whatsapp text-white text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 mb-1">WhatsApp</h3>
                                    <p class="text-orange-600 font-medium mb-2">+224 622 12 34 57</p>
                                    <p class="text-sm text-gray-600">
                                        Support rapide via WhatsApp pour questions urgentes et suivi de commandes.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Adresse -->
                        <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow">
                            <div class="flex items-start">
                                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                    <i class="fas fa-map-marker-alt text-yellow-600 text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 mb-1">Adresse</h3>
                                    <p class="text-yellow-600 font-medium mb-2">Conakry, Guinée</p>
                                    <p class="text-sm text-gray-600">
                                        Siège social à Conakry. Service client disponible dans toute la Guinée.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message de Confiance -->
                <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl p-6 border border-yellow-200">
                    <div class="flex items-center mb-3">
                        <i class="fas fa-shield-alt text-yellow-600 text-xl mr-3"></i>
                        <h3 class="font-semibold text-gray-900">Notre engagement</h3>
                    </div>
                    <p class="text-gray-700 leading-relaxed">
                        Notre équipe est disponible pour vous accompagner à chaque étape. 
                        Nous sommes fiers d'offrir un service client local, réactif et compréhensif 
                        pour répondre à tous vos besoins sur la marketplace guinéenne.
                    </p>
                </div>
            </div>

            <!-- Colonne Droite: Formulaire de Contact -->
            <div class="space-y-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-paper-plane text-yellow-600 mr-3"></i>
                        Envoyez-nous un message
                    </h2>
                    
                    <div class="bg-white rounded-xl p-8 shadow-lg border border-gray-100">
                        <form class="space-y-6">
                            <!-- Nom -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nom complet
                                </label>
                                <input type="text" 
                                       id="name" 
                                       name="name" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all"
                                       placeholder="Votre nom et prénom">
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Email
                                </label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all"
                                       placeholder="votre.email@example.com">
                            </div>

                            <!-- Sujet -->
                            <div>
                                <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                                    Sujet
                                </label>
                                <select id="subject" 
                                        name="subject" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                                    <option value="">Choisissez un sujet</option>
                                    <option value="order">Question sur une commande</option>
                                    <option value="account">Problème de compte</option>
                                    <option value="payment">Question de paiement</option>
                                    <option value="technical">Support technique</option>
                                    <option value="vendor">Devenir vendeur</option>
                                    <option value="other">Autre</option>
                                </select>
                            </div>

                            <!-- Message -->
                            <div>
                                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                                    Message
                                </label>
                                <textarea id="message" 
                                          name="message" 
                                          rows="5" 
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all resize-none"
                                          placeholder="Décrivez votre question ou votre problème..."></textarea>
                            </div>

                            <!-- Bouton Envoyer -->
                            <button type="submit" 
                                    class="w-full bg-gradient-to-r from-green-600 to-emerald-600 text-white py-3 px-6 rounded-lg font-semibold hover:from-green-700 hover:to-emerald-700 transition-all transform hover:scale-105 shadow-lg">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Envoyer le message
                            </button>
                        </form>

                        <!-- Info de réponse -->
                        <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                                <div class="text-sm text-blue-800">
                                    <p class="font-semibold mb-1">Délai de réponse</p>
                                    <p>Nous nous engageons à répondre dans les 24 heures ouvrées.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ Rapide -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-question-circle text-blue-600 text-xl mr-3"></i>
                        <h3 class="font-semibold text-gray-900">Questions fréquentes</h3>
                    </div>
                    <p class="text-gray-700 mb-4">
                        Vous trouverez peut-être déjà la réponse à votre question dans notre FAQ.
                    </p>
                    <a href="{{ route('pages.faq') }}" 
                       class="inline-flex items-center text-blue-600 font-medium hover:text-blue-700 transition-colors">
                        Consulter la FAQ
                        <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
