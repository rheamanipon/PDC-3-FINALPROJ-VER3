<?php

namespace Database\Seeders;

use App\Models\Venue;
use Illuminate\Database\Seeder;

class VenueSeeder extends Seeder
{
    public function run(): void
    {
        Venue::create([
            'name' => 'Madison Square Garden',
            'location' => 'New York, NY',
            'capacity' => 300,
        ]);

        Venue::create([
            'name' => 'Araneta Coliseum',
            'location' => 'Quezon City, Manila, Philippines',
            'capacity' => 25000,
        ]);
    }
}

