<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    /**
     * Liste des catégories
     */
    public function index()
    {
        $categories = Category::latest()->paginate(10);

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * Enregistrement d’une catégorie
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'preset_name' => 'nullable|required_without:custom_name|string|max:255',
            'custom_name' => 'nullable|required_without:preset_name|string|max:255',
            'status'      => 'required|in:active,inactive',
        ]);

        $name = $request->filled('custom_name')
            ? $request->custom_name
            : $request->preset_name;

        $name = trim($name);

        $validated = [
            'name' => $name,
            'status' => $validated['status'],
            'slug' => $this->makeUniqueSlug($name),
        ];

        Category::create($validated);
        Cache::forget('categories_active');
        Cache::forget('categories_with_counts');

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Catégorie créée avec succès');
    }

    /**
     * Formulaire d’édition
     */
    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Mise à jour
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'preset_name' => 'nullable|required_without:custom_name|string|max:255',
            'custom_name' => 'nullable|required_without:preset_name|string|max:255',
            'status'      => 'required|in:active,inactive',
        ]);

        $name = $request->filled('custom_name')
            ? $request->custom_name
            : $request->preset_name;

        $name = trim($name);

        $validated = [
            'name' => $name,
            'status' => $validated['status'],
        ];

        if ($name !== $category->name) {
            $validated['slug'] = $this->makeUniqueSlug($name, $category->id);
        }

        $category->update($validated);
        Cache::forget('categories_active');
        Cache::forget('categories_with_counts');

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Catégorie mise à jour');
    }

    /**
     * Activer / Désactiver une catégorie
     */
    public function toggle(Category $category)
    {
        $category->update([
            'status' => $category->status === 'active' ? 'inactive' : 'active'
        ]);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Statut de la catégorie modifié');
    }

    /**
     * Suppression
     */
    public function destroy(Category $category)
    {
        $category->delete();
        Cache::forget('categories_active');
        Cache::forget('categories_with_counts');

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Catégorie supprimée');
    }

    private function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        if ($baseSlug === '') {
            $baseSlug = 'categorie';
        }
        $slug = $baseSlug;
        $counter = 2;

        while (
            Category::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
