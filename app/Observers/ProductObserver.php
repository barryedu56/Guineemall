<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductObserver
{
    public function creating(Product $product): void
    {
        if (! $product->slug) {
            $product->slug = Str::slug($product->name) . '-' . time();
        }
    }

    public function deleting(Product $product): void
    {
        if (method_exists($product, 'deleteImage')) {
            $product->deleteImage();
        }
    }
}
