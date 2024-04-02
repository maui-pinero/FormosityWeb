<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = array(
            array('name' => 'Metro Manila'),
            array('name' => 'Luzon'),
            array('name' => 'Visayas'),
            array('name' => 'Mindanao')
        );

		DB::table('locations')->insert($locations);
    }
}
