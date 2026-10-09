<?php
namespace Database\Seeders;
use App\Models\{Category, Color, Size};
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $cats = ['Dresses','Tops','T-Shirts','Shirts','Polo Shirts','Jeans','Trousers','Shorts','Skirts','Jackets','Suits','Shoes','Bags','Accessories','Jewelry','Watches','Perfumes',"Women's Wear","Men's Wear","Children's Wear"];
        foreach ($cats as $i => $n) {
            if (!Category::where('name', $n)->exists()) Category::create(['name' => $n, 'sort_order' => $i + 1]);
        }
        foreach (['XS','S','M','L','XL','XXL','XXXL','28','30','32','34','36','38','40'] as $i => $n) Size::firstOrCreate(['name' => $n], ['sort_order' => $i + 1]);
        foreach (['Black','White','Red','Blue','Green','Pink','Brown','Beige','Grey'] as $n) Color::firstOrCreate(['name' => $n]);
    }
}
