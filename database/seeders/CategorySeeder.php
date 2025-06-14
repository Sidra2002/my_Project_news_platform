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
           'Environment',
           'Culture & Arts',
           'Education',
           'Tourism',
           'Crime & Law',
           'Lifestyle',
           'Science',
           'General',
        ];

        foreach ($categories as $name) {
            Category::create(['name' => $name]);
        }
    }
}
