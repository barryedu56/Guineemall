@php
/**
 * Directive Blade pour afficher le nom de marque de manière sécurisée
 * Utilisation: @brand_name ou @brand_name('html')
 */
if (!function_exists('brand_name')) {
    function brand_name($type = 'text') {
        $name = app_name();
        
        if ($type === 'html') {
            return app_name('public', 'logo');
        }
        
        return app_name();
    }
}

/*
Utilisation dans les templates Blade:
    
    1. Texte simple: @brand_name()
       Résultat: GuinéeMall
    
    2. HTML avec style: @brand_name('html')  
       Résultat: GuinéeMall (avec style depuis helper)
    
    3. Configuration: {{ app_name() }}
       Résultat: GuinéeMall (depuis le helper centralisé)
*/
@endphp
