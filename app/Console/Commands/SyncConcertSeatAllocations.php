<?php

namespace App\Console\Commands;

use App\Models\Concert;
use App\Models\Seat;
use App\Models\Ticket;
use App\Services\ConcertSeatSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncConcertSeatAllocations extends Command
{
    protected $signature = 'app:sync-concert-seat-allocations';

    protected $description = 'Normalize concert ticket quantities to venue section capacities and sync seat assignments.';

    public function __construct(private ConcertSeatSyncService $concertSeatSyncService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $concerts = Concert::with(['venue', 'concertTicketTypes.ticketType'])->get();
        if ($concerts->isEmpty()) {
            $this->info('No concerts found.');
            return self::SUCCESS;
        }

        $this->info('Checking concert seat allocation consistency...');

        foreach ($concerts as $concert) {
            $this->line("Concert #{$concert->id}: {$concert->title}");

            $hasSoldTickets = Ticket::whereHas('booking', function ($query) use ($concert) {
                $query->where('concert_id', $concert->id);
            })->exists();

            if ($hasSoldTickets) {
                $this->warn('  Skipped quantity normalization (sold tickets exist).');
                $this->renderCurrentStatus($concert);
                continue;
            }

            $sectionSeatCounts = Seat::where('venue_id', $concert->venue_id)
                ->select('section', DB::raw('count(*) as total'))
                ->groupBy('section')
                ->pluck('total', 'section');

            foreach ($concert->concertTicketTypes as $concertTicketType) {
                $slug = $concertTicketType->ticketType->name ?? '';
                $section = $this->mapTicketTypeToSection($slug);
                if ($section === null) {
                    continue;
                }

                $target = (int) ($sectionSeatCounts[$section] ?? 0);
                if ((int) $concertTicketType->quantity !== $target) {
                    $concertTicketType->update(['quantity' => $target]);
                    $this->line("  Updated {$slug} quantity to {$target} (section capacity).");
                }
            }

            $concert->load(['concertTicketTypes.ticketType']);
            $assignedCapacity = 0;
            $flexibleTypes = [];
            foreach ($concert->concertTicketTypes as $concertTicketType) {
                $slug = $concertTicketType->ticketType->name ?? '';
                $section = $this->mapTicketTypeToSection($slug);
                if ($section === null) {
                    $flexibleTypes[] = $concertTicketType;
                    continue;
                }
                $assignedCapacity += (int) $concertTicketType->quantity;
            }

            $remaining = max(0, (int) $concert->venue->capacity - $assignedCapacity);
            $flexibleCount = count($flexibleTypes);
            if ($flexibleCount > 0) {
                $base = intdiv($remaining, $flexibleCount);
                $remainder = $remaining % $flexibleCount;
                foreach ($flexibleTypes as $index => $flexibleType) {
                    $target = $base + ($index < $remainder ? 1 : 0);
                    if ((int) $flexibleType->quantity !== $target) {
                        $flexibleType->update(['quantity' => $target]);
                        $this->line("  Updated {$flexibleType->ticketType->name} quantity to {$target} (remaining capacity).");
                    }
                }
            }

            try {
                $this->concertSeatSyncService->syncConcert($concert, true);
                $this->info('  Seat assignments synchronized.');
            } catch (\RuntimeException $exception) {
                $this->error('  Sync failed: '.$exception->getMessage());
            }

            $concert->load(['concertTicketTypes.ticketType']);
            $this->renderCurrentStatus($concert);
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function renderCurrentStatus(Concert $concert): void
    {
        foreach ($concert->concertTicketTypes as $concertTicketType) {
            $available = DB::table('concert_seats')
                ->where('concert_id', $concert->id)
                ->where('concert_ticket_type_id', $concertTicketType->id)
                ->where('status', 'available')
                ->count();

            $this->line("  {$concertTicketType->ticketType->name}: qty={$concertTicketType->quantity}, available={$available}");
        }
    }

    private function mapTicketTypeToSection(string $ticketTypeSlug): ?string
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

