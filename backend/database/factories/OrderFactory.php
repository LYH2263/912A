<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_no' => Order::generateOrderNo(),
            'user_id' => null,
            'total_amount' => $this->faker->randomFloat(2, 100, 10000),
            'discount_amount' => 0,
            'final_amount' => function (array $attributes) {
                return $attributes['total_amount'] - $attributes['discount_amount'];
            },
            'status' => $this->faker->randomElement(['pending', 'paid', 'shipped', 'completed', 'cancelled']),
            'shipping_address' => $this->faker->address,
            'shipping_name' => $this->faker->name,
            'shipping_phone' => $this->faker->phoneNumber,
            'remark' => $this->faker->optional()->sentence,
        ];
    }
}
