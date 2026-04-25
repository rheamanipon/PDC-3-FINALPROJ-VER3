<?php

namespace App\Console\Commands;

use App\Models\Seat;
use App\Models\Venue;
use App\Services\ConcertSeatSyncService;
use Illuminate\Console\Command;

class FixVenueSeats extends Command
{
    public function __construct(private ConcertSeatSyncService $concertSeatSyncService)
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-venue-seats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix missing seats for venues that have no seats';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $venues = Venue::all();
        $fixedCount = 0;

        foreach ($venues as $venue) {
            $seatCount = $venue->seats()->count();

            if ($seatCount === 0) {
                $this->info("Fixing venue: {$venue->name} (ID: {$venue->id}, Capacity: {$venue->capacity})");

                $this->createVenueSeats($venue, $venue->capacity);
                $fixedCount++;

                // Also fix concert seats for concerts in this venue, using ticket-type allocations
                $concerts = $venue->concerts;
                foreach ($concerts as $concert) {
                    $this->concertSeatSyncService->syncConcert($concert);
                }
            }
        }

        $this->info("Fixed {$fixedCount} venues with missing seats.");
    }

    private function createVenueSeats(Venue $venue, int $capacity): void
    {
        Seat::where('venue_id', $venue->id)->delete();

        $baseSections = [
            'VIP Seated' => 20,
            'Lower Box B (LBB)' => 50,
            'Upper Box B (UBB)' => 100,
            'Lower Box A (LBA)' => 50,
            'Upper Box A (UBA)' => 50,
            'General Admission (Gen Ad)' => 30,
        ];

        $totalBaseSeats = array_sum($baseSections);
        $scale = $capacity / $totalBaseSeats;

        $sections = [];
        foreach ($baseSections as $section => $count) {
            $sections[$section] = range(1, (int) round($count * $scale));
        }

        $totalSeats = 0;
        foreach ($sections as $section => $seats) {
            $totalSeats += count($seats);
            foreach ($seats as $seatNumber) {
                Seat::create([
                    'venue_id' => $venue->id,
                    'seat_number' => (string) $seatNumber,
                    'section' => $section,
                ]);
            }
        }

        $this->info("Created {$totalSeats} seats for venue {$venue->id}");
    }
}
