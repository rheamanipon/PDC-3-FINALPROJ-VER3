<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Venue;
use App\Services\ConcertSeatSyncService;
use Illuminate\Http\Request;

class VenueApiController extends Controller
{
    public function __construct(private ConcertSeatSyncService $concertSeatSyncService)
    {
    }

    public function index()
    {
        return response()->json(Venue::orderByDesc('id')->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
        ]);

        $venue = Venue::create($data);
        return response()->json($venue, 201);
    }

    public function show(Venue $venue)
    {
        return response()->json($venue->load('seats'));
    }

    public function update(Request $request, Venue $venue)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
        ]);

        $oldCapacity = $venue->capacity;
        $newCapacity = $data['capacity'];

        // Check ticket allocations for existing concerts
        if ($newCapacity != $oldCapacity) {
            $concerts = $venue->concerts()->with('concertTicketTypes.ticketType')->get();

            foreach ($concerts as $concert) {
                $totalSold = Ticket::whereHas('concertTicketType', function($q) use ($concert) {
                    $q->where('concert_id', $concert->id);
                })->count();

                $currentTotal = $concert->concertTicketTypes->sum('quantity');

                if ($newCapacity < $totalSold) {
                    return response()->json(['error' => "Cannot reduce capacity below already sold tickets ({$totalSold}) for concert '{$concert->title}'."], 422);
                }

                if ($totalSold > 0 && $newCapacity < $currentTotal) {
                    return response()->json(['error' => "Cannot reduce capacity below current ticket allocation ({$currentTotal}) for concert '{$concert->title}' with sold tickets."], 422);
                }
            }

            $venue->update($data);

            // Adjust ticket allocations for concerts
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

                $this->concertSeatSyncService->syncConcert($concert);
            }
        } else {
            $venue->update($data);

            foreach ($venue->concerts()->with('concertTicketTypes.ticketType')->get() as $concert) {
                $this->concertSeatSyncService->syncConcert($concert);
            }
        }

        return response()->json($venue);
    }

    public function destroy(Venue $venue)
    {
        $venue->delete();
        return response()->json(['message' => 'Venue deleted']);
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
