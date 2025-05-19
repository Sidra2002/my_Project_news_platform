<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Politics',
            'Economy',
            'Technology',
            'Health',
           'Sports',
           'Various_internatonal_news',
        ];

        foreach ($categories as $name) {
            Category::create(['name' => $name]);
        }
    }
}
