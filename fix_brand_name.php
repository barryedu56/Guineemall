<?php

echo "🔧 CORRECTION SYSTÉMATIQUE DU NOM DE MARQUE\n";
echo "=============================================\n\n";

// Liste des fichiers à corriger
$filesToFix = [
    'resources/views/welcome.blade.php',
    'resources/views/welcome-improved.blade.php', 
    'resources/views/welcome-backup.blade.php',
    'resources/views/components/brand-name.blade.php'
];

foreach ($filesToFix as $file) {
    if (!file_exists($file)) {
        echo "❌ Fichier non trouvé: {$file}\n";
        continue;
    }
    
    echo "📋 Traitement du fichier: {$file}\n";
    
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Corrections systématiques
    $corrections = [
        // Titres et headers
        '/GuinéeMall/' => '{{ app_name() }}',
        
        // Dans les titres de page (avec tiret)
        '/- GuinéeMall/' => '- {{ app_name() }}',
        
        // Dans les phrases contextuelles
        '/sur GuinéeMall/' => 'sur {{ app_name() }}',
        '/avec GuinéeMall/' => 'avec {{ app_name() }}',
        '/dans GuinéeMall/' => 'dans {{ app_name() }}',
        '/pour GuinéeMall/' => 'pour {{ app_name() }}',
        '/de GuinéeMall/' => 'de {{ app_name() }}',
        '/votre GuinéeMall/' => 'votre {{ app_name() }}',
        '/plateforme GuinéeMall/' => 'plateforme {{ app_name() }}',
        
        // Cas spéciaux
        '/Pourquoi choisir GuinéeMall/' => 'Pourquoi choisir {{ app_name() }}',
        '/Pourquoi GUINÉEMALL/' => 'Pourquoi {{ app_name() }}',
        '/POURQUOI GUINÉEMALL/' => 'POURQUOI {{ app_name() }}',
        
        // Dans les attributs alt
        '/sur GuinéeMall"/' => 'sur {{ app_name() }}"',
        
        // Dans les copyrights
        '/© .* GuinéeMall/' => '© {{ date(\'Y\') }} {{ app_name() }}',
        '/©.*GuinéeMall/' => '© {{ date(\'Y\') }} {{ app_name() }}',
        
        // Config directes (remplacer par helper)
        "/config\\('app\\.name', 'GuinéeMall'\\)/" => 'app_name()',
    ];
    
    foreach ($corrections as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    // Cas spécifiques pour le HTML avec logo
    $content = preg_replace('/Guinée<span class="[^"]*">Mall<\\/span>/', '{!! app_name(\'public\', \'logo\') !!}', $content);
    
    // Sauvegarder les changements
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "✅ Fichier corrigé avec succès\n";
    } else {
        echo "ℹ️  Aucune correction nécessaire\n";
    }
    
    echo "\n";
}

echo "🎉 CORRECTION TERMINÉE !\n";
echo "========================\n\n";

echo "📝 RÉCAPITULATIF DES CORRECTIONS:\n";
echo "• ✅ Remplacement de 'GuinéeMall' par {{ app_name() }}\n";
echo "• ✅ Remplacement du HTML par {!! app_name('public', 'logo') !!}\n";
echo "• ✅ Remplacement des config directes par le helper\n";
echo "• ✅ Standardisation de l'affichage du nom\n\n";

echo "🚀 Le nom de marque est maintenant centralisé !\n";
echo "   Testez l'affichage sur toutes les pages\n";
