<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Source; 
use Carbon\Carbon;

class SourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
       
        $sources = [
            [
                'name' => 'SANA News',
                'url' => 'https://www.sana.sy/?feed=rss2',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]];

            foreach ($sources as $source) {
                Source::create($source);
            }
    }
}
