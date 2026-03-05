<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Trait centralisé pour la gestion des images dans GuinéeMall
 * 
 * Ce trait fournit une interface unifiée pour:
 * - Upload d'images
 * - Stockage dans des dossiers organisés
 * - Génération d'URL publiques
 * - Suppression automatique des anciennes images
 * - Fallbacks sécurisés
 * 
 * @package App\Traits
 */
trait HasImage
{
    /**
     * Obtenir l'URL publique de l'image (mode strict)
     *
     * @return string|null URL complète si le fichier existe, null sinon
     */
    public function getImageUrlAttribute(): ?string
    {
        $field = $this->getImageField();
        $path = $this->$field;

        // 1. Si aucune image en base → null
        if (!$path) {
            return null;
        }

        // 2. URLs externes (http/https) → retour direct
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        // 3. Vérifier si le fichier existe réellement dans storage/app/public
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        // 4. Fallback local si stockage public non lié (uploads/* dans /public)
        if (is_file(public_path($path))) {
            return asset($path);
        }

        // 5. Fichier manquant → null
        return null;
    }

    /**
     * Stocker une nouvelle image et supprimer l'ancienne
     * 
     * @param \Illuminate\Http\UploadedFile|null $file Fichier uploadé
     * @return string|null Chemin relatif du fichier stocké
     */
    public function storeImage($file): ?string
    {
        if (!$file) {
            return null;
        }

        // 1. Supprimer l'ancienne image si elle existe
        $this->deleteImage();

        // 2. Stocker la nouvelle image dans le bon dossier
        $path = $this->storeInPublicDisk($file);

        // 3. Mettre à jour le champ en base
        $this->{$this->getImageField()} = $path;
        $this->save();

        return $path;
    }

    /**
     * Supprimer l'image actuelle du disque
     * 
     * @return void
     */
    public function deleteImage(): void
    {
        $field = $this->getImageField();
        $path = $this->$field;

        if (!$path) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $publicPath = public_path($path);
        if (is_file($publicPath)) {
            @unlink($publicPath);
        }
    }

    /**
     * Stocker l'image sur le disque public ou dans /public/uploads
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string Chemin relatif stocké en base
     */
    protected function storeInPublicDisk($file): string
    {
        if ($this->hasPublicStorageLink()) {
            return $file->store($this->getImageStoragePath(), 'public');
        }

        return $this->storeInPublicUploads($file);
    }

    /**
     * Stocker l'image dans le dossier public (fallback)
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string Chemin relatif stocké en base
     */
    protected function storeInPublicUploads($file): string
    {
        $directory = trim($this->getImageStoragePath(), '/');
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = Str::uuid()->toString() . '.' . $extension;
        $relativePath = $directory . '/' . $filename;
        $destination = public_path($directory);

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $file->move($destination, $filename);

        return $relativePath;
    }

    /**
     * Vérifier si le lien public/storage existe
     */
    protected function hasPublicStorageLink(): bool
    {
        $publicStorage = public_path('storage');

        return is_dir($publicStorage) || is_link($publicStorage);
    }

    /**
     * Obtenir le nom du champ image pour ce modèle
     * DOIT être surchargé dans chaque modèle
     * 
     * @return string Nom du champ (image, logo, avatar, etc.)
     */
    protected function getImageField(): string
    {
        // Par défaut: 'image'
        // À surcharger dans Vendor::class pour retourner 'logo'
        // À surcharger dans User::class pour retourner 'avatar'
        return 'image';
    }

    /**
     * Obtenir le chemin de stockage pour ce type d'entité
     * DOIT être surchargé dans chaque modèle
     * 
     * @return string Chemin relatif (uploads/products, uploads/vendors, etc.)
     */
    protected function getImageStoragePath(): string
    {
        // Par défaut: 'uploads'
        // À surcharger pour spécifier le sous-dossier
        return 'uploads';
    }

    /**
     * Obtenir l'URL de l'image par défaut (DÉSACTIVÉ - MODE STRICT)
     * 
     * @return void Cette méthode ne doit plus être utilisée
     */
    protected function getDefaultImageUrl(): void
    {
        // MODE STRICT: Aucune image par défaut autorisée
        // Cette méthode est conservée pour compatibilité mais ne retourne rien
        throw new \Exception('Les images par défaut sont désactivées en mode strict. Utilisez uniquement les images uploadées.');
    }
}
