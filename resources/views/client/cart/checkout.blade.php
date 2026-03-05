@extends('client.layout')

@section('title', 'Finaliser la commande - GuinéeMall')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Finaliser ma commande</h1>

    <form x-data="checkoutForm()" @submit.prevent="submitOrder()" class="space-y-8">
        @csrf
        
        <!-- Section 1: Récapitulatif de la commande -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Récapitulatif de votre commande</h2>
            
            <div class="space-y-4">
                @foreach($cartItems as $item)
                    <div class="flex items-center justify-between py-3 border-b">
                        <div class="flex items-center space-x-4">
                            @if($item->product->image_url)
                                <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}" 
                                     class="w-16 h-16 object-cover rounded" 
                                     loading="lazy"
                                     onerror="this.src='{{ asset('images/default-product.svg') }}'">
                            @else
                                <div class="w-16 h-16 bg-gradient-to-br from-yellow-100 to-orange-100 rounded flex items-center justify-center">
                                    <i class="fas fa-box text-yellow-600"></i>
                                </div>
                            @endif
                            <div>
                                <h3 class="font-semibold">{{ $item->product->name }}</h3>
                                <p class="text-sm text-gray-500">Quantité: {{ $item->quantity }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">{{ number_format($item->subtotal, 0, ',', ' ') }} GNF</p>
                            <p class="text-sm text-gray-500">{{ number_format($item->product->price, 0, ',', ' ') }} GNF/unité</p>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="mt-6 pt-4 border-t">
                <div class="flex justify-between items-center">
                    <span class="text-lg font-semibold">Total:</span>
                    <span class="text-2xl font-bold text-yellow-600">{{ number_format($total, 0, ',', ' ') }} GNF</span>
                </div>
            </div>
        </div>

        <!-- Section 2: Informations de livraison -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Informations de livraison</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nom complet *</label>
                    <input type="text" x-model="form.delivery_name" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                           placeholder="Votre nom complet">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone *</label>
                    <input type="tel" x-model="form.delivery_phone" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                           placeholder="+224 XXX XXX XXX">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Adresse de livraison *</label>
                    <textarea x-model="form.delivery_address" required rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                              placeholder="Adresse complète pour la livraison"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ville *</label>
                    <select x-model="form.delivery_city" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                        <option value="">Sélectionnez une ville</option>
                        <option value="Conakry">Conakry</option>
                        <option value="Kindia">Kindia</option>
                        <option value="Labé">Labé</option>
                        <option value="Kankan">Kankan</option>
                        <option value="N'Zérékoré">N'Zérékoré</option>
                        <option value="Mamou">Mamou</option>
                        <option value="Faranah">Faranah</option>
                        <option value="Boké">Boké</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quartier *</label>
                    <input type="text" x-model="form.delivery_neighborhood" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                           placeholder="Votre quartier">
                </div>
            </div>
        </div>

        <!-- Section 3: Mode de livraison -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Mode de livraison</h2>
            
            <div class="space-y-3">
                <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" x-model="form.delivery_method" value="standard" required
                           class="mr-3 text-yellow-600 focus:ring-yellow-500">
                    <div class="flex-1">
                        <div class="font-medium">Livraison Standard</div>
                        <div class="text-sm text-gray-500">3-5 jours ouvrables</div>
                    </div>
                    <div class="font-semibold">15 000 GNF</div>
                </label>
                
                <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" x-model="form.delivery_method" value="express" required
                           class="mr-3 text-yellow-600 focus:ring-yellow-500">
                    <div class="flex-1">
                        <div class="font-medium">Livraison Express</div>
                        <div class="text-sm text-gray-500">24-48h</div>
                    </div>
                    <div class="font-semibold">30 000 GNF</div>
                </label>
                
                <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" x-model="form.delivery_method" value="pickup" required
                           class="mr-3 text-yellow-600 focus:ring-yellow-500">
                    <div class="flex-1">
                        <div class="font-medium">Retrait sur place</div>
                        <div class="text-sm text-gray-500">À la boutique du vendeur</div>
                    </div>
                    <div class="font-semibold">Gratuit</div>
                </label>
            </div>
        </div>

        <!-- Section 4: Mode de paiement -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Mode de paiement</h2>
            
            <div class="space-y-3">
                <!-- Orange Money -->
                <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" x-model="form.payment_method" value="orange_money" required
                           class="mr-3 text-yellow-600 focus:ring-yellow-500">
                    <div class="flex-1">
                        <div class="font-medium">Orange Money</div>
                        <div class="text-sm text-gray-500">Paiement mobile sécurisé</div>
                    </div>
                    <i class="fas fa-mobile-alt text-orange-500 text-xl"></i>
                </label>
                
                <!-- Formulaire Orange Money -->
                <div x-show="form.payment_method === 'orange_money'" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="bg-orange-50 border border-orange-200 rounded-lg p-4 space-y-3">
                    <h4 class="font-semibold text-orange-800">Détails du paiement Orange Money</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Numéro Orange Money *</label>
                            <input type="tel" x-model="form.orange_money.phone" 
                                   placeholder="+224 XXX XXX XXX"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Code secret *</label>
                            <input type="password" x-model="form.orange_money.code" 
                                   placeholder="Code à 4 chiffres"
                                   maxlength="4"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        </div>
                    </div>
                    <div class="bg-white rounded-lg p-3 border border-orange-200">
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-info-circle text-orange-500 mr-2"></i>
                            Vous recevrez une notification sur votre téléphone pour confirmer le paiement de 
                            <span class="font-bold text-orange-600" x-text="formatPrice(getTotal())"></span>
                        </p>
                    </div>
                    <button type="button"
                            class="w-full bg-orange-200 text-orange-700 py-2 rounded-lg font-semibold cursor-not-allowed opacity-70">
                        Payer avec Orange Money (bientôt)
                    </button>
                </div>

                <!-- MTN Money -->
                <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" x-model="form.payment_method" value="mtn_money" required
                           class="mr-3 text-yellow-600 focus:ring-yellow-500">
                    <div class="flex-1">
                        <div class="font-medium">MTN Money</div>
                        <div class="text-sm text-gray-500">Paiement mobile sécurisé</div>
                    </div>
                    <i class="fas fa-mobile-alt text-yellow-500 text-xl"></i>
                </label>
                
                <!-- Formulaire MTN Money -->
                <div x-show="form.payment_method === 'mtn_money'" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 space-y-3">
                    <h4 class="font-semibold text-yellow-800">Détails du paiement MTN Money</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Numéro MTN Money *</label>
                            <input type="tel" x-model="form.mtn_money.phone" 
                                   placeholder="+224 XXX XXX XXX"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Code PIN *</label>
                            <input type="password" x-model="form.mtn_money.pin" 
                                   placeholder="Code PIN à 4 chiffres"
                                   maxlength="4"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                    </div>
                    <div class="bg-white rounded-lg p-3 border border-yellow-200">
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-info-circle text-yellow-500 mr-2"></i>
                            Vous recevrez une notification sur votre téléphone pour confirmer le paiement de 
                            <span class="font-bold text-yellow-600" x-text="formatPrice(getTotal())"></span>
                        </p>
                    </div>
                </div>
                
                <!-- Paiement à la livraison -->
                <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" x-model="form.payment_method" value="cash" required
                           class="mr-3 text-yellow-600 focus:ring-yellow-500">
                    <div class="flex-1">
                        <div class="font-medium">Paiement à la livraison</div>
                        <div class="text-sm text-gray-500">Espèces à la réception</div>
                    </div>
                    <i class="fas fa-money-bill-wave text-yellow-500 text-xl"></i>
                </label>
                
                <!-- Détails paiement cash -->
                <div x-show="form.payment_method === 'cash'" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 space-y-3">
                    <h4 class="font-semibold text-yellow-800">Paiement à la livraison</h4>
                    <div class="bg-white rounded-lg p-3 border border-yellow-200">
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-check-circle text-yellow-500 mr-2"></i>
                            Paiement en espèces à la réception de votre commande
                        </p>
                        <p class="text-sm text-gray-600 mt-2">
                            <i class="fas fa-info-circle text-yellow-500 mr-2"></i>
                            Montant à payer : 
                            <span class="font-bold text-yellow-600" x-text="formatPrice(getTotal())"></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 5: Instructions supplémentaires -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Instructions supplémentaires</h2>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes pour le livreur (optionnel)</label>
                <textarea x-model="form.notes" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                          placeholder="Instructions spécifiques pour la livraison..."></textarea>
            </div>
        </div>

        <!-- Bouton de validation -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="text-lg">Total à payer:</span>
                <span class="text-2xl font-bold text-yellow-600" x-text="formatPrice(getTotal())"></span>
            </div>
            
            <button type="submit" 
                    :disabled="submitting || !isFormValid()"
                    class="w-full bg-yellow-600 text-white py-4 rounded-lg font-semibold text-lg hover:bg-yellow-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-text="submitting ? 'Traitement en cours...' : 'Confirmer ma commande'"></span>
            </button>
            
            <p class="text-sm text-gray-500 mt-4 text-center">
                En confirmant votre commande, vous acceptez nos conditions générales de vente
            </p>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function checkoutForm() {
        return {
            form: {
                delivery_name: '{{ auth()->user()->name ?? '' }}',
                delivery_phone: '{{ auth()->user()->phone ?? '' }}',
                delivery_address: '{{ auth()->user()->delivery_address ?? '' }}',
                delivery_city: '',
                delivery_neighborhood: '',
                delivery_method: 'standard',
                payment_method: 'orange_money',
                notes: '',
                orange_money: {
                    phone: '',
                    code: ''
                },
                mtn_money: {
                    phone: '',
                    pin: ''
                }
            },
            submitting: false,
            baseTotal: {{ $total }},
            
            getDeliveryCost() {
                const costs = {
                    'standard': 15000,
                    'express': 30000,
                    'pickup': 0
                };
                return costs[this.form.delivery_method] || 15000;
            },
            
            getTotal() {
                return this.baseTotal + this.getDeliveryCost();
            },
            
            formatPrice(price) {
                return new Intl.NumberFormat('fr-GN', {
                    style: 'currency',
                    currency: 'GNF',
                    minimumFractionDigits: 0
                }).format(price).replace('GNF', '').trim() + ' GNF';
            },

            buildPaymentReference() {
                const timestamp = Date.now();

                if (this.form.payment_method === 'orange_money') {
                    const phone = (this.form.orange_money.phone || '').replace(/\s+/g, '');
                    return `OM-${phone.slice(-4)}-${timestamp}`;
                }

                if (this.form.payment_method === 'mtn_money') {
                    const phone = (this.form.mtn_money.phone || '').replace(/\s+/g, '');
                    return `MTN-${phone.slice(-4)}-${timestamp}`;
                }

                return `CASH-${timestamp}`;
            },
            
            isFormValid() {
                // Validation des champs de base
                if (!this.form.delivery_name || 
                    !this.form.delivery_phone || 
                    !this.form.delivery_address || 
                    !this.form.delivery_city || 
                    !this.form.delivery_neighborhood || 
                    !this.form.delivery_method || 
                    !this.form.payment_method) {
                    return false;
                }
                
                // Validation spécifique selon le mode de paiement
                if (this.form.payment_method === 'orange_money') {
                    return this.form.orange_money.phone && this.form.orange_money.code;
                } else if (this.form.payment_method === 'mtn_money') {
                    return this.form.mtn_money.phone && this.form.mtn_money.pin;
                }
                
                return true; // cash n'a pas de champs supplémentaires
            },
            
            submitOrder() {
                if (!this.isFormValid()) {
                    alert('Veuillez remplir tous les champs obligatoires');
                    return;
                }
                
                this.submitting = true;
                
                const orderData = {
                    ...this.form,
                    delivery_cost: this.getDeliveryCost(),
                    total: this.getTotal(),
                    payment_method: this.form.payment_method,
                    payment_reference: this.buildPaymentReference()
                };
                
                fetch('{{ route('client.orders.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(orderData)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Commande passée avec succès! Redirection...');
                        window.location.href = data.redirect || '{{ route('client.orders.index') }}';
                    } else {
                        alert(data.message || 'Erreur lors de la commande');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erreur lors de la commande');
                })
                .finally(() => {
                    this.submitting = false;
                });
            }
        }
    }
</script>
@endpush
@endsection
