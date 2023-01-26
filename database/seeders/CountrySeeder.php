<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datas = config('list_country', []);

        foreach ($datas as $row) {
            Country::updateOrCreate([
                'name' => $row['name']
            ], [
                'code' => $row['code']
            ]);
        }
    }
}
