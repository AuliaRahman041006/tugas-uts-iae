<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            ['name' => 'Laptop ASUS VivoBook 14',    'price' => 7500000,  'description' => 'Laptop 14 inch, Intel i5, 8GB RAM, 512GB SSD',   'stock' => 15],
            ['name' => 'Samsung Galaxy A54',          'price' => 4200000,  'description' => 'Smartphone 6.4 inch, 128GB, Kamera 50MP',        'stock' => 30],
            ['name' => 'TWS Earbuds Pro',             'price' => 350000,   'description' => 'True Wireless Stereo dengan noise cancellation',  'stock' => 50],
            ['name' => 'Kaos Polos Premium Cotton',   'price' => 89000,    'description' => 'Kaos katun combed 30s, berbagai warna',           'stock' => 100],
            ['name' => 'Jaket Hoodie Unisex',         'price' => 175000,   'description' => 'Hoodie fleece tebal, cocok untuk musim hujan',    'stock' => 40],
            ['name' => 'Kopi Arabica Toraja 250gr',   'price' => 65000,    'description' => 'Biji kopi arabica premium dari Toraja',           'stock' => 80],
            ['name' => 'Sepatu Running Nike Air',     'price' => 1250000,  'description' => 'Sepatu lari ringan dengan teknologi Air cushion', 'stock' => 18],
            ['name' => 'Buku Pemrograman Laravel',    'price' => 120000,   'description' => 'Panduan lengkap membangun web app dengan Laravel','stock' => 35],
            ['name' => 'Blender Philips 2L',          'price' => 450000,   'description' => 'Blender multifungsi 2 liter, 700 watt',          'stock' => 12],
            ['name' => 'Vitamin C 1000mg isi 30',     'price' => 45000,    'description' => 'Suplemen vitamin C untuk daya tahan tubuh',       'stock' => 60],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
