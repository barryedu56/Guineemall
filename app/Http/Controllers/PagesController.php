<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PagesController extends Controller
{
    /**
     * Affiche la page pour les vendeurs
     */
    public function vendors(): View
    {
        return view('pages.vendors', [
            'pageTitle' => 'Devenir Vendeur - GuinéeMall',
            'pageDescription' => 'Rejoignez la marketplace N°1 en Guinée et développez votre business'
        ]);
    }

    /**
     * Affiche la page des conditions d'utilisation
     */
    public function terms(): View
    {
        return view('pages.terms', [
            'pageTitle' => 'Conditions d\'utilisation - GuinéeMall',
            'pageDescription' => 'Conditions générales d\'utilisation de la plateforme GuinéeMall'
        ]);
    }

    /**
     * Affiche la page de politique de confidentialité
     */
    public function privacy(): View
    {
        return view('pages.privacy', [
            'pageTitle' => 'Politique de Confidentialité - GuinéeMall',
            'pageDescription' => 'Notre engagement pour la protection de vos données personnelles'
        ]);
    }

    /**
     * Affiche la page des cookies
     */
    public function cookies(): View
    {
        return view('pages.cookies', [
            'pageTitle' => 'Politique des Cookies - GuinéeMall',
            'pageDescription' => 'Comment nous utilisons les cookies sur GuinéeMall'
        ]);
    }

    /**
     * Affiche la page des mentions légales
     */
    public function legal(): View
    {
        return view('pages.legal', [
            'pageTitle' => 'Mentions Légales - GuinéeMall',
            'pageDescription' => 'Informations légales sur la société GuinéeMall'
        ]);
    }

    /**
     * Affiche la page sur les paiements sécurisés
     */
    public function securePayments(): View
    {
        return view('pages.secure-payments', [
            'pageTitle' => 'Paiements Sécurisés - GuinéeMall',
            'pageDescription' => 'Comment nous protégeons vos transactions sur GuinéeMall'
        ]);
    }

    /**
     * Affiche la page sur la livraison
     */
    public function delivery(): View
    {
        return view('pages.delivery', [
            'pageTitle' => 'Livraison Rapide - GuinéeMall',
            'pageDescription' => 'Notre service de livraison rapide et fiable en Guinée'
        ]);
    }

    /**
     * Affiche la page sur le support client
     */
    public function support(): View
    {
        return view('pages.support', [
            'pageTitle' => 'Support 24/7 - GuinéeMall',
            'pageDescription' => 'Notre équipe de support disponible 24h/24 et 7j/7'
        ]);
    }

    /**
     * Affiche la page sur les meilleurs prix
     */
    public function bestPrices(): View
    {
        return view('pages.best-prices', [
            'pageTitle' => 'Meilleurs Prix Garantis - GuinéeMall',
            'pageDescription' => 'Comment nous vous garantissons les meilleurs prix du marché'
        ]);
    }

    /**
     * Affiche la page sur les vendeurs vérifiés
     */
    public function verifiedVendors(): View
    {
        return view('pages.verified-vendors', [
            'pageTitle' => 'Vendeurs Vérifiés - GuinéeMall',
            'pageDescription' => 'Notre processus de vérification des vendeurs pour votre sécurité'
        ]);
    }

    /**
     * Affiche la page sur la politique de retour
     */
    public function returns(): View
    {
        return view('pages.returns', [
            'pageTitle' => 'Politique de Retour - GuinéeMall',
            'pageDescription' => 'Notre politique de retour simple et satisfait ou remboursé'
        ]);
    }
}
