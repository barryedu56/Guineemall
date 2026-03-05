<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\Category;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\VendorOrder;
use App\Services\Client\OrderService;
use App\Exceptions\StockException;
use App\Exceptions\OrderException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderService $orderService;
    protected User $client;
    protected Vendor $vendor;
    protected Category $category;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orderService = app(OrderService::class);
        
        // Créer un client
        $this->client = User::factory()->create([
            'role' => 'client',
            'phone' => '+224 622 123 456',
            'delivery_address' => 'Conakry, Kaloum'
        ]);
        
        // Créer un vendeur et sa boutique
        $this->vendor = Vendor::factory()->create();
        
        // Créer une catégorie
        $this->category = Category::factory()->create();
        
        // Créer un produit
        $this->product = Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'stock' => 10,
            'price' => 50000,
            'status' => 'active'
        ]);
    }

    /** @test */
    public function it_creates_order_successfully_with_sufficient_stock()
    {
        // Ajouter un produit au panier
        Cart::create([
            'user_id' => $this->client->id,
            'product_id' => $this->product->id,
            'quantity' => 5
        ]);

        // Créer la commande
        $order = $this->orderService->createOrderFromCart($this->client->id);

        // Vérifications
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($this->client->id, $order->user_id);
        $this->assertEquals(250000, $order->total_amount); // 5 * 50000
        $this->assertEquals('pending', $order->status);

        // Vérifier que le stock a été décrémenté
        $this->product->refresh();
        $this->assertEquals(5, $this->product->stock); // 10 - 5

        // Vérifier que la sous-commande vendeur a été créée
        $this->assertCount(1, $order->vendorOrders);
        $vendorOrder = $order->vendorOrders->first();
        $this->assertEquals($this->vendor->id, $vendorOrder->vendor_id);
        $this->assertEquals(250000, $vendorOrder->total_amount);
        $this->assertEquals('pending', $vendorOrder->status);

        // Vérifier que les lignes de commande ont été créées
        $this->assertCount(1, $order->orderItems);
        $orderItem = $order->orderItems->first();
        $this->assertEquals($this->product->id, $orderItem->product_id);
        $this->assertEquals(5, $orderItem->quantity);
        $this->assertEquals(50000, $orderItem->price);

        // Vérifier que le panier a été vidé
        $this->assertCount(0, Cart::forUser($this->client->id)->get());
    }

    /** @test */
    public function it_throws_stock_exception_when_insufficient_stock()
    {
        // Ajouter plus de produits au panier que le stock disponible
        Cart::create([
            'user_id' => $this->client->id,
            'product_id' => $this->product->id,
            'quantity' => 15 // Plus que les 10 disponibles
        ]);

        $this->expectException(StockException::class);
        $this->expectExceptionMessage("Stock insuffisant");

        $this->orderService->createOrderFromCart($this->client->id);

        // Vérifier que le stock n'a pas été modifié
        $this->product->refresh();
        $this->assertEquals(10, $this->product->stock);
    }

    /** @test */
    public function it_throws_order_exception_when_cart_is_empty()
    {
        $this->expectException(OrderException::class);
        $this->expectExceptionMessage('Votre panier est vide');

        $this->orderService->createOrderFromCart($this->client->id);
    }

    /** @test */
    public function it_cancels_order_and_restores_stock()
    {
        // Créer une commande d'abord
        Cart::create([
            'user_id' => $this->client->id,
            'product_id' => $this->product->id,
            'quantity' => 3
        ]);

        $order = $this->orderService->createOrderFromCart($this->client->id);
        
        // Stock après création: 10 - 3 = 7
        $this->product->refresh();
        $this->assertEquals(7, $this->product->stock);

        // Annuler la commande
        $this->orderService->cancelOrder($order);

        // Vérifications
        $order->refresh();
        $this->assertEquals('cancelled', $order->status);

        // Vérifier que les sous-commandes sont annulées
        foreach ($order->vendorOrders as $vendorOrder) {
            $this->assertEquals('cancelled', $vendorOrder->status);
        }

        // Vérifier que le stock a été restauré
        $this->product->refresh();
        $this->assertEquals(10, $this->product->stock); // 7 + 3 restauré
    }

    /** @test */
    public function it_handles_multi_vendor_orders_correctly()
    {
        // Créer un deuxième vendeur et produit
        $vendor2 = Vendor::factory()->create();
        $product2 = Product::factory()->create([
            'vendor_id' => $vendor2->id,
            'category_id' => $this->category->id,
            'stock' => 5,
            'price' => 30000,
            'status' => 'active'
        ]);

        // Ajouter des produits des deux vendeurs au panier
        Cart::create([
            'user_id' => $this->client->id,
            'product_id' => $this->product->id,
            'quantity' => 2
        ]);

        Cart::create([
            'user_id' => $this->client->id,
            'product_id' => $product2->id,
            'quantity' => 3
        ]);

        // Créer la commande
        $order = $this->orderService->createOrderFromCart($this->client->id);

        // Vérifier qu'il y a 2 sous-commandes (une par vendeur)
        $this->assertCount(2, $order->vendorOrders);

        // Vérifier les montants des sous-commandes
        $vendorOrder1 = $order->vendorOrders->where('vendor_id', $this->vendor->id)->first();
        $vendorOrder2 = $order->vendorOrders->where('vendor_id', $vendor2->id)->first();

        $this->assertEquals(100000, $vendorOrder1->total_amount); // 2 * 50000
        $this->assertEquals(90000, $vendorOrder2->total_amount);  // 3 * 30000

        // Vérifier le montant total de la commande
        $this->assertEquals(190000, $order->total_amount); // 100000 + 90000
    }

    /** @test */
    public function it_prevents_access_to_other_user_orders()
    {
        // Créer un autre client
        $otherClient = User::factory()->create(['role' => 'client']);

        // Créer une commande pour l'autre client
        Cart::create([
            'user_id' => $otherClient->id,
            'product_id' => $this->product->id,
            'quantity' => 1
        ]);

        $otherOrder = $this->orderService->createOrderFromCart($otherClient->id);

        // Tenter d'accéder à la commande d'un autre utilisateur
        $response = $this->actingAs($this->client)
                        ->get(route('client.orders.show', $otherOrder));

        $response->assertStatus(403);
    }

    /** @test */
    public function it_prevents_modification_of_delivered_orders()
    {
        // Créer et marquer une commande comme livrée
        Cart::create([
            'user_id' => $this->client->id,
            'product_id' => $this->product->id,
            'quantity' => 1
        ]);

        $order = $this->orderService->createOrderFromCart($this->client->id);
        $order->update(['status' => 'delivered']);

        // Tenter d'annuler une commande livrée
        $this->expectException(\App\Exceptions\OrderStatusException::class);
        $this->expectExceptionMessage('ne peut plus être annulée');

        $this->orderService->cancelOrder($order);
    }
}
