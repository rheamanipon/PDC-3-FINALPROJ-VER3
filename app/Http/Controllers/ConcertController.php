<?php

namespace App\Http\Controllers;

use App\Models\Concert;
use App\Models\ConcertSeat;
use App\Models\TicketPrice;
use App\Models\Venue;
use Illuminate\Http\Request;

class ConcertController extends Controller
{
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
        return view('admin.concerts.create', compact('venues'));
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
        ]);

        $data = $request->only(['title', 'description', 'artist', 'venue_id', 'date', 'time']);

        if ($request->hasFile('poster')) {
            $data['poster_url'] = $request->file('poster')->store('posters', 'public');
        }

        $concert = Concert::create($data);

        // Create concert seats for all seats in the venue
        $venue = Venue::find($request->venue_id);
        foreach ($venue->seats as $seat) {
            ConcertSeat::create([
                'concert_id' => $concert->id,
                'seat_id' => $seat->id,
                'status' => 'available',
            ]);
        }

        // Create default ticket prices
        $sections = ['Floor', 'Lower Bowl', 'Upper Bowl', 'Balcony'];
        $defaultPrices = [150.00, 100.00, 75.00, 50.00];

        foreach ($sections as $index => $section) {
            TicketPrice::create([
                'concert_id' => $concert->id,
                'section' => $section,
                'price' => $defaultPrices[$index],
            ]);
        }

        return redirect()->route('admin.concerts.index')->with('success', 'Concert created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Concert $concert)
    {
$concert->load(['venue', 'ticketPrices', 'bookings', 'concertSeats.seat']);
        return view('admin.concerts.show', compact('concert'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Concert $concert)
    {
        $venues = Venue::all();
        return view('admin.concerts.edit', compact('concert', 'venues'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Concert $concert)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'artist' => 'required|string|max:255',
            'venue_id' => 'required|exists:venues,id',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'poster' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only(['title', 'description', 'artist', 'venue_id', 'date', 'time']);

        if ($request->hasFile('poster')) {
            $data['poster_url'] = $request->file('poster')->store('posters', 'public');
        }

        $concert->update($data);

        return redirect()->route('admin.concerts.index')->with('success', 'Concert updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Concert $concert)
    {
        $concert->delete();
        return redirect()->route('admin.concerts.index')->with('success', 'Concert deleted successfully.');
    }
}
