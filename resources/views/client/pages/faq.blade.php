@extends('client.layout')

@section('title', 'FAQ - Centre d\'aide GuinéeMall')

@section('content')
<div class="min-h-screen bg-white">
    <!-- Header Hero -->
    <div class="bg-gradient-to-r from-yellow-500 to-orange-500 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-question-circle text-4xl"></i>
                </div>
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Centre d'aide</h1>
                <p class="text-xl text-yellow-100 max-w-2xl mx-auto">
                    Trouvez rapidement les réponses à vos questions sur GuinéeMall
                </p>
                <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                    <div class="flex items-center justify-center">
                        <i class="fas fa-book mr-2"></i>
                        <span>50+ Questions</span>
                    </div>
                    <div class="flex items-center justify-center">
                        <i class="fas fa-clock mr-2"></i>
                        <span>Mis à jour régulièrement</span>
                    </div>
                    <div class="flex items-center justify-center">
                        <i class="fas fa-search mr-2"></i>
                        <span>Recherche rapide</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenu Principal -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <!-- Barre de Recherche -->
        <div class="mb-12">
            <div class="relative">
                <input type="text" 
                       id="faqSearch"
                       placeholder="Rechercher une question..."
                       class="w-full px-6 py-4 pr-12 text-lg border border-gray-300 rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all shadow-lg">
                <div class="absolute right-4 top-1/2 transform -translate-y-1/2">
                    <i class="fas fa-search text-gray-400 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- FAQ par Catégories -->
        <div class="space-y-8" x-data="faqManager()">
            <!-- Achats & Commandes -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-yellow-500 to-orange-500 text-white p-6">
                    <div class="flex items-center">
                        <i class="fas fa-shopping-cart text-2xl mr-4"></i>
                        <div>
                            <h2 class="text-2xl font-bold">Achats & Commandes</h2>
                            <p class="text-yellow-100">Tout savoir sur vos achats sur GuinéeMall</p>
                        </div>
                    </div>
                </div>
                
                <div class="divide-y divide-gray-100">
                    <!-- Question 1 -->
                    <div class="faq-item">
                        <button @click="toggleQuestion(1)" 
                                class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <div class="flex items-center">
                                <i class="fas fa-question-circle text-yellow-500 mr-3"></i>
                                <span class="font-medium text-gray-900">Comment passer une commande sur GuinéeMall ?</span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 transition-transform" 
                               :class="{'rotate-180': openQuestions.includes(1)}"></i>
                        </button>
                        <div x-show="openQuestions.includes(1)" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             class="px-6 pb-4">
                            <div class="pl-8 text-gray-600">
                                <p class="mb-3">Passer une commande sur GuinéeMall est simple et rapide :</p>
                                <ol class="list-decimal list-inside space-y-2 mb-3">
                                    <li>Recherchez le produit souhaité dans la barre de recherche</li>
                                    <li>Cliquez sur le produit pour voir les détails</li>
                                    <li>Ajoutez le produit au panier</li>
                                    <li>Continuez vos achats ou passez à la caisse</li>
                                    <li>Remplissez vos informations de livraison</li>
                                    <li>Choisissez votre mode de paiement</li>
                                    <li>Confirmez votre commande</li>
                                </ol>
                                <p class="text-sm text-blue-600">
                                    <i class="fas fa-lightbulb mr-1"></i>
                                    Conseil : Vérifiez bien les informations avant de valider votre commande.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Question 2 -->
                    <div class="faq-item">
                        <button @click="toggleQuestion(2)" 
                                class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <div class="flex items-center">
                                <i class="fas fa-question-circle text-yellow-500 mr-3"></i>
                                <span class="font-medium text-gray-900">Puis-je annuler ma commande ?</span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 transition-transform" 
                               :class="{'rotate-180': openQuestions.includes(2)}"></i>
                        </button>
                        <div x-show="openQuestions.includes(2)" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             class="px-6 pb-4">
                            <div class="pl-8 text-gray-600">
                                <p class="mb-3">Oui, vous pouvez annuler votre commande selon son statut :</p>
                                <ul class="list-disc list-inside space-y-2 mb-3">
                                    <li><strong>En attente</strong> : Annulation immédiate et gratuite</li>
                                    <li><strong>En préparation</strong> : Annulation possible, contactez le vendeur</li>
                                    <li><strong>En cours de livraison</strong> : Annulation difficile, contactez le support</li>
                                    <li><strong>Livrée</strong> : Annulation impossible, utilisez la procédure de retour</li>
                                </ul>
                                <p class="text-sm text-orange-600">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    Important : Vérifiez toujours le statut avant d'annuler.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Question 3 -->
                    <div class="faq-item">
                        <button @click="toggleQuestion(3)" 
                                class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <div class="flex items-center">
                                <i class="fas fa-question-circle text-yellow-500 mr-3"></i>
                                <span class="font-medium text-gray-900">Comment suivre ma commande ?</span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 transition-transform" 
                               :class="{'rotate-180': openQuestions.includes(3)}"></i>
                        </button>
                        <div x-show="openQuestions.includes(3)" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             class="px-6 pb-4">
                            <div class="pl-8 text-gray-600">
                                <p class="mb-3">Suivez votre commande facilement :</p>
                                <ol class="list-decimal list-inside space-y-2 mb-3">
                                    <li>Connectez-vous à votre compte GuinéeMall</li>
                                    <li>Allez dans "Mes commandes"</li>
                                    <li>Cliquez sur la commande que vous souhaitez suivre</li>
                                    <li>Vous verrez le statut en temps réel</li>
                                    <li>Recevez des notifications par email/SMS</li>
                                </ol>
                                <p class="text-sm text-yellow-600">
                                    <i class="fas fa-mobile-alt mr-1"></i>
                                    Astuce : Activez les notifications pour ne rien manquer !
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paiement -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-yellow-500 to-orange-500 text-white p-6">
                    <div class="flex items-center">
                        <i class="fas fa-credit-card text-2xl mr-4"></i>
                        <div>
                            <h2 class="text-2xl font-bold">Paiement</h2>
                            <p class="text-blue-100">Moyens de paiement et sécurité</p>
                        </div>
                    </div>
                </div>
                
                <div class="divide-y divide-gray-100">
                    <!-- Question 4 -->
                    <div class="faq-item">
                        <button @click="toggleQuestion(4)" 
                                class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <div class="flex items-center">
                                <i class="fas fa-question-circle text-blue-500 mr-3"></i>
                                <span class="font-medium text-gray-900">Quels moyens de paiement sont acceptés ?</span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 transition-transform" 
                               :class="{'rotate-180': openQuestions.includes(4)}"></i>
                        </button>
                        <div x-show="openQuestions.includes(4)" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             class="px-6 pb-4">
                            <div class="pl-8 text-gray-600">
                                <p class="mb-3">GuinéeMall accepte plusieurs moyens de paiement sécurisés :</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div class="flex items-center p-3 bg-yellow-50 rounded-lg">
                                        <i class="fas fa-money-bill-wave text-yellow-600 mr-3"></i>
                                        <div>
                                            <strong>Paiement à la livraison</strong>
                                            <p class="text-sm">Espèces lors de la réception</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center p-3 bg-blue-50 rounded-lg">
                                        <i class="fas fa-mobile-alt text-blue-600 mr-3"></i>
                                        <div>
                                            <strong>Mobile Money</strong>
                                            <p class="text-sm">Orange Money, MTN Money</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center p-3 bg-orange-50 rounded-lg">
                                        <i class="fas fa-university text-orange-600 mr-3"></i>
                                        <div>
                                            <strong>Virement bancaire</strong>
                                            <p class="text-sm">Toutes les banques guinéennes</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center p-3 bg-orange-50 rounded-lg">
                                        <i class="fas fa-wifi text-orange-600 mr-3"></i>
                                        <div>
                                            <strong>Carte bancaire</strong>
                                            <p class="text-sm">Visa, Mastercard (bientôt)</p>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-sm text-blue-600">
                                    <i class="fas fa-shield-alt mr-1"></i>
                                    Tous les paiements sont sécurisés et cryptés.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Question 5 -->
                    <div class="faq-item">
                        <button @click="toggleQuestion(5)" 
                                class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <div class="flex items-center">
                                <i class="fas fa-question-circle text-blue-500 mr-3"></i>
                                <span class="font-medium text-gray-900">Le paiement est-il sécurisé ?</span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 transition-transform" 
                               :class="{'rotate-180': openQuestions.includes(5)}"></i>
                        </button>
                        <div x-show="openQuestions.includes(5)" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             class="px-6 pb-4">
                            <div class="pl-8 text-gray-600">
                                <p class="mb-3">Oui, la sécurité de vos paiements est notre priorité :</p>
                                <ul class="list-disc list-inside space-y-2 mb-3">
                                    <li>Connexion HTTPS cryptée (SSL/TLS)</li>
                                    <li>Données bancaires jamais stockées</li>
                                    <li>Partenaires de paiement certifiés</li>
                                    <li>Protection anti-fraude avancée</li>
                                    <li>Garantie remboursement en cas de problème</li>
                                </ul>
                                <div class="bg-yellow-50 p-3 rounded-lg">
                                    <p class="text-yellow-700">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        <strong>100% Sécurisé</strong> - Votre argent est protégé jusqu'à la réception de votre commande.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Livraison -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-500 to-pink-500 text-white p-6">
                    <div class="flex items-center">
                        <i class="fas fa-truck text-2xl mr-4"></i>
                        <div>
                            <h2 class="text-2xl font-bold">Livraison</h2>
                            <p class="text-orange-100">Délais et options de livraison</p>
                        </div>
                    </div>
                </div>
                
                <div class="divide-y divide-gray-100">
                    <!-- Question 6 -->
                    <div class="faq-item">
                        <button @click="toggleQuestion(6)" 
                                class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <div class="flex items-center">
                                <i class="fas fa-question-circle text-orange-500 mr-3"></i>
                                <span class="font-medium text-gray-900">En combien de temps suis-je livré en Guinée ?</span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 transition-transform" 
                               :class="{'rotate-180': openQuestions.includes(6)}"></i>
                        </button>
                        <div x-show="openQuestions.includes(6)" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             class="px-6 pb-4">
                            <div class="pl-8 text-gray-600">
                                <p class="mb-3">Les délais de livraison varient selon votre localisation :</p>
                                <div class="space-y-3 mb-4">
                                    <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                                        <div class="flex items-center">
                                            <i class="fas fa-map-marker-alt text-yellow-600 mr-3"></i>
                                            <span><strong>Conakry</strong></span>
                                        </div>
                                        <span class="text-yellow-600 font-semibold">1-2 jours</span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                        <div class="flex items-center">
                                            <i class="fas fa-city text-blue-600 mr-3"></i>
                                            <span><strong>Grandes villes</strong></span>
                                        </div>
                                        <span class="text-blue-600 font-semibold">2-4 jours</span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg">
                                        <div class="flex items-center">
                                            <i class="fas fa-globe-africa text-orange-600 mr-3"></i>
                                            <span><strong>Intérieur du pays</strong></span>
                                        </div>
                                        <span class="text-orange-600 font-semibold">3-7 jours</span>
                                    </div>
                                </div>
                                <p class="text-sm text-orange-600">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Les délais sont indicatifs et peuvent varier selon les produits et les vendeurs.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Question 7 -->
                    <div class="faq-item">
                        <button @click="toggleQuestion(7)" 
                                class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <div class="flex items-center">
                                <i class="fas fa-question-circle text-orange-500 mr-3"></i>
                                <span class="font-medium text-gray-900">Quels sont les frais de livraison ?</span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 transition-transform" 
                               :class="{'rotate-180': openQuestions.includes(7)}"></i>
                        </button>
                        <div x-show="openQuestions.includes(7)" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             class="px-6 pb-4">
                            <div class="pl-8 text-gray-600">
                                <p class="mb-3">Les frais de livraison dépendent de plusieurs facteurs :</p>
                                <ul class="list-disc list-inside space-y-2 mb-3">
                                    <li><strong>Distance</strong> : Plus la livraison est loin, plus les frais augmentent</li>
                                    <li><strong>Poids/Volume</strong> : Les produits lourds ou volumineux coûtent plus cher</li>
                                    <li><strong>Vendeur</strong> : Chaque vendeur fixe ses propres tarifs</li>
                                    <li><strong>Livraison express</strong> : Option plus rapide mais plus coûteuse</li>
                                </ul>
                                <div class="bg-blue-50 p-3 rounded-lg">
                                    <p class="text-blue-700">
                                        <i class="fas fa-tag mr-2"></i>
                                        <strong>Bon plan</strong> : Profitez souvent de la livraison gratuite pour les commandes de plus de 50,000 GNF !
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Retours & Remboursements -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-orange-500 to-red-500 text-white p-6">
                    <div class="flex items-center">
                        <i class="fas fa-undo text-2xl mr-4"></i>
                        <div>
                            <h2 class="text-2xl font-bold">Retours & Remboursements</h2>
                            <p class="text-orange-100">Politique de retour et remboursement</p>
                        </div>
                    </div>
                </div>
                
                <div class="divide-y divide-gray-100">
                    <!-- Question 8 -->
                    <div class="faq-item">
                        <button @click="toggleQuestion(8)" 
                                class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <div class="flex items-center">
                                <i class="fas fa-question-circle text-orange-500 mr-3"></i>
                                <span class="font-medium text-gray-900">Que faire en cas de problème avec une commande ?</span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 transition-transform" 
                               :class="{'rotate-180': openQuestions.includes(8)}"></i>
                        </button>
                        <div x-show="openQuestions.includes(8)" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             class="px-6 pb-4">
                            <div class="pl-8 text-gray-600">
                                <p class="mb-3">En cas de problème, suivez ces étapes :</p>
                                <ol class="list-decimal list-inside space-y-2 mb-3">
                                    <li><strong>Contactez d'abord le vendeur</strong> pour trouver une solution amiable</li>
                                    <li><strong>Documentez le problème</strong> (photos, description détaillée)</li>
                                    <li><strong>Ouvrez un litige</strong> depuis votre espace client si nécessaire</li>
                                    <li><strong>Attendez la médiation</strong> de l'équipe GuinéeMall</li>
                                    <li><strong>Suivez la résolution</strong> proposée (remboursement, échange, etc.)</li>
                                </ol>
                                <div class="bg-orange-50 p-3 rounded-lg">
                                    <p class="text-orange-700">
                                        <i class="fas fa-headset mr-2"></i>
                                        <strong>Support disponible</strong> : Notre équipe vous assiste à chaque étape.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Question 9 -->
                    <div class="faq-item">
                        <button @click="toggleQuestion(9)" 
                                class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <div class="flex items-center">
                                <i class="fas fa-question-circle text-orange-500 mr-3"></i>
                                <span class="font-medium text-gray-900">Quelle est la politique de retour ?</span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 transition-transform" 
                               :class="{'rotate-180': openQuestions.includes(9)}"></i>
                        </button>
                        <div x-show="openQuestions.includes(9)" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             class="px-6 pb-4">
                            <div class="pl-8 text-gray-600">
                                <p class="mb-3">Notre politique de retour vous protège :</p>
                                <div class="space-y-3 mb-4">
                                    <div class="flex items-start">
                                        <i class="fas fa-check-circle text-yellow-500 mt-1 mr-3"></i>
                                        <div>
                                            <strong>14 jours pour retourner</strong> un produit qui ne vous convient pas
                                        </div>
                                    </div>
                                    <div class="flex items-start">
                                        <i class="fas fa-check-circle text-yellow-500 mt-1 mr-3"></i>
                                        <div>
                                            <strong>Produit neuf et intact</strong> avec emballage d'origine
                                        </div>
                                    </div>
                                    <div class="flex items-start">
                                        <i class="fas fa-check-circle text-yellow-500 mt-1 mr-3"></i>
                                        <div>
                                            <strong>Remboursement complet</strong> ou échange selon votre choix
                                        </div>
                                    </div>
                                    <div class="flex items-start">
                                        <i class="fas fa-exclamation-triangle text-orange-500 mt-1 mr-3"></i>
                                        <div>
                                            <strong>Frais de port</strong> gratuits pour les retours de produits défectueux
                                        </div>
                                    </div>
                                </div>
                                <p class="text-sm text-orange-600">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Certains produits (personnalisés, hygiéniques) ne sont pas retournables.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vendeurs -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-500 to-purple-500 text-white p-6">
                    <div class="flex items-center">
                        <i class="fas fa-store text-2xl mr-4"></i>
                        <div>
                            <h2 class="text-2xl font-bold">Vendeurs</h2>
                            <p class="text-indigo-100">Devenir vendeur sur GuinéeMall</p>
                        </div>
                    </div>
                </div>
                
                <div class="divide-y divide-gray-100">
                    <!-- Question 10 -->
                    <div class="faq-item">
                        <button @click="toggleQuestion(10)" 
                                class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <div class="flex items-center">
                                <i class="fas fa-question-circle text-indigo-500 mr-3"></i>
                                <span class="font-medium text-gray-900">Comment devenir vendeur sur GuinéeMall ?</span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 transition-transform" 
                               :class="{'rotate-180': openQuestions.includes(10)}"></i>
                        </button>
                        <div x-show="openQuestions.includes(10)" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             class="px-6 pb-4">
                            <div class="pl-8 text-gray-600">
                                <p class="mb-3">Devenir vendeur sur GuinéeMall est simple et gratuit :</p>
                                <ol class="list-decimal list-inside space-y-2 mb-3">
                                    <li><strong>Inscrivez-vous</strong> sur GuinéeMall</li>
                                    <li><strong>Complétez votre profil vendeur</strong> (informations, documents)</li>
                                    <li><strong>Ajoutez vos premiers produits</strong> avec photos et descriptions</li>
                                    <li><strong>Définissez vos prix</strong> et conditions de vente</li>
                                    <li><strong>Validez votre boutique</strong> (vérification par notre équipe)</li>
                                    <li><strong>Commencez à vendre</strong> et recevez vos paiements</li>
                                </ol>
                                <div class="bg-indigo-50 p-3 rounded-lg">
                                    <p class="text-indigo-700">
                                        <i class="fas fa-rocket mr-2"></i>
                                        <strong>Avantages</strong> : Accès à des milliers de clients, outils de vente, support dédié.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Question 11 -->
                    <div class="faq-item">
                        <button @click="toggleQuestion(11)" 
                                class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <div class="flex items-center">
                                <i class="fas fa-question-circle text-indigo-500 mr-3"></i>
                                <span class="font-medium text-gray-900">Quels sont les frais pour les vendeurs ?</span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 transition-transform" 
                               :class="{'rotate-180': openQuestions.includes(11)}"></i>
                        </button>
                        <div x-show="openQuestions.includes(11)" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             class="px-6 pb-4">
                            <div class="pl-8 text-gray-600">
                                <p class="mb-3">Nos frais sont transparents et compétitifs :</p>
                                <div class="space-y-3 mb-4">
                                    <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                                        <span><strong>Inscription</strong></span>
                                        <span class="text-yellow-600 font-semibold">Gratuite</span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                        <span><strong>Commission</strong></span>
                                        <span class="text-blue-600 font-semibold">5-10% par vente</span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg">
                                        <span><strong>Mise en avant</strong></span>
                                        <span class="text-orange-600 font-semibold">Optionnel</span>
                                    </div>
                                </div>
                                <p class="text-sm text-indigo-600">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Pas de frais cachés. Vous ne payez que lorsque vous vendez.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Contact -->
        <div class="mt-12 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl p-8 border border-yellow-200">
            <div class="text-center">
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Vous n'avez pas trouvé votre réponse ?</h3>
                <p class="text-gray-600 mb-6">
                    Notre équipe de support est disponible pour vous aider avec toutes vos questions.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('pages.contact') }}" 
                       class="px-6 py-3 bg-yellow-600 text-white rounded-lg font-semibold hover:bg-yellow-700 transition-colors">
                        <i class="fas fa-envelope mr-2"></i>
                        Contacter le support
                    </a>
                    <a href="#" 
                       class="px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        <i class="fab fa-whatsapp mr-2"></i>
                        WhatsApp Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function faqManager() {
    return {
        openQuestions: [],
        
        toggleQuestion(questionId) {
            const index = this.openQuestions.indexOf(questionId);
            if (index > -1) {
                this.openQuestions.splice(index, 1);
            } else {
                this.openQuestions = [questionId]; // Une seule question ouverte à la fois
            }
        }
    }
}
</script>
@endsection
