<?php

namespace Database\Seeders;

use App\Models\Concert;
use App\Models\ConcertSeat;
use App\Models\Seat;
use Illuminate\Database\Seeder;

class ConcertSeatSeeder extends Seeder
{
    public function run(): void
    {
        $concerts = Concert::all();

        foreach ($concerts as $concert) {
            $concert->load(['concertTicketTypes.ticketType', 'venue.seats']);
            $venueSeats = $concert->venue->seats->groupBy('section');

            \Log::info("Creating concert seats for concert {$concert->id}, ticket types: " . $concert->concertTicketTypes->count());

            $concertSeats = [];
            foreach ($concert->concertTicketTypes as $concertTicketType) {
                $seatSection = $this->getSeatSectionFromTicketType($concertTicketType->ticketType->name ?? '');
                if (!$seatSection) {
                    continue;
                }

                $assignedSeats = $venueSeats->get($seatSection, collect())
                    ->sortBy(fn($seat) => intval($seat->seat_number))
                    ->take($concertTicketType->quantity);

                foreach ($assignedSeats as $seat) {
                    $concertSeats[] = [
                        'concert_id' => $concert->id,
                        'concert_ticket_type_id' => $concertTicketType->id,
                        'seat_id' => $seat->id,
                        'status' => 'available',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Bulk insert for better performance, in chunks
            $chunks = array_chunk($concertSeats, 1000);
            foreach ($chunks as $chunk) {
                ConcertSeat::insert($chunk);
            }
        }
    }

    private function getSeatSectionFromTicketType(string $ticketTypeSlug): ?string
    {
        return match ($ticketTypeSlug) {
            'VIP Seated' => 'VIP Seated',
            'LBB' => 'Lower Box B (LBB)',
            'UBB' => 'Upper Box B (UBB)',
            'LBA' => 'Lower Box A (LBA)',
            'UBA' => 'Upper Box A (UBA)',
            'Gen Ad', 'GEN AD' => 'General Admission (Gen Ad)',
            default => null,
        };
    }
}