<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur - GuinéeMall</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="text-red-500 mb-4">
                <i class="fas fa-exclamation-triangle text-6xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Erreur technique</h1>
            <p class="text-gray-600 mb-6">
                Une erreur technique est survenue. Nos équipes en sont informées et travaillent à résoudre le problème.
            </p>
            <div class="space-y-3">
                <a href="{{ url('/') }}" class="bg-yellow-600 text-white px-6 py-2 rounded-lg hover:bg-yellow-700 transition-colors inline-block">
                    <i class="fas fa-home mr-2"></i>
                    Retour à l'accueil
                </a>
                <br>
                <a href="javascript:history.back()" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Page précédente
                </a>
            </div>
            <div class="mt-6 text-sm text-gray-500">
                <p>Code d'erreur: 500</p>
                <p class="mt-2">Si le problème persiste, contactez notre support.</p>
            </div>
        </div>
    </div>
</body>
</html>
