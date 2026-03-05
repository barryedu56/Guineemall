<?php

/**
 * Configuration du thème GuinéeMall
 * Palette de couleurs principale
 */

return [
    'colors' => [
        'primary' => '#FFC107',      // Jaune principal
        'primary_hover' => '#FFB300', // Jaune hover
        'secondary' => '#FFA726',    // Orange clair
        'secondary_hover' => '#FF9800', // Orange hover
        'background' => '#F8F9FA',   // Gris clair fond
        'surface' => '#FFFFFF',      // Blanc dominant
        'text' => '#212529',         // Noir doux texte
        'text_muted' => '#6C757D',   // Gris texte secondaire
        'border' => '#E9ECEF',       // Bordure légère
        'success' => '#28A745',      // Succès (vert conservé mais atténué)
        'warning' => '#FFC107',      // Avertissement (jaune)
        'danger' => '#DC3545',       // Danger (rouge)
        'info' => '#17A2B8',         // Info (bleu)
    ],
    
    'shadows' => [
        'card' => '0 2px 12px rgba(0, 0, 0, 0.08)',
        'card_hover' => '0 8px 24px rgba(255, 193, 7, 0.15)',
        'button' => '0 4px 12px rgba(255, 193, 7, 0.25)',
        'button_hover' => '0 6px 16px rgba(255, 167, 38, 0.35)',
    ],
    
    'transitions' => [
        'fast' => '150ms ease-in-out',
        'normal' => '250ms ease-in-out',
        'slow' => '350ms ease-in-out',
    ],
    
    'border_radius' => [
        'sm' => '6px',
        'md' => '10px',
        'lg' => '16px',
        'xl' => '24px',
        'full' => '9999px',
    ],
];
