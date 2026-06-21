<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Category;
use App\Services\ProductService;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductService $service;
    private ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ProductRepository();
        $this->service = new ProductService($this->repository);
    }

    /** @test */
    public function it_can_create_a_product()
    {
        $category = Category::factory()->create();

        $data = [
            'name' => 'Test Product',
            'sku' => 'TEST001',
            'category_id' => $category->id,
            'price' => 99.99,
            'stock_quantity' => 100,
        ];

        $product = $this->service->create($data);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals('TEST001', $product->sku);
        $this->assertEquals(99.99, $product->price);
    }

    /** @test */
    public function it_throws_exception_when_sku_already_exists()
    {
        $category = Category::factory()->create();
        Product::factory()->create(['sku' => 'TEST001']);

        $data = [
            'name' => 'Test Product',
            'sku' => 'TEST001',
            'category_id' => $category->id,
            'price' => 99.99,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('SKU 已存在，请使用其他 SKU');

        $this->service->create($data);
    }

    /** @test */
    public function it_throws_exception_when_cost_price_greater_than_price()
    {
        $category = Category::factory()->create();

        $data = [
            'name' => 'Test Product',
            'sku' => 'TEST001',
            'category_id' => $category->id,
            'price' => 99.99,
            'cost_price' => 150.00,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('成本价不能大于售价');

        $this->service->create($data);
    }

    /** @test */
    public function it_can_update_a_product()
    {
        $product = Product::factory()->create(['name' => 'Old Name']);

        $data = ['name' => 'New Name'];
        $updated = $this->service->update($product, $data);

        $this->assertEquals('New Name', $updated->name);
    }

    /** @test */
    public function it_can_delete_a_product_without_orders()
    {
        $product = Product::factory()->create();

        $result = $this->service->delete($product);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    /** @test */
    public function it_soft_deletes_product_with_orders()
    {
        $product = Product::factory()->create();
        // 创建订单项关联
        \App\Models\OrderItem::factory()->create(['product_id' => $product->id]);

        $result = $this->service->delete($product);

        $this->assertTrue($result);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
