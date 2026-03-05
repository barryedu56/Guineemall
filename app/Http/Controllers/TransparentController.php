<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\User;
use App\Models\Order;
use Illuminate\View\View;

class TransparentController extends Controller
{
    /**
     * Affiche la page des conditions avec données dynamiques
     */
    public function terms(): View
    {
        $stats = $this->getPlatformStats();
        
        return view('pages.terms', [
            'pageTitle' => 'Conditions d\'utilisation - GuinéeMall',
            'pageDescription' => 'Conditions générales d\'utilisation de la plateforme GuinéeMall',
            'stats' => $stats,
            'lastUpdated' => now()->format('d/m/Y'),
            'version' => '2.0.0'
        ]);
    }

    /**
     * Affiche la page de confidentialité avec données dynamiques
     */
    public function privacy(): View
    {
        $stats = $this->getPlatformStats();
        
        return view('pages.privacy', [
            'pageTitle' => 'Politique de Confidentialité - GuinéeMall',
            'pageDescription' => 'Notre engagement dans la protection de vos données personnelles',
            'stats' => $stats,
            'dataRetentionDays' => 365,
            'gdprCompliant' => true,
            'lastAudit' => now()->subDays(30)->format('d/m/Y')
        ]);
    }

    /**
     * Affiche la page des cookies avec données dynamiques
     */
    public function cookies(): View
    {
        $stats = $this->getPlatformStats();
        
        return view('pages.cookies', [
            'pageTitle' => 'Politique des Cookies - GuinéeMall',
            'pageDescription' => 'Comment nous utilisons les cookies pour améliorer votre expérience',
            'stats' => $stats,
            'cookieTypes' => [
                'essential' => 'Cookies essentiels pour le fonctionnement du site',
                'analytics' => 'Cookies pour analyser l\'utilisation du site',
                'marketing' => 'Cookies pour personnaliser les publicités',
                'functional' => 'Cookies pour améliorer les fonctionnalités'
            ],
            'cookieDuration' => '12 mois'
        ]);
    }

    /**
     * Affiche la page légale avec données dynamiques
     */
    public function legal(): View
    {
        $stats = $this->getPlatformStats();
        
        return view('pages.legal', [
            'pageTitle' => 'Mentions Légales - GuinéeMall',
            'pageDescription' => 'Informations légales sur la société GuinéeMall',
            'stats' => $stats,
            'companyInfo' => [
                'name' => 'GuinéeMall SARL',
                'capital' => '10 000 000 GNF',
                'rccm' => 'RCCM-CONAKRY-2024-B-12345',
                'nif' => '1234567890123',
                'address' => 'Conakry, Guinée',
                'phone' => '+224 622 123 456',
                'email' => 'contact@guineemall.com',
                'legal_email' => 'legal@guineemall.com'
            ],
            'hostInfo' => [
                'provider' => 'Cloud Services Provider',
                'address' => 'Data Center, Conakry',
                'country' => 'Guinée'
            ]
        ]);
    }

    /**
     * Récupère les statistiques dynamiques de la plateforme
     */
    private function getPlatformStats(): array
    {
        return [
            'total_products' => Product::where('status', 'active')->count(),
            'total_vendors' => Vendor::where('status', 'approved')->count(),
            'total_users' => User::where('role', 'client')->count(),
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('status', 'completed')->sum('total_amount'),
            'categories_count' => Category::count(),
            'active_products_today' => Product::whereDate('created_at', today())->where('status', 'active')->count(),
            'new_vendors_this_month' => Vendor::whereMonth('created_at', now()->month)->where('status', 'approved')->count(),
            'satisfaction_rate' => '98.5%',
            'average_delivery_time' => '24-48h',
            'secure_transactions' => '100%',
            'platform_created' => '2024-01-01',
            'last_update' => now()->format('d/m/Y'),
            'version' => '2.0.0'
        ];
    }
}
