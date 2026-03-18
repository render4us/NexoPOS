<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductGallery;
use App\Models\ProductUnitQuantity;
use App\Models\Role;
use App\Models\Unit;
use App\Models\UnitGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KioskMockSeeder extends Seeder
{
    // ── Cardápio completo Snow Shake Auriflama ──────────────────────────
    private array $categories = [
        ['id' => 1, 'name' => 'Grand Gateau, Cascão e Potinhos To Go'],
        ['id' => 2, 'name' => 'Soft e Sundae'],
        ['id' => 3, 'name' => 'Milk-Shake Especiais'],
        ['id' => 4, 'name' => 'Milk Shakes Clássicos'],
        ['id' => 5, 'name' => 'Bebidas'],
    ];

    private array $products = [
        // Grand Gateau, Cascão e Potinhos To Go
        ['name' => 'Grand Gateau Tradicional',        'price' => 22.00, 'category_id' => 1, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e187b78be.png'],
        ['name' => 'Grand Gateau Pistache',           'price' => 26.00, 'category_id' => 1, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e18312243.png'],
        ['name' => 'Cascão Merengue',                 'price' => 15.00, 'category_id' => 1, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e17e49776.png'],
        ['name' => 'Cascão Pistache Chocolate',       'price' => 18.00, 'category_id' => 1, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e18d4dd6d.png'],
        ['name' => 'Cascão Pistache',                 'price' => 18.00, 'category_id' => 1, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e19354e17.png'],
        ['name' => 'Cascão Sensação Morango',         'price' => 17.00, 'category_id' => 1, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e198c5278.png'],
        ['name' => 'Cascão Sensação Chocolate',       'price' => 17.00, 'category_id' => 1, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e1940910d.png'],
        ['name' => 'Cascão Ouro Branco',              'price' => 15.00, 'category_id' => 1, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e198263e3.png'],
        ['name' => 'Cascão Kit Kat',                  'price' => 15.00, 'category_id' => 1, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e17d98c51.png'],
        ['name' => 'Cascão Pop Corn Ninho',           'price' => 15.00, 'category_id' => 1, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e182a0275.png'],
        ['name' => 'Cascão Chocolatudo',              'price' => 15.00, 'category_id' => 1, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e18b2aa79.png'],
        ['name' => 'To Go Merengue',                  'price' => 15.00, 'category_id' => 1, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e187a1c10.png'],
        ['name' => 'To Go Chocolatudo',               'price' => 15.00, 'category_id' => 1, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e19f9e004.png'],
        ['name' => 'To Go Sensação',                  'price' => 17.00, 'category_id' => 1, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e1aeb0eee.png'],
        ['name' => 'To Go Pop Corn Ninho',            'price' => 15.00, 'category_id' => 1, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e17b44a7e.png'],
        ['name' => 'To Go Ouro Branco',               'price' => 15.00, 'category_id' => 1, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e190c6622.png'],
        ['name' => 'To Go Kit Kat',                   'price' => 15.00, 'category_id' => 1, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e177e04f0.png'],
        ['name' => 'To Go Escolha Soft',              'price' => 12.00, 'category_id' => 1, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e19ed9428.png'],

        // Soft e Sundae
        ['name' => 'Soft Chocolate',                  'price' =>  8.00, 'category_id' => 2, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e30e1d9c7.png'],
        ['name' => 'Soft Morango',                    'price' =>  8.00, 'category_id' => 2, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e30eb6020.png'],
        ['name' => 'Soft Chocolate e Morango',        'price' =>  8.00, 'category_id' => 2, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e3b865a0f.png'],
        ['name' => 'Soft Pistache',                   'price' =>  8.00, 'category_id' => 2, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e31b7c040.png'],
        ['name' => 'Soft Ninho',                      'price' =>  8.00, 'category_id' => 2, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e3146ef19.png'],
        ['name' => 'Soft Pistache e Ninho',           'price' =>  8.00, 'category_id' => 2, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e31d6b429.png'],
        ['name' => 'Cascão',                          'price' =>  7.00, 'category_id' => 2, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e31579c3a.png'],
        ['name' => 'Casquinha',                       'price' =>  5.00, 'category_id' => 2, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e3075a9c1.png'],
        ['name' => 'Sundae Morango',                  'price' => 14.00, 'category_id' => 2, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e32546f66.png'],
        ['name' => 'Sundae Caramelo',                 'price' => 14.00, 'category_id' => 2, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e31a6dc06.png'],
        ['name' => 'Sundae Chocolate',                'price' => 14.00, 'category_id' => 2, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e322398b2.png'],

        // Milk-Shake Especiais
        ['name' => 'Milk Shake Pistachello',          'price' => 19.00, 'category_id' => 3, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e830eddd0.png'],
        ['name' => 'Milk Shake Ferrero Rocher',       'price' => 19.00, 'category_id' => 3, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e4d77c981.jpeg'],
        ['name' => 'Milk Shake Ninho Nutella',        'price' => 19.00, 'category_id' => 3, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e4df45d5f.jpeg'],
        ['name' => 'Milk Shake Cookies e Cream',      'price' => 19.00, 'category_id' => 3, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e4cebc32f.jpeg'],
        ['name' => 'Milk Shake Morango com Ninho',    'price' => 19.00, 'category_id' => 3, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e4e7e097a.jpeg'],
        ['name' => 'Milk Shake Paçoquita com Nutella','price' => 19.00, 'category_id' => 3, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e4af73e7c.jpeg'],
        ['name' => 'Milk Shake Nutella',              'price' => 19.00, 'category_id' => 3, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e4e5cdd91.png'],
        ['name' => 'Milk Shake Café com Nutella',     'price' => 19.00, 'category_id' => 3, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e4c117abc.jpeg'],
        ['name' => 'Milk Shake Morango com Nutella',  'price' => 19.00, 'category_id' => 3, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e4b6eeebb.jpeg'],
        ['name' => 'Milk Shake Chiclete',             'price' => 19.00, 'category_id' => 3, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e4be2c99b.jpeg'],
        ['name' => 'Milk Shake Céu Azul',             'price' => 19.00, 'category_id' => 3, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e4ac7703d.jpeg'],

        // Milk Shakes Clássicos
        ['name' => 'Milk Shake Ninho',                'price' => 16.00, 'category_id' => 4, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e5b790c71.jpeg'],
        ['name' => 'Milk Shake Paçoquita',            'price' => 16.00, 'category_id' => 4, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e5b06843a.jpeg'],
        ['name' => 'Milk Shake Morango',              'price' => 16.00, 'category_id' => 4, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e5c816828.jpeg'],
        ['name' => 'Milk Shake Ovomaltine',           'price' => 16.00, 'category_id' => 4, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e5bf19628.jpeg'],
        ['name' => 'Milk Shake Ninhomaltine',         'price' => 16.00, 'category_id' => 4, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e5ccbadf7.jpeg'],
        ['name' => 'Milk Shake Doce de Leite',        'price' => 16.00, 'category_id' => 4, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6877e5c50293b.jpeg'],

        // Bebidas
        ['name' => 'Água',                            'price' =>  3.00, 'category_id' => 5, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6856e36d690e9.png'],
        ['name' => 'Água com Gás',                    'price' =>  4.00, 'category_id' => 5, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6855bb5bb362f.png'],
        ['name' => 'Coca Cola',                       'price' =>  5.00, 'category_id' => 5, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6855bb5e93869.png'],
        ['name' => 'Coca Cola Zero',                  'price' =>  5.00, 'category_id' => 5, 'image' => 'https://duisktnou8b89.cloudfront.net/img/items/6855bb60f10e1.png'],
    ];

    public function run(): void
    {
        // ── Autor (primeiro admin) ───────────────────────────────────────
        $author = Role::namespace('admin')->users->first()?->id ?? 1;

        // ── Unit "Unidade" ───────────────────────────────────────────────
        $unit = Unit::where('identifier', 'piece')->first();

        if (! $unit) {
            $unitGroup = UnitGroup::firstOrCreate(
                ['name' => 'Contável'],
                ['author' => $author]
            );

            $unit = Unit::create([
                'name'       => 'Unidade',
                'value'      => 1,
                'identifier' => 'piece',
                'base_unit'  => true,
                'group_id'   => $unitGroup->id,
                'author'     => $author,
            ]);

            $this->command->info("Unidade criada (group_id: {$unitGroup->id}).");
        }

        // ── Limpa dados antigos de kiosk (produtos KIOSK-*) ─────────────
        $this->command->line('Removendo produtos antigos de kiosk...');
        $antigos = Product::where('sku', 'like', 'KIOSK-%')->get();
        foreach ($antigos as $produto) {
            ProductGallery::where('product_id', $produto->id)->delete();
            ProductUnitQuantity::where('product_id', $produto->id)->delete();
            $produto->delete();
        }
        $this->command->line("  {$antigos->count()} produto(s) removido(s).");

        // ── Categorias ───────────────────────────────────────────────────
        $categoryMap = [];
        foreach ($this->categories as $cat) {
            $category = ProductCategory::firstOrCreate(
                ['name' => $cat['name']],
                ['author' => $author]
            );
            $categoryMap[$cat['id']] = $category->id;
            $this->command->line("  Categoria: <info>{$category->name}</info>");
        }

        // ── Produtos ─────────────────────────────────────────────────────
        $criados = 0;
        foreach ($this->products as $item) {
            $price      = (float) $item['price'];
            $categoryId = $categoryMap[$item['category_id']] ?? null;

            $product = Product::create([
                'name'             => $item['name'],
                'sku'              => 'KIOSK-' . strtoupper(Str::random(6)),
                'barcode'          => 'KIOSK' . str_pad(rand(1000000, 9999999), 7, '0'),
                'barcode_type'     => 'ean13',
                'type'             => Product::TYPE_DEMATERIALIZED,
                'status'           => Product::STATUS_AVAILABLE,
                'stock_management' => Product::STOCK_MANAGEMENT_DISABLED,
                'category_id'      => $categoryId,
                'unit_group'       => $unit->group_id,
                'accurate_tracking'=> false,
                'auto_cogs'        => false,
                'author'           => $author,
                'uuid'             => Str::uuid()->toString(),
            ]);

            ProductUnitQuantity::create([
                'product_id'                  => $product->id,
                'unit_id'                     => $unit->id,
                'type'                        => 'product',
                'quantity'                    => 0,
                'low_quantity'                => 0,
                'stock_alert_enabled'         => false,
                'visible'                     => true,
                'sale_price'                  => $price,
                'sale_price_edit'             => $price,
                'sale_price_with_tax'         => $price,
                'sale_price_without_tax'      => $price,
                'sale_price_tax'              => 0,
                'wholesale_price'             => $price,
                'wholesale_price_edit'        => $price,
                'wholesale_price_with_tax'    => $price,
                'wholesale_price_without_tax' => $price,
                'wholesale_price_tax'         => 0,
                'custom_price'                => 0,
                'custom_price_edit'           => 0,
                'custom_price_with_tax'       => 0,
                'custom_price_without_tax'    => 0,
                'custom_price_tax'            => 0,
                'cogs'                        => 0,
                'uuid'                        => Str::uuid()->toString(),
            ]);

            ProductGallery::create([
                'product_id' => $product->id,
                'url'        => $item['image'],
                'featured'   => true,
                'author'     => $author,
            ]);

            $this->command->line(sprintf(
                '  <info>✔</info> %-45s R$ %s  <comment>%s</comment>',
                $item['name'],
                number_format($price, 2, ',', '.'),
                $item['image']
            ));

            $criados++;
        }

        $this->command->newLine();
        $this->command->info("Concluído: {$criados} produto(s) criado(s) com imagem.");
    }
}
