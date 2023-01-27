<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Faker\Factory as Faker;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (!Category::count()) {
            $food = Category::create([
                'title' => 'Food',
                'alias' => 'food'
            ]);

            $automotive = Category::create([
                'title' => 'Automotive',
                'alias' => 'automotive'
            ]);

            $faker = Faker::create();
            for ($i = 1; $i <= 20; $i++) {
                $title = $faker->unique()->word;

                if ($i % 2 == 0) {
                    $parent_id = optional($food)->id;
                } else {
                    $parent_id = optional($automotive)->id;
                }

                Category::create([
                    'parent_id' => $parent_id,
                    'title' => ucwords($title),
                    'alias' => str_replace(' ', '-', $title)
                ]);
            }
        }
    }
}
