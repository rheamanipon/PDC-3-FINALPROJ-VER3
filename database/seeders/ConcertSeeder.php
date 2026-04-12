<?php

namespace Database\Seeders;

use App\Models\Concert;
use App\Models\ConcertSeat;
use App\Models\TicketPrice;
use App\Models\Venue;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConcertSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $venue = Venue::first();

        $concert = Concert::create([
            'title' => 'Summer Music Festival',
            'description' => 'A fantastic outdoor concert featuring multiple artists.',
            'artist' => 'Various Artists',
            'venue_id' => $venue->id,
            'date' => now()->addDays(30)->toDateString(),
            'time' => '19:00:00',
            'poster_url' => null,
        ]);

        // Create ticket prices
        TicketPrice::create([
            'concert_id' => $concert->id,
            'section' => 'Floor',
            'price' => 150.00,
        ]);

        TicketPrice::create([
            'concert_id' => $concert->id,
            'section' => 'Lower Bowl',
            'price' => 100.00,
        ]);

        TicketPrice::create([
            'concert_id' => $concert->id,
            'section' => 'Upper Bowl',
            'price' => 75.00,
        ]);

        TicketPrice::create([
            'concert_id' => $concert->id,
            'section' => 'Balcony',
            'price' => 50.00,
        ]);

        // Create concert seats
        foreach ($venue->seats as $seat) {
            ConcertSeat::create([
                'concert_id' => $concert->id,
                'seat_id' => $seat->id,
                'status' => 'available',
            ]);
        }
    }
}
