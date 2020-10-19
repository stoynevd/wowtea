<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        for ($i = 0; $i < 200; $i++) {
            \App\Parking::create([
                'car_reg_number' => \Illuminate\Support\Str::random(10),
                'entry_date' => \Carbon\Carbon::now()
            ]);
        }
    }
}
