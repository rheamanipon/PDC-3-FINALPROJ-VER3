<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Concert;
use App\Models\ConcertSeat;
use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function index()
    {
        // FIX: Use 'id' instead of 'created_at'
        $bookings = Auth::user()->bookings()
            ->with('concert.venue', 'tickets.seat')
            ->orderBy('id', 'desc') 
            ->get();

        return view('bookings.index', compact('bookings'));
    }

    public function create(Concert $concert)
    {
        $concert->load('venue.seats', 'ticketPrices', 'concertSeats');
        $availableSeats = $concert->concertSeats()->where('status', 'available')->with('seat')->get();
        return view('bookings.create', compact('concert', 'availableSeats'));
    }

    public function store(Request $request, Concert $concert)
    {
        $request->validate([
            'ticket_quantity' => 'required|integer|min:1|max:5',
            'seat_ids' => 'required|array|min:1|max:5',
            'seat_ids.*' => 'exists:concert_seats,id',
        ]);

        $seatIds = $request->seat_ids;
        $ticketQuantity = (int) $request->ticket_quantity;

        if (count($seatIds) !== $ticketQuantity) {
            return back()->withErrors(['seat_ids' => 'Selected seats must match the ticket quantity.']);
        }
        $concertSeats = ConcertSeat::whereIn('id', $seatIds)->where('concert_id', $concert->id)->where('status', 'available')->get();

        if ($concertSeats->count() != count($seatIds)) {
            return back()->withErrors(['seat_ids' => 'Some selected seats are not available.']);
        }

        $totalPrice = 0;
        foreach ($concertSeats as $concertSeat) {
            $price = $concert->ticketPrices()->where('section', $concertSeat->seat->section)->first()->price ?? 0;
            $totalPrice += $price;
        }

        DB::transaction(function () use ($concert, $concertSeats, $totalPrice) {
            $booking = Booking::create([
                'user_id' => Auth::id(),
                'concert_id' => $concert->id,
                'total_price' => $totalPrice,
                'status' => 'pending',
            ]);

            foreach ($concertSeats as $concertSeat) {
                $price = $concert->ticketPrices()->where('section', $concertSeat->seat->section)->first()->price ?? 0;
                Ticket::create([
                    'booking_id' => $booking->id,
                    'seat_id' => $concertSeat->seat_id,
                    'price_at_purchase' => $price,
                    'qr_code' => uniqid(),
                ]);
                $concertSeat->update(['status' => 'reserved']);
            }

            Payment::create([
                'booking_id' => $booking->id,
                'amount' => $totalPrice,
                'payment_method' => 'pending',
                'status' => 'pending',
            ]);
        });

        return redirect()->route('bookings.index')->with('success', 'Booking created successfully!');
    }

    public function show(Booking $booking)
    {
        Gate::authorize('view', $booking); // This replaces $this->authorize()
        $booking->load('concert.venue', 'tickets.seat', 'payment');
        return view('bookings.show', compact('booking'));
    }
}
