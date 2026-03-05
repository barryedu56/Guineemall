<?php

/**
 * Helper functions pour GuinéeMall
 * Fonctions utilitaires pour garantir l'affichage correct du nom et des éléments de marque
 */

if (!function_exists('app_name')) {
    /**
     * Retourne le nom de l'application avec l'orthographe correcte
     * @param string $space Espace (vendor, admin, client, public)
     * @param string $type Type (name, logo, welcome)
     * @return string
     */
    function app_name($space = 'public', $type = 'name')
    {
        $config = config('app_name');
        
        switch ($type) {
            case 'logo':
                return $config['spaces'][$space]['header_logo'] ?? $config['name'];
            case 'welcome':
                return $config['spaces'][$space]['welcome_text'] ?? 'Bienvenue sur ' . $config['name'];
            case 'title':
                return $config['spaces'][$space]['title_suffix'] ?? $config['name'];
            default:
                return $config['name'];
        }
    }
}

if (!function_exists('brand_name')) {
    /**
     * Retourne le nom de la marque avec HTML safe
     * @param bool $html Si true, retourne avec balises HTML pour le style
     * @return string
     */
    function brand_name($html = false)
    {
        $name = config('app_name.name', 'GuinéeMall');
        
        if ($html) {
            return 'Guinée<span class="vendor-gradient-text">Mall</span>';
        }
        
        return $name;
    }
}
