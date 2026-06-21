<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => '深圳科技供应链有限公司',
                'contact' => '陈经理',
                'phone' => '0755-88886666',
                'address' => '广东省深圳市南山区科技园南路88号',
                'status' => 'active',
            ],
            [
                'name' => '杭州优品服饰贸易公司',
                'contact' => '林小姐',
                'phone' => '0571-87654321',
                'address' => '浙江省杭州市余杭区文一西路969号',
                'status' => 'active',
            ],
            [
                'name' => '上海家居生活采购中心',
                'contact' => '周主管',
                'phone' => '021-55667788',
                'address' => '上海市嘉定区曹安公路3000号',
                'status' => 'active',
            ],
            [
                'name' => '北京文华图书发行站',
                'contact' => '刘编辑',
                'phone' => '010-66554433',
                'address' => '北京市朝阳区望京街10号',
                'status' => 'active',
            ],
            [
                'name' => '广州华南电子批发部',
                'contact' => '黄老板',
                'phone' => '020-33445566',
                'address' => '广东省广州市天河区石牌西路8号',
                'status' => 'inactive',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(
                ['name' => $supplier['name']],
                $supplier
            );
        }
    }
}
