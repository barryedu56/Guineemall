<?php

namespace App\Http\Requests\Vendeur;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->role === 'vendeur' && auth()->user()->vendor;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'price' => 'required|numeric|min:0|max:999999.99',
            'stock' => 'required|integer|min:0|max:99999',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|max:2048',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du produit est obligatoire.',
            'name.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'description.required' => 'La description est obligatoire.',
            'description.max' => 'La description ne doit pas dépasser 2000 caractères.',
            'price.required' => 'Le prix est obligatoire.',
            'price.numeric' => 'Le prix doit être un nombre.',
            'price.min' => 'Le prix doit être supérieur ou égal à 0.',
            'stock.required' => 'Le stock est obligatoire.',
            'stock.integer' => 'Le stock doit être un nombre entier.',
            'stock.min' => 'Le stock doit être supérieur ou égal à 0.',
            'category_id.required' => 'La catégorie est obligatoire.',
            'category_id.exists' => 'La catégorie sélectionnée n\'existe pas.',
            'image.image' => 'Le fichier doit être une image valide (JPEG, PNG, GIF).',
            'image.max' => 'L\'image ne doit pas dépasser 2 Mo.',
            'status.in' => 'Le statut doit être actif ou inactif.',
        ];
    }
}
