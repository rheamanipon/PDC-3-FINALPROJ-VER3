<?php

namespace App\Services;

use App\Models\Concert;
use App\Models\ConcertSeat;
use App\Models\Seat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ConcertSeatSyncService
{
    public function syncConcert(Concert $concert, bool $allowCreateAssignments = true): void
    {
        $concert->loadMissing('concertTicketTypes.ticketType', 'venue.seats');

        DB::transaction(function () use ($concert, $allowCreateAssignments) {
            $this->normalizeConcertSeatInventory($concert, $allowCreateAssignments);

            foreach ($concert->concertTicketTypes as $concertTicketType) {
                $section = $this->getSeatSectionFromTicketType($concertTicketType->ticketType->name ?? '');
                $expected = $section ? (int) $concertTicketType->quantity : 0;

                $this->syncTicketTypeSeats($concert, $concertTicketType->id, $section, $expected, $allowCreateAssignments);
            }
        });
    }

    public function validateIntegrity(Concert $concert): array
    {
        $concert->loadMissing('concertTicketTypes.ticketType');
        $mismatches = [];

        foreach ($concert->concertTicketTypes as $concertTicketType) {
            $section = $this->getSeatSectionFromTicketType($concertTicketType->ticketType->name ?? '');
            $expected = $section ? (int) $concertTicketType->quantity : 0;
            $actual = ConcertSeat::where('concert_id', $concert->id)
                ->where('concert_ticket_type_id', $concertTicketType->id)
                ->join('seats', 'concert_seats.seat_id', '=', 'seats.id')
                ->where('seats.venue_id', $concert->venue_id)
                ->count();

            if ($actual !== $expected) {
                $mismatches[] = [
                    'concert_ticket_type_id' => $concertTicketType->id,
                    'ticket_type' => $concertTicketType->section,
                    'expected' => $expected,
                    'actual' => $actual,
                ];
            }
        }

        return $mismatches;
    }

    public function ensureIntegrityOrSync(Concert $concert): array
    {
        $mismatches = $this->validateIntegrity($concert);
        if (!empty($mismatches)) {
            $this->syncConcert($concert);
            $mismatches = $this->validateIntegrity($concert);
        }

        return $mismatches;
    }

    private function syncTicketTypeSeats(Concert $concert, int $concertTicketTypeId, ?string $section, int $expected, bool $allowCreateAssignments): void
    {
        $baseQuery = ConcertSeat::where('concert_id', $concert->id)
            ->where('concert_ticket_type_id', $concertTicketTypeId)
            ->join('seats', 'concert_seats.seat_id', '=', 'seats.id')
            ->where('seats.venue_id', $concert->venue_id)
            ->select('concert_seats.*');

        $actual = (clone $baseQuery)->count();

        if ($actual < $expected) {
            if (!$allowCreateAssignments) {
                throw new \RuntimeException('Seat allocation cannot be expanded during ticket-type edits.');
            }

            $missing = $expected - $actual;
            $availableVenueSeats = $this->getUnassignedVenueSeatsForConcert($concert->id, $concert->venue_id, $section, $missing);
            if ($availableVenueSeats->count() < $missing) {
                throw new \RuntimeException('Seat allocation exceeds available venue seats.');
            }

            foreach ($availableVenueSeats->take($missing) as $seat) {
                ConcertSeat::create([
                    'concert_id' => $concert->id,
                    'concert_ticket_type_id' => $concertTicketTypeId,
                    'seat_id' => $seat->id,
                    'status' => 'available',
                ]);
            }
        } elseif ($actual > $expected) {
            $excess = $actual - $expected;

            $removableSeatIds = (clone $baseQuery)
                ->where('status', 'available')
                ->orderByDesc('id')
                ->limit($excess)
                ->pluck('seat_id');

            if ($removableSeatIds->isNotEmpty()) {
                ConcertSeat::where('concert_id', $concert->id)
                    ->where('concert_ticket_type_id', $concertTicketTypeId)
                    ->whereIn('seat_id', $removableSeatIds)
                    ->delete();
            }

            $remainingActual = (clone $baseQuery)->count();
            if ($remainingActual > $expected) {
                throw new \RuntimeException('Cannot reduce seats below sold/reserved seats for ticket type allocation.');
            }
        }
    }

    private function normalizeConcertSeatInventory(Concert $concert, bool $allowCreateAssignments): void
    {
        $activeTicketTypeIds = $concert->concertTicketTypes->pluck('id');

        $staleVenueBlocked = ConcertSeat::where('concert_id', $concert->id)
            ->join('seats', 'concert_seats.seat_id', '=', 'seats.id')
            ->where('seats.venue_id', '!=', $concert->venue_id)
            ->where('concert_seats.status', '!=', 'available')
            ->count();

        if ($staleVenueBlocked > 0) {
            throw new \RuntimeException('Concert has sold/reserved seats from a previous venue and cannot be auto-synced.');
        }

        $staleVenueAvailableIds = ConcertSeat::where('concert_id', $concert->id)
            ->join('seats', 'concert_seats.seat_id', '=', 'seats.id')
            ->where('seats.venue_id', '!=', $concert->venue_id)
            ->where('concert_seats.status', 'available')
            ->pluck('concert_seats.id');

        if ($staleVenueAvailableIds->isNotEmpty()) {
            ConcertSeat::whereIn('id', $staleVenueAvailableIds)->delete();
        }

        $orphanBlocked = ConcertSeat::where('concert_id', $concert->id)
            ->whereNotIn('concert_ticket_type_id', $activeTicketTypeIds)
            ->where('status', '!=', 'available')
            ->count();

        if ($orphanBlocked > 0) {
            throw new \RuntimeException('Concert has sold/reserved seats linked to removed ticket types and cannot be auto-synced.');
        }

        ConcertSeat::where('concert_id', $concert->id)
            ->whereNotIn('concert_ticket_type_id', $activeTicketTypeIds)
            ->where('status', 'available')
            ->delete();

        $this->removeDuplicateAvailableSeats($concert->id);

        $assignedSeatCount = ConcertSeat::where('concert_id', $concert->id)->count();
        if ($assignedSeatCount > (int) $concert->venue->capacity) {
            if (!$allowCreateAssignments) {
                throw new \RuntimeException('Seat count exceeds venue capacity and cannot be auto-expanded during edit.');
            }

            $overflow = $assignedSeatCount - (int) $concert->venue->capacity;
            $trimCandidateIds = ConcertSeat::where('concert_id', $concert->id)
                ->where('status', 'available')
                ->orderByDesc('id')
                ->limit($overflow)
                ->pluck('id');

            if ($trimCandidateIds->count() < $overflow) {
                throw new \RuntimeException('Concert seat count exceeds venue capacity and cannot be safely trimmed.');
            }

            ConcertSeat::whereIn('id', $trimCandidateIds)->delete();
        }
    }

    private function getUnassignedVenueSeatsForConcert(int $concertId, int $venueId, ?string $section, int $limit): Collection
    {
        $query = Seat::where('venue_id', $venueId)
            ->whereNotIn('id', function ($subQuery) use ($concertId) {
                $subQuery->select('seat_id')
                    ->from('concert_seats')
                    ->where('concert_id', $concertId);
            });

        if ($section !== null) {
            $query->where('section', $section);
        }

        return $query->orderByRaw('CAST(seat_number as unsigned)')
            ->orderBy('seat_number')
            ->limit($limit)
            ->get();
    }

    private function removeDuplicateAvailableSeats(int $concertId): void
    {
        $duplicateSeatIds = ConcertSeat::where('concert_id', $concertId)
            ->select('seat_id')
            ->groupBy('seat_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('seat_id');

        foreach ($duplicateSeatIds as $seatId) {
            $duplicates = ConcertSeat::where('concert_id', $concertId)
                ->where('seat_id', $seatId)
                ->orderByRaw("CASE WHEN status = 'available' THEN 1 ELSE 0 END ASC")
                ->orderBy('id')
                ->get();

            $keeper = $duplicates->first();
            if (!$keeper) {
                continue;
            }

            $deletableIds = $duplicates
                ->filter(fn (ConcertSeat $concertSeat) => $concertSeat->id !== $keeper->id && $concertSeat->status === 'available')
                ->pluck('id');

            if ($deletableIds->isNotEmpty()) {
                ConcertSeat::whereIn('id', $deletableIds)->delete();
            }

            $remainingCount = ConcertSeat::where('concert_id', $concertId)
                ->where('seat_id', $seatId)
                ->count();

            if ($remainingCount > 1) {
                throw new \RuntimeException('Concert has duplicate sold/reserved seat rows and requires manual cleanup.');
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
