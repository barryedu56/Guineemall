<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\Category;
use App\Models\Cart;
use App\Services\Client\CartService;
use App\Exceptions\StockException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CartService $cartService;
    protected User $client;
    protected Vendor $vendor;
    protected Category $category;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cartService = app(CartService::class);
        
        $this->client = User::factory()->create(['role' => 'client']);
        $this->vendor = Vendor::factory()->create();
        $this->category = Category::factory()->create();
        
        $this->product = Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'stock' => 10,
            'price' => 25000,
            'status' => 'active'
        ]);
    }

    /** @test */
    public function it_adds_product_to_cart_successfully()
    {
        $cartItem = $this->cartService->addToCart(
            $this->client->id,
            $this->product->id,
            3
        );

        $this->assertInstanceOf(Cart::class, $cartItem);
        $this->assertEquals($this->client->id, $cartItem->user_id);
        $this->assertEquals($this->product->id, $cartItem->product_id);
        $this->assertEquals(3, $cartItem->quantity);
    }

    /** @test */
    public function it_accumulates_quantity_when_product_already_in_cart()
    {
        // Ajouter une première fois
        $this->cartService->addToCart($this->client->id, $this->product->id, 2);
        
        // Ajouter une seconde fois
        $cartItem = $this->cartService->addToCart($this->client->id, $this->product->id, 3);

        $this->assertEquals(5, $cartItem->quantity); // 2 + 3
    }

    /** @test */
    public function it_throws_exception_when_insufficient_stock()
    {
        $this->expectException(StockException::class);
        $this->expectExceptionMessage("Quantité demandée non disponible");

        $this->cartService->addToCart($this->client->id, $this->product->id, 15);
    }

    /** @test */
    public function it_throws_exception_when_product_inactive()
    {
        $this->product->update(['status' => 'inactive']);

        $this->expectException(StockException::class);
        $this->expectExceptionMessage("n'est pas disponible");

        $this->cartService->addToCart($this->client->id, $this->product->id, 1);
    }

    /** @test */
    public function it_calculates_cart_total_correctly()
    {
        // Créer un deuxième produit
        $product2 = Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'stock' => 5,
            'price' => 15000,
            'status' => 'active'
        ]);

        // Ajouter les produits au panier
        $this->cartService->addToCart($this->client->id, $this->product->id, 2); // 2 * 25000 = 50000
        $this->cartService->addToCart($this->client->id, $product2->id, 3);   // 3 * 15000 = 45000

        $total = $this->cartService->getCartTotal($this->client->id);

        $this->assertEquals(95000, $total); // 50000 + 45000
    }

    /** @test */
    public function it_updates_cart_item_quantity()
    {
        // Ajouter un produit au panier
        $cartItem = $this->cartService->addToCart($this->client->id, $this->product->id, 2);

        // Mettre à jour la quantité
        $updatedItem = $this->cartService->updateCartItem(
            $this->client->id,
            $cartItem->id,
            5
        );

        $this->assertEquals(5, $updatedItem->quantity);
    }

    /** @test */
    public function it_removes_item_when_quantity_set_to_zero()
    {
        $cartItem = $this->cartService->addToCart($this->client->id, $this->product->id, 2);

        $this->expectException(StockException::class);
        $this->expectExceptionMessage("Article supprimé du panier");

        $this->cartService->updateCartItem($this->client->id, $cartItem->id, 0);

        // Vérifier que l'article a été supprimé
        $this->assertDatabaseMissing('carts', [
            'id' => $cartItem->id
        ]);
    }

    /** @test */
    public function it_prevents_unauthorized_cart_access()
    {
        $otherClient = User::factory()->create(['role' => 'client']);
        
        $cartItem = $this->cartService->addToCart($otherClient->id, $this->product->id, 1);

        $this->expectException(StockException::class);
        $this->expectExceptionMessage("Non autorisé");

        $this->cartService->updateCartItem($this->client->id, $cartItem->id, 2);
    }
}
