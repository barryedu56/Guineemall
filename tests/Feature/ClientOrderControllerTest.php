<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\Category;
use App\Models\Cart;
use App\Models\Order;
use App\Services\Client\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $client;
    protected Vendor $vendor;
    protected Category $category;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = User::factory()->create([
            'role' => 'client',
            'phone' => '+224 622 123 456',
            'delivery_address' => 'Conakry, Kaloum'
        ]);
        
        $this->vendor = Vendor::factory()->create();
        $this->category = Category::factory()->create();
        
        $this->product = Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'stock' => 10,
            'price' => 50000,
            'status' => 'active'
        ]);
    }

    /** @test */
    public function client_can_view_their_orders()
    {
        // Créer une commande pour le client
        Cart::create([
            'user_id' => $this->client->id,
            'product_id' => $this->product->id,
            'quantity' => 2
        ]);

        $order = app(OrderService::class)->createOrderFromCart($this->client->id);

        $response = $this->actingAs($this->client)
                        ->get(route('client.orders.index'));

        $response->assertStatus(200);
        $response->assertSee('Commande #' . $order->id);
    }

    /** @test */
    public function client_can_view_their_order_details()
    {
        Cart::create([
            'user_id' => $this->client->id,
            'product_id' => $this->product->id,
            'quantity' => 1
        ]);

        $order = app(OrderService::class)->createOrderFromCart($this->client->id);

        $response = $this->actingAs($this->client)
                        ->get(route('client.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee($this->product->name);
        $response->assertSee('50 000 GNF'); // Prix formaté
    }

    /** @test */
    public function client_cannot_view_other_user_orders()
    {
        $otherClient = User::factory()->create(['role' => 'client']);

        Cart::create([
            'user_id' => $otherClient->id,
            'product_id' => $this->product->id,
            'quantity' => 1
        ]);

        $otherOrder = app(OrderService::class)->createOrderFromCart($otherClient->id);

        $response = $this->actingAs($this->client)
                        ->get(route('client.orders.show', $otherOrder));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_can_create_order_from_cart()
    {
        Cart::create([
            'user_id' => $this->client->id,
            'product_id' => $this->product->id,
            'quantity' => 3
        ]);

        $response = $this->actingAs($this->client)
                        ->post(route('client.orders.store'));

        $response->assertRedirect();
        
        // Vérifier qu'une commande a été créée
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->client->id,
            'total_amount' => 150000, // 3 * 50000
            'status' => 'pending'
        ]);

        // Vérifier que le panier est vide
        $this->assertDatabaseMissing('carts', [
            'user_id' => $this->client->id
        ]);
    }

    /** @test */
    public function client_cannot_create_order_with_empty_cart()
    {
        $response = $this->actingAs($this->client)
                        ->post(route('client.orders.store'));

        $response->assertRedirect(route('client.cart.index'));
        $response->assertSessionHas('error', 'Votre panier est vide');
    }

    /** @test */
    public function client_cannot_create_order_without_profile_info()
    {
        $clientWithoutInfo = User::factory()->create([
            'role' => 'client',
            'phone' => null, // Manquant
            'delivery_address' => 'Conakry, Kaloum'
        ]);

        Cart::create([
            'user_id' => $clientWithoutInfo->id,
            'product_id' => $this->product->id,
            'quantity' => 1
        ]);

        $response = $this->actingAs($clientWithoutInfo)
                        ->post(route('client.orders.store'));

        $response->assertRedirect(route('client.profile.edit'));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function client_can_cancel_pending_order()
    {
        Cart::create([
            'user_id' => $this->client->id,
            'product_id' => $this->product->id,
            'quantity' => 2
        ]);

        $order = app(OrderService::class)->createOrderFromCart($this->client->id);

        $response = $this->actingAs($this->client)
                        ->patch(route('client.orders.cancel', $order));

        $response->assertRedirect();
        
        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
    }

    /** @test */
    public function client_cannot_cancel_delivered_order()
    {
        Cart::create([
            'user_id' => $this->client->id,
            'product_id' => $this->product->id,
            'quantity' => 1
        ]);

        $order = app(OrderService::class)->createOrderFromCart($this->client->id);
        $order->update(['status' => 'delivered']);

        $response = $this->actingAs($this->client)
                        ->patch(route('client.orders.cancel', $order));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'ne peut plus être annulée');
    }

    /** @test */
    public function unauthenticated_user_cannot_access_orders()
    {
        $response = $this->get(route('client.orders.index'));
        $response->assertRedirect('login');

        $response = $this->get(route('client.orders.show', 1));
        $response->assertRedirect('login');
    }

    /** @test */
    public function non_client_user_cannot_access_orders()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
                        ->get(route('client.orders.index'));

        $response->assertStatus(403);
    }
}
