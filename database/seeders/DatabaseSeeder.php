<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Stock;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Создаем склады
        $warehouses = [
            ['name' => 'Склад №1'],
            ['name' => 'Склад №2'],
            ['name' => 'Склад №3'],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::create($warehouse);
        }

        // Создаем товары
        $products = [
            ['name' => 'Xiaomi 15 ultra', 'price' => 129999.90],
            ['name' => 'iPhone 16 pro max', 'price' => 139999.90],
            ['name' => 'Vivo X200 ultra ', 'price' => 119999.00],
            ['name' => 'Asus Rog rtx 5090 Strix', 'price' => 359000.00],
            ['name' => 'Ryzen 7 7800x3D', 'price' => 36499.99],
            ['name' => 'ADATA XPG Lancer Blade', 'price' => 11499.99],
            ['name' => 'MSI B650 Gaming Plus', 'price' => 14999.00],
            ['name' => 'DEEPCOOL GamerStorm PN1200M WH', 'price' => 15999.99],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        // Создаем остатки на складах
        $stocks = [
            // Склад №1
            ['warehouse_id' => 1, 'product_id' => 1, 'stock' => 10],
            ['warehouse_id' => 1, 'product_id' => 2, 'stock' => 15],
            ['warehouse_id' => 1, 'product_id' => 3, 'stock' => 20],
            ['warehouse_id' => 1, 'product_id' => 4, 'stock' => 8],
            ['warehouse_id' => 1, 'product_id' => 5, 'stock' => 12],
            ['warehouse_id' => 1, 'product_id' => 6, 'stock' => 25],
            ['warehouse_id' => 1, 'product_id' => 7, 'stock' => 30],
            ['warehouse_id' => 1, 'product_id' => 8, 'stock' => 5],

            // Склад №2
            ['warehouse_id' => 2, 'product_id' => 1, 'stock' => 5],
            ['warehouse_id' => 2, 'product_id' => 2, 'stock' => 8],
            ['warehouse_id' => 2, 'product_id' => 3, 'stock' => 15],
            ['warehouse_id' => 2, 'product_id' => 4, 'stock' => 3],
            ['warehouse_id' => 2, 'product_id' => 5, 'stock' => 7],
            ['warehouse_id' => 2, 'product_id' => 6, 'stock' => 18],
            ['warehouse_id' => 2, 'product_id' => 7, 'stock' => 22],
            ['warehouse_id' => 2, 'product_id' => 8, 'stock' => 2],

            // Склад №3
            ['warehouse_id' => 3, 'product_id' => 1, 'stock' => 7],
            ['warehouse_id' => 3, 'product_id' => 2, 'stock' => 12],
            ['warehouse_id' => 3, 'product_id' => 3, 'stock' => 18],
            ['warehouse_id' => 3, 'product_id' => 4, 'stock' => 6],
            ['warehouse_id' => 3, 'product_id' => 5, 'stock' => 9],
            ['warehouse_id' => 3, 'product_id' => 6, 'stock' => 20],
            ['warehouse_id' => 3, 'product_id' => 7, 'stock' => 25],
            ['warehouse_id' => 3, 'product_id' => 8, 'stock' => 4],
        ];

        foreach ($stocks as $stock) {
            Stock::create($stock);
        }
    }
}
