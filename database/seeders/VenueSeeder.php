<?php

namespace Database\Seeders;

use App\Models\ConcertSeat;
use App\Models\Seat;
use App\Models\Venue;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VenueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $venue = Venue::create([
            'name' => 'Madison Square Garden',
            'location' => 'New York, NY',
            'capacity' => 20000,
        ]);

        // Create seats for the venue
        $sections = ['Floor', 'Lower Bowl', 'Upper Bowl', 'Balcony'];
        $seatsPerSection = 50;

        foreach ($sections as $section) {
            for ($i = 1; $i <= $seatsPerSection; $i++) {
                Seat::create([
                    'venue_id' => $venue->id,
                    'seat_number' => $section . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'section' => $section,
                ]);
            }
        }
    }
}
