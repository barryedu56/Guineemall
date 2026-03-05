<?php

namespace App\Services\Vendeur;

use App\Models\User;
use App\Models\Vendor;
use App\Models\Order;

class ProgressionService
{
    /**
     * Obtenir la progression complète du vendeur
     */
    public static function getProgression(User $user): array
    {
        $vendor = $user->vendor;
        
        if (!$vendor) {
            return [
                'current_step' => 0,
                'total_steps' => 5,
                'percentage' => 0,
                'steps' => self::getStepsDefinition(),
                'next_action' => [
                    'text' => 'Créer ma boutique',
                    'url' => route('vendeur.profile.create'),
                    'icon' => 'fas fa-rocket'
                ],
                'message' => '🚀 Commencez par créer votre boutique pour vendre sur GuinéeMall'
            ];
        }

        $steps = self::getStepsDefinition();
        $completedSteps = 0;
        $currentStep = 0;

        // Étape 1: Créer boutique
        if ($vendor) {
            $completedSteps++;
            $currentStep = 1;
            $steps[0]['completed'] = true;
            $steps[0]['status'] = 'completed';
        }

        // Étape 2: Compléter infos
        if ($vendor->description && $vendor->phone && $vendor->address) {
            $completedSteps++;
            $currentStep = 2;
            $steps[1]['completed'] = true;
            $steps[1]['status'] = 'completed';
        } elseif ($vendor) {
            $steps[1]['status'] = 'current';
        }

        // Étape 3: Ajouter produits
        $productCount = $vendor->products()->count();
        if ($productCount > 0) {
            $completedSteps++;
            $currentStep = 3;
            $steps[2]['completed'] = true;
            $steps[2]['status'] = 'completed';
            $steps[2]['details'] = "{$productCount} produit(s)";
        } elseif ($vendor) {
            $steps[2]['status'] = 'current';
        }

        // Étape 4: Activer produits
        $activeProductsCount = $vendor->products()->where('status', 'active')->count();
        if ($activeProductsCount > 0 && $productCount > 0) {
            $completedSteps++;
            $currentStep = 4;
            $steps[3]['completed'] = true;
            $steps[3]['status'] = 'completed';
            $steps[3]['details'] = "{$activeProductsCount} activé(s)";
        } elseif ($productCount > 0) {
            $steps[3]['status'] = 'current';
            $steps[3]['details'] = "0 activé(s)";
        }

        // Étape 5: Première vente
        $completedOrdersCount = $vendor->vendorOrders()->where('status', 'delivered')->count();
        if ($completedOrdersCount > 0) {
            $completedSteps++;
            $currentStep = 5;
            $steps[4]['completed'] = true;
            $steps[4]['status'] = 'completed';
            $steps[4]['details'] = "{$completedOrdersCount} vente(s)";
        } elseif ($activeProductsCount > 0) {
            $steps[4]['status'] = 'current';
        }

        $percentage = ($completedSteps / 5) * 100;
        
        // Déterminer la prochaine action
        $nextAction = self::getNextAction($vendor, $currentStep, $productCount, $activeProductsCount, $completedOrdersCount);
        $message = self::getMotivationalMessage($currentStep, $completedSteps, $productCount, $activeProductsCount, $completedOrdersCount);

        return [
            'current_step' => $currentStep,
            'completed_steps' => $completedSteps,
            'total_steps' => 5,
            'percentage' => $percentage,
            'steps' => $steps,
            'next_action' => $nextAction,
            'message' => $message,
            'stats' => [
                'products_count' => $productCount,
                'active_products_count' => $activeProductsCount,
                'completed_orders_count' => $completedOrdersCount,
                'pending_orders_count' => $vendor->vendorOrders()->where('status', 'pending')->count()
            ]
        ];
    }

    /**
     * Définition des étapes
     */
    private static function getStepsDefinition(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Créer boutique',
                'icon' => 'fas fa-store',
                'description' => 'Enregistrez votre boutique',
                'completed' => false,
                'status' => 'pending'
            ],
            [
                'id' => 2,
                'title' => 'Compléter infos',
                'icon' => 'fas fa-edit',
                'description' => 'Description, contact, etc.',
                'completed' => false,
                'status' => 'pending'
            ],
            [
                'id' => 3,
                'title' => 'Ajouter produits',
                'icon' => 'fas fa-box',
                'description' => 'Vos premiers articles',
                'completed' => false,
                'status' => 'pending',
                'details' => null
            ],
            [
                'id' => 4,
                'title' => 'Activer produits',
                'icon' => 'fas fa-power-off',
                'description' => 'Rendez-les visibles',
                'completed' => false,
                'status' => 'pending',
                'details' => null
            ],
            [
                'id' => 5,
                'title' => 'Première vente',
                'icon' => 'fas fa-shopping-cart',
                'description' => 'Commencez à vendre',
                'completed' => false,
                'status' => 'pending',
                'details' => null
            ]
        ];
    }

    /**
     * Obtenir la prochaine action suggérée
     */
    private static function getNextAction(Vendor $vendor, int $currentStep, int $productCount, int $activeProductsCount, int $completedOrdersCount): array
    {
        if (!$vendor) {
            return [
                'text' => 'Créer ma boutique',
                'url' => route('vendeur.profile.create'),
                'icon' => 'fas fa-rocket'
            ];
        }

        if (!$vendor->description || !$vendor->phone || !$vendor->address) {
            return [
                'text' => 'Compléter mon profil',
                'url' => route('vendeur.profile.edit'),
                'icon' => 'fas fa-edit'
            ];
        }

        if ($productCount === 0) {
            return [
                'text' => 'Ajouter mon premier produit',
                'url' => route('vendeur.products.create'),
                'icon' => 'fas fa-plus'
            ];
        }

        if ($activeProductsCount === 0) {
            return [
                'text' => 'Activer mes produits',
                'url' => route('vendeur.products.index'),
                'icon' => 'fas fa-power-off'
            ];
        }

        if ($completedOrdersCount === 0) {
            return [
                'text' => 'Partager ma boutique',
                'url' => route('vendeur.profile.index'),
                'icon' => 'fas fa-share-alt'
            ];
        }

        return [
            'text' => 'Voir mes ventes',
            'url' => route('vendeur.orders.index'),
            'icon' => 'fas fa-chart-line'
        ];
    }

    /**
     * Obtenir le message motivant
     */
    private static function getMotivationalMessage(int $currentStep, int $completedSteps, int $productCount, int $activeProductsCount, int $completedOrdersCount): string
    {
        if ($completedOrdersCount > 0) {
            return "🎉 Félicitations ! Vous avez réalisé {$completedOrdersCount} vente(s) ! Continuez comme ça !";
        }

        if ($activeProductsCount > 0) {
            $remaining = 5 - $completedSteps;
            return "🚀 Excellent ! Vous êtes à {$remaining} étape(s) de votre première vente !";
        }

        if ($productCount > 0) {
            return "💪 Super ! Activez vos produits pour commencer à vendre";
        }

        if ($currentStep >= 2) {
            return "📦 Continuez ! Ajoutez vos produits pour commencer à vendre";
        }

        return "🚀 Commencez par créer votre boutique pour vendre sur GuinéeMall";
    }
}
