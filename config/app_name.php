<?php

/**
 * Configuration de l'application GuinéeMall
 * Informations sur la marque et l'application
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Nom de l'application
    |--------------------------------------------------------------------------
    |
    | Nom officiel de l'application avec l'orthographe exacte
    | Attention : l'accent sur "é" doit être préservé
    |
    */
    'name' => 'GuinéeMall',
    
    /*
    |--------------------------------------------------------------------------
    | Tagline et description
    |--------------------------------------------------------------------------
    */
    'tagline' => 'Votre marketplace en Guinée',
    'description' => 'La marketplace N°1 en Guinée pour acheter et vendre en toute confiance',
    
    /*
    |--------------------------------------------------------------------------
    | Informations de contact
    |--------------------------------------------------------------------------
    */
    'contact' => [
        'email' => 'contact@guineemall.com',
        'support' => 'support@guineemall.com',
        'legal' => 'legal@guineemall.com',
        'phone' => '+224 622 123 456',
        'address' => 'Conakry, Guinée',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | URLs et réseaux sociaux
    |--------------------------------------------------------------------------
    */
    'urls' => [
        'website' => 'https://guineemall.com',
        'facebook' => 'https://facebook.com/guineemall',
        'instagram' => 'https://instagram.com/guineemall',
        'twitter' => 'https://twitter.com/guineemall',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuration par espace
    |--------------------------------------------------------------------------
    */
    'spaces' => [
        'public' => [
            'title_suffix' => 'GuinéeMall',
            'header_logo' => 'Guinée<span class="vendor-gradient-text">Mall</span>',
        ],
        'vendor' => [
            'title_suffix' => 'Espace Vendeur - GuinéeMall',
            'header_logo' => '<span class="text-black">Guinée</span><span class="vendor-gradient-text">Mall</span>',
            'welcome_text' => 'Bienvenue sur GuinéeMall !',
        ],
        'admin' => [
            'title_suffix' => 'Administration - GuinéeMall',
            'header_logo' => 'Guinée<span class="admin-gradient-text">Mall</span>',
        ],
        'client' => [
            'title_suffix' => 'Mon Espace - GuinéeMall',
            'header_logo' => 'Guinée<span class="client-gradient-text">Mall</span>',
        ],
    ],
];
