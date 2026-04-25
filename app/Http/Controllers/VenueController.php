<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\Venue;
use App\Services\ConcertSeatSyncService;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    public function __construct(private ConcertSeatSyncService $concertSeatSyncService)
    {
    }

    public function index()
    {
        $venues = Venue::paginate(10);
        return view('admin.venues.index', compact('venues'));
    }

    public function create()
    {
        return view('admin.venues.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'seat_plan_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only(['name', 'location', 'capacity']);

        if ($request->hasFile('seat_plan_image')) {
            $data['seat_plan_image'] = $request->file('seat_plan_image')->store('venue-plans', 'public');
        }

        $venue = Venue::create($data);

        $this->createVenueSeats($venue, $data['capacity']);

        ActivityLog::record([
            'user_id' => auth()->id(),
            'action' => 'create',
            'entity_type' => 'venue',
            'entity_id' => $venue->id,
            'description' => 'Created venue: '.$venue->name,
        ]);

        return redirect()->route('admin.venues.index')->with('success', 'Venue created successfully.');
    }


    public function show(Venue $venue)
    {
        return view('admin.venues.show', compact('venue'));
    }

    public function edit(Venue $venue)
    {
        return view('admin.venues.edit', compact('venue'));
    }

    public function update(Request $request, Venue $venue)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'seat_plan_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only(['name', 'location', 'capacity']);

        if ($request->hasFile('seat_plan_image')) {
            $data['seat_plan_image'] = $request->file('seat_plan_image')->store('venue-plans', 'public');
        }

        $oldCapacity = $venue->capacity;
        $newCapacity = $data['capacity'];

        // Check ticket allocations for existing concerts
        if ($newCapacity != $oldCapacity) {
            $concerts = $venue->concerts()->with('concertTicketTypes')->get();
            $needsRedistribution = false;

            foreach ($concerts as $concert) {
                $totalSold = Ticket::whereHas('concertTicketType', function($q) use ($concert) {
                    $q->where('concert_id', $concert->id);
                })->count();

                $currentTotal = $concert->concertTicketTypes->sum('quantity');

                if ($newCapacity < $totalSold) {
                    return back()
                        ->withInput()
                        ->withErrors(['capacity' => "Cannot reduce capacity below already sold tickets ({$totalSold}) for concert '{$concert->title}'."]);
                }

                if ($totalSold == 0 && $currentTotal != $oldCapacity) {
                    // This shouldn't happen, but skip
                    continue;
                }

                if ($totalSold > 0 && $newCapacity != $currentTotal) {
                    if ($newCapacity < $currentTotal) {
                        return back()
                            ->withInput()
                            ->withErrors(['capacity' => "Cannot reduce capacity below current ticket allocation ({$currentTotal}) for concert '{$concert->title}' with sold tickets."]);
                    } else {
                        $needsRedistribution = true;
                    }
                }
            }

            $venue->update($data);

            // Adjust ticket allocations
            foreach ($concerts as $concert) {
                $totalSold = Ticket::whereHas('concertTicketType', function($q) use ($concert) {
                    $q->where('concert_id', $concert->id);
                })->count();

                if ($totalSold == 0) {
                    // Redistribute proportionally for concerts with no sold tickets
                    $this->redistributeTicketQuantities($concert, $newCapacity);
                } elseif ($newCapacity > $oldCapacity) {
                    // Capacity increased: distribute extra seats proportionally for concerts with sold tickets
                    $this->distributeExtraTickets($concert, $oldCapacity, $newCapacity);
                }
            }

            if ($needsRedistribution) {
                session()->flash('success', 'Venue capacity increased. Additional ticket allocations have been distributed proportionally.');
            }
        } else {
            $venue->update($data);
        }

        // If capacity changed or the venue has no seats yet, rebuild venue seat templates.
        if ($newCapacity != $oldCapacity || $venue->seats()->count() === 0) {
            $this->createVenueSeats($venue, $newCapacity);
        }

        // Always enforce allocation == selectable seats after any venue updates.
        foreach ($venue->concerts as $concert) {
            $this->concertSeatSyncService->syncConcert($concert);
        }

        $description = 'Updated venue: '.$venue->name;
        ActivityLog::record([
            'user_id' => auth()->id(),
            'action' => 'update',
            'entity_type' => 'venue',
            'entity_id' => $venue->id,
            'description' => $description,
        ]);

        return redirect()->route('admin.venues.index')->with('success', 'Venue updated successfully.');
    }

    public function destroy(Venue $venue)
    {
        // Check if venue is referenced by any concerts
        $hasConcerts = $venue->concerts()->exists();

        if ($hasConcerts) {
            return back()->with('error', 'Cannot delete this venue because it is currently used by one or more concerts.');
        }

        $name = $venue->name;
        $id = $venue->id;
        $venue->delete();
        ActivityLog::record([
            'user_id' => auth()->id(),
            'action' => 'delete',
            'entity_type' => 'venue',
            'entity_id' => $id,
            'description' => 'Deleted venue: '.$name,
        ]);
        return redirect()->route('admin.venues.index')->with('success', 'Venue deleted successfully.');
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

        \Log::info('Created ' . $totalSeats . ' seats for venue ' . $venue->id);
    }

    private function redistributeTicketQuantities($concert, $newCapacity)
    {
        $ticketTypes = $concert->concertTicketTypes;
        if ($ticketTypes->isEmpty()) {
            return; // No ticket types to redistribute
        }

        $totalCurrent = $ticketTypes->sum('quantity');
        if ($totalCurrent == 0) {
            // If all are 0, distribute equally
            $equalShare = (int) ($newCapacity / $ticketTypes->count());
            $remainder = $newCapacity % $ticketTypes->count();
            foreach ($ticketTypes as $index => $ctt) {
                $quantity = $equalShare + ($index < $remainder ? 1 : 0);
                $ctt->update(['quantity' => $quantity]);
            }
        } else {
            // Proportional redistribution
            $totalAssigned = 0;
            $updates = [];
            foreach ($ticketTypes as $ctt) {
                $proportion = $ctt->quantity / $totalCurrent;
                $newQuantity = (int) round($proportion * $newCapacity);
                $updates[] = ['ctt' => $ctt, 'quantity' => max(1, $newQuantity)]; // Ensure at least 1
                $totalAssigned += max(1, $newQuantity);
            }

            // Adjust to make sum exactly newCapacity
            $diff = $newCapacity - $totalAssigned;
            if ($diff != 0) {
                // Add/subtract from the first one
                $updates[0]['quantity'] += $diff;
            }

            foreach ($updates as $update) {
                $update['ctt']->update(['quantity' => $update['quantity']]);
            }
        }
    }

    /**
     * Distribute extra seats proportionally when capacity increases for concerts with sold tickets
     * 
     * Calculation:
     * - extra = newCapacity - oldCapacity
     * - For each ticket type:
     *   - proportion = currentQuantity / currentTotal
     *   - extraShare = round(proportion * extra)
     *   - newQuantity = currentQuantity + extraShare
     */
    private function distributeExtraTickets($concert, $oldCapacity, $newCapacity)
    {
        $ticketTypes = $concert->concertTicketTypes;
        if ($ticketTypes->isEmpty()) {
            return; // No ticket types to update
        }

        $extra = $newCapacity - $oldCapacity;
        if ($extra <= 0) {
            return; // No extra seats to distribute
        }

        $currentTotal = $ticketTypes->sum('quantity');
        if ($currentTotal == 0) {
            return; // No current allocation, skip
        }

        $totalDistributed = 0;
        $updates = [];

        // Calculate proportional distribution of extra seats
        foreach ($ticketTypes as $ctt) {
            $proportion = $ctt->quantity / $currentTotal;
            $extraShare = (int) round($proportion * $extra);
            $newQuantity = $ctt->quantity + $extraShare;
            
            $updates[] = [
                'ctt' => $ctt,
                'oldQuantity' => $ctt->quantity,
                'newQuantity' => $newQuantity,
            ];
            $totalDistributed += $extraShare;
        }

        // Adjust for rounding differences to ensure total matches exactly
        $diff = $extra - $totalDistributed;
        if ($diff != 0) {
            $updates[0]['newQuantity'] += $diff;
        }

        // Apply updates
        foreach ($updates as $update) {
            $update['ctt']->update(['quantity' => $update['newQuantity']]);
        }

        // Log the distribution
        \Log::info("Distributed {$extra} extra seats for concert '{$concert->title}' (capacity {$oldCapacity} -> {$newCapacity})", [
            'concert_id' => $concert->id,
            'old_capacity' => $oldCapacity,
            'new_capacity' => $newCapacity,
            'extra_seats' => $extra,
            'total_allocation_before' => $currentTotal,
            'total_allocation_after' => $newCapacity,
            'distribution' => $updates,
        ]);
    }

}


