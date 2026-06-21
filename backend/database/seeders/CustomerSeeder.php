<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'name' => '张三',
                'phone' => '13800138000',
                'address' => '北京市朝阳区建国路88号SOHO现代城',
                'order_count' => 0,
                'total_spent' => 0,
            ],
            [
                'name' => '李四',
                'phone' => '13900139000',
                'address' => '上海市浦东新区陆家嘴环路1000号',
                'order_count' => 0,
                'total_spent' => 0,
            ],
            [
                'name' => '王五',
                'phone' => '13700137000',
                'address' => '广东省广州市天河区体育西路103号',
                'order_count' => 0,
                'total_spent' => 0,
            ],
            [
                'name' => '赵六',
                'phone' => '13600136000',
                'address' => '浙江省杭州市西湖区文三路478号',
                'order_count' => 0,
                'total_spent' => 0,
            ],
            [
                'name' => '钱七',
                'phone' => '13500135000',
                'address' => '四川省成都市武侯区天府大道北段1700号',
                'order_count' => 0,
                'total_spent' => 0,
            ],
            [
                'name' => '孙八',
                'phone' => '13400134000',
                'address' => '江苏省南京市鼓楼区中山北路30号',
                'order_count' => 0,
                'total_spent' => 0,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(
                ['phone' => $customer['phone']],
                $customer
            );
        }
    }
}
