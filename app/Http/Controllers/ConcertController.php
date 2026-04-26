<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Concert;
use App\Models\ConcertTicketType;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\Venue;
use App\Services\ConcertSeatSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConcertController extends Controller
{
    public function __construct(private ConcertSeatSyncService $concertSeatSyncService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $concerts = Concert::with('venue')->paginate(10);
        return view('admin.concerts.index', compact('concerts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $venues = Venue::all();
        $ticketTypes = TicketType::all();

        if ($ticketTypes->isEmpty()) {
            $defaultTypes = [
                ['name' => 'VIP Standing', 'description' => 'VIP Standing'],
                ['name' => 'VIP Seated', 'description' => 'VIP Seated'],
                ['name' => 'LBB', 'description' => 'Lower Box B'],
                ['name' => 'UBB', 'description' => 'Upper Box B'],
                ['name' => 'LBA', 'description' => 'Lower Box A'],
                ['name' => 'UBA', 'description' => 'Upper Box A'],
                ['name' => 'GEN AD', 'description' => 'General Admission'],
            ];

            foreach ($defaultTypes as $type) {
                TicketType::firstOrCreate(['name' => $type['name']], ['description' => $type['description']]);
            }

            $ticketTypes = TicketType::all();
        }

        return view('admin.concerts.create', compact('venues', 'ticketTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'artist' => 'required|string|max:255',
            'venue_id' => 'required|exists:venues,id',
            'date' => 'required|date|after:today',
            'time' => 'required|date_format:H:i',
            'poster' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'ticket_types' => 'required|array|min:1',
            'ticket_types.*.ticket_type_id' => 'required|exists:ticket_types,id',
            'ticket_types.*.price' => 'required|numeric|min:0',
            'ticket_types.*.quantity' => 'required|integer|min:1',
            'ticket_types.*.color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
        ]);

        $data = $request->only(['title', 'description', 'artist', 'venue_id', 'date', 'time']);

        if ($request->hasFile('poster')) {
            $data['poster_url'] = $request->file('poster')->store('posters', 'public');
        }

        $venue = Venue::find($request->venue_id);
        $totalTicketQuantity = collect($request->ticket_types)->sum('quantity');

        if ($venue && $totalTicketQuantity !== $venue->capacity) {
            return back()
                ->withInput()
                ->withErrors(['ticket_types' => "Ticket quantities must total the venue capacity of {$venue->capacity}. You provided {$totalTicketQuantity}. Please adjust the ticket type quantities."]);
        }

        $data['seat_plan_image'] = $request->hasFile('seat_plan_image') ? $request->file('seat_plan_image')->store('seat-plans', 'public') : null;
        $concert = Concert::create($data);
        ActivityLog::record([
            'user_id' => auth()->id(),
            'action' => 'create',
            'entity_type' => 'concert',
            'entity_id' => $concert->id,
            'description' => 'Created concert: '.$concert->title,
        ]);

        // Create concert ticket types
        foreach ($request->ticket_types as $ticketData) {
            ConcertTicketType::create([
                'concert_id' => $concert->id,
                'ticket_type_id' => $ticketData['ticket_type_id'],
                'custom_name' => $ticketData['custom_name'] ?? null,
                'price' => $ticketData['price'],
                'quantity' => $ticketData['quantity'],
                'color' => $ticketData['color'],
            ]);
        }

        // Keep selectable seats synchronized with ticket allocations.
        $this->concertSeatSyncService->syncConcert($concert);

        return redirect()->route('admin.concerts.index')->with('success', 'Concert created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Concert $concert)
    {
        $concert->load(['venue', 'concertTicketTypes.ticketType', 'bookings']);

        $ticketTypeSalesById = Ticket::whereHas('booking', function ($query) use ($concert) {
            $query->where('concert_id', $concert->id);
        })
        ->select('concert_ticket_type_id', DB::raw('count(*) as count'))
        ->groupBy('concert_ticket_type_id')
        ->pluck('count', 'concert_ticket_type_id')
        ->all();

        $ticketTypeSalesByLabel = Ticket::whereHas('booking', function ($query) use ($concert) {
            $query->where('concert_id', $concert->id);
        })
        ->whereNull('concert_ticket_type_id')
        ->select('ticket_type', DB::raw('count(*) as count'))
        ->groupBy('ticket_type')
        ->pluck('count', 'ticket_type')
        ->all();

        $ticketTypeAvailability = $concert->concertTicketTypes->map(function (ConcertTicketType $concertTicketType) use ($ticketTypeSalesById, $ticketTypeSalesByLabel) {
            $ticketTypeSlug = $concertTicketType->ticketType->name ?? '';
            $ticketTypeLabel = $concertTicketType->custom_name ?: ($concertTicketType->ticketType->description ? $concertTicketType->ticketType->description . ' (' . $ticketTypeSlug . ')' : $ticketTypeSlug);
            $sold = $ticketTypeSalesById[$concertTicketType->id] ?? ($ticketTypeSalesByLabel[$ticketTypeLabel] ?? 0);
            $remaining = max(0, $concertTicketType->quantity - $sold);

            return [
                'label' => $ticketTypeLabel,
                'slug' => $ticketTypeSlug,
                'quantity' => $concertTicketType->quantity,
                'sold' => $sold,
                'remaining' => $remaining,
            ];
        });

        return view('admin.concerts.show', compact('concert', 'ticketTypeAvailability'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Concert $concert)
    {
        $venues = Venue::all();
        $ticketTypes = TicketType::all();
        $concert->load('concertTicketTypes.ticketType');
        $hasSoldTickets = Ticket::whereHas('booking', function ($query) use ($concert) {
            $query->where('concert_id', $concert->id);
        })->exists();

        return view('admin.concerts.edit', compact('concert', 'venues', 'ticketTypes', 'hasSoldTickets'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Concert $concert)
    {
        $hasSoldTickets = Ticket::whereHas('booking', function ($query) use ($concert) {
            $query->where('concert_id', $concert->id);
        })->exists();

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'artist' => 'required|string|max:255',
            'venue_id' => 'required|exists:venues,id',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'poster' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'seat_plan_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'ticket_types' => 'required|array|min:1',
            'ticket_types.*.id' => 'nullable|exists:concert_ticket_types,id',
            'ticket_types.*.ticket_type_id' => 'required|exists:ticket_types,id',
            'ticket_types.*.custom_name' => 'nullable|string|max:255',
            'ticket_types.*.price' => 'required|numeric|min:0',
            'ticket_types.*.quantity' => 'required|integer|min:1',
            'ticket_types.*.color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
        ]);

        $existingTypes = $concert->concertTicketTypes()->get()->keyBy('id');
        $submittedTypeIds = collect($request->ticket_types)->pluck('id')->filter()->map(fn ($id) => (int) $id)->values()->all();
        $hasTypeStructureChange = count($submittedTypeIds) !== $existingTypes->count()
            || collect($existingTypes->keys()->all())->diff($submittedTypeIds)->isNotEmpty();

        if ($hasTypeStructureChange) {
            return back()
                ->withInput()
                ->withErrors(['ticket_types' => 'You can only edit existing ticket prices and colors in this form.']);
        }

        $data = $request->only(['title', 'description', 'artist', 'venue_id', 'date', 'time']);

        if ($request->hasFile('poster')) {
            $data['poster_url'] = $request->file('poster')->store('posters', 'public');
        }
        
        if ($request->hasFile('seat_plan_image')) {
            $data['seat_plan_image'] = $request->file('seat_plan_image')->store('seat-plans', 'public');
        }

        $oldVenueId = $concert->venue_id;
        $concert->update($data);
        $ticketAllocationChanged = false;
        $venueChanged = $oldVenueId != $request->venue_id;

        if ($venueChanged && $hasSoldTickets) {
            return back()
                ->withInput()
                ->withErrors(['venue_id' => 'Venue cannot be changed once tickets are already sold for this concert.']);
        }

        foreach ($request->ticket_types as $ticketData) {
            if (!empty($ticketData['id']) && $existingTypes->has($ticketData['id'])) {
                $concertTicketType = $existingTypes->get($ticketData['id']);
                $soldCount = Ticket::where('concert_ticket_type_id', $concertTicketType->id)->count();

                if ($ticketData['quantity'] < $soldCount) {
                    return back()
                        ->withInput()
                        ->withErrors(['ticket_types' => "Ticket quantity for {$concertTicketType->ticketType->name} cannot be less than already sold tickets ({$soldCount})."]);
                }

                $typeOrQuantityChanged = (int) $concertTicketType->ticket_type_id !== (int) $ticketData['ticket_type_id']
                    || $concertTicketType->quantity !== (int) $ticketData['quantity'];

                $priceOrColorChanged = (string) $concertTicketType->price !== (string) $ticketData['price']
                    || (string) $concertTicketType->color !== (string) $ticketData['color'];

                if ($hasSoldTickets && ($typeOrQuantityChanged || $priceOrColorChanged)) {
                    return back()
                        ->withInput()
                        ->withErrors(['ticket_types' => 'Ticket type and pricing edits are locked because tickets have already been sold for this concert.']);
                }

                if ($typeOrQuantityChanged) {
                    return back()
                        ->withInput()
                        ->withErrors(['ticket_types' => 'Only ticket price and color can be edited. Ticket type and quantity are fixed in this form.']);
                }

                $concertTicketType->update([
                    'ticket_type_id' => (int) $ticketData['ticket_type_id'],
                    'price' => $ticketData['price'],
                    'quantity' => $ticketData['quantity'],
                    'color' => $ticketData['color'],
                ]);
            } else {
                return back()
                    ->withInput()
                    ->withErrors(['ticket_types' => 'You can only edit existing ticket prices and colors in this form.']);
            }
        }

        // Always recalculate allocation on edit (when no sold tickets) to keep totals aligned.
        if (!$hasSoldTickets) {
            $targetVenue = Venue::find($request->venue_id);
            if ($targetVenue && $existingTypes->isNotEmpty()) {
                $quantities = $this->calculateVenueBasedQuantities($targetVenue, $existingTypes);
                foreach ($existingTypes->values() as $concertTicketType) {
                    $newQuantity = $quantities[$concertTicketType->id] ?? 0;
                    if ((int) $concertTicketType->quantity !== (int) $newQuantity) {
                        $concertTicketType->update(['quantity' => $newQuantity]);
                        $ticketAllocationChanged = true;
                    }
                }
            }
        }

        $concert->load('concertTicketTypes');
        $totalAllocated = (int) $concert->concertTicketTypes->sum('quantity');
        $venueCapacity = (int) $concert->venue()->value('capacity');
        if ($totalAllocated !== $venueCapacity) {
            return back()
                ->withInput()
                ->withErrors(['ticket_types' => "Ticket allocation mismatch detected after edit. Expected total {$venueCapacity}, got {$totalAllocated}."]);
        }

        $description = 'Updated concert: '.$concert->title;
        try {
            $this->concertSeatSyncService->syncConcert($concert, true);
            $description .= ' (seat allocation synced)';
        } catch (\RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['ticket_types' => $exception->getMessage()]);
        }

        ActivityLog::record([
            'user_id' => auth()->id(),
            'action' => 'update',
            'entity_type' => 'concert',
            'entity_id' => $concert->id,
            'description' => $description,
        ]);

        $successMsg = 'Concert updated successfully.';
        if ($venueChanged) {
            $successMsg .= ' Seats regenerated for new venue.';
        }
        return redirect()->route('admin.concerts.index')->with('success', $successMsg);
    }

    private function calculateVenueBasedQuantities(Venue $venue, $concertTicketTypes): array
    {
        $sectionSeatCounts = Seat::where('venue_id', $venue->id)
            ->select('section', DB::raw('count(*) as total'))
            ->groupBy('section')
            ->pluck('total', 'section');

        $quantities = [];
        $assignedCapacity = 0;
        $flexibleTypeIds = [];

        foreach ($concertTicketTypes as $concertTicketType) {
            $ticketTypeSlug = $concertTicketType->ticketType->name ?? '';
            $section = $this->getSeatSectionFromTicketType($ticketTypeSlug);

            if ($section !== null) {
                $quantity = (int) ($sectionSeatCounts[$section] ?? 0);
                $quantities[$concertTicketType->id] = $quantity;
                $assignedCapacity += $quantity;
            } else {
                $flexibleTypeIds[] = $concertTicketType->id;
            }
        }

        $remaining = max(0, (int) $venue->capacity - $assignedCapacity);
        $flexibleCount = count($flexibleTypeIds);
        if ($flexibleCount > 0) {
            $base = intdiv($remaining, $flexibleCount);
            $remainder = $remaining % $flexibleCount;
            foreach ($flexibleTypeIds as $index => $typeId) {
                $quantities[$typeId] = $base + ($index < $remainder ? 1 : 0);
            }
        }

        return $quantities;
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Concert $concert)
    {
        $title = $concert->title;
        $id = $concert->id;
        $concert->delete();
        ActivityLog::record([
            'user_id' => auth()->id(),
            'action' => 'delete',
            'entity_type' => 'concert',
            'entity_id' => $id,
            'description' => 'Deleted concert: '.$title,
        ]);
        return redirect()->route('admin.concerts.index')->with('success', 'Concert deleted successfully.');
    }
}

