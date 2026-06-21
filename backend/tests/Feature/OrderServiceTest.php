<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\InventoryService;
use App\Repositories\OrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $repository = new OrderRepository();
        $inventoryService = new InventoryService();
        $this->service = new OrderService($repository, $inventoryService);
    }

    /** @test */
    public function it_can_create_an_order()
    {
        $product = Product::factory()->create([
            'stock_quantity' => 100,
            'price' => 99.99,
            'status' => 'active',
        ]);

        $data = [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
            'shipping_name' => 'Test User',
            'shipping_phone' => '13800138000',
            'shipping_address' => 'Test Address',
        ];

        $order = $this->service->create($data);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals(199.98, $order->total_amount);
        $this->assertCount(1, $order->orderItems);
        
        // 验证库存已扣减
        $product->refresh();
        $this->assertEquals(98, $product->stock_quantity);
    }

    /** @test */
    public function it_throws_exception_when_insufficient_stock()
    {
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'status' => 'active',
        ]);

        $data = [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 20,
                ],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('库存不足');

        $this->service->create($data);
    }

    /** @test */
    public function it_can_update_order_status()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $updated = $this->service->updateStatus($order, 'paid');

        $this->assertEquals('paid', $updated->status);
        $this->assertNotNull($updated->paid_at);
    }

    /** @test */
    public function it_restores_inventory_when_order_cancelled()
    {
        $product = Product::factory()->create([
            'stock_quantity' => 100,
            'price' => 99.99,
            'status' => 'active',
        ]);

        $order = Order::factory()->create(['status' => 'paid']);
        \App\Models\OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        // 手动设置库存（模拟订单创建时已扣减）
        $product->update(['stock_quantity' => 95]);

        $this->service->updateStatus($order, 'cancelled');

        $product->refresh();
        $this->assertEquals(100, $product->stock_quantity);
    }
}
