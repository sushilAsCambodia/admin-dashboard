<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $products = [
            [
                'id' => 2,
                'name' => '2D',
                'status' => 'enabled',
            ],
            [
                'id' => 3,
                'name' => '3D',
                'status' => 'enabled',
            ],
            [
                'id' => 4,
                'name' => '4D',
                'status' => 'enabled',
            ],

        ];

        foreach ($products as $product) {
            $product += ['created_by' => 1, 'updated_by' => 1];
            Product::updateOrcreate(['id' => $product['id']], $product);
        }
    }
}
