<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Concert;
use App\Models\ConcertSeat;
use App\Models\Payment;
use App\Models\Seat;
use App\Models\Ticket;
use App\Services\ConcertSeatSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function __construct(private ConcertSeatSyncService $concertSeatSyncService)
    {
    }

    public function index()
    {
        $bookings = Auth::user()->bookings()
            ->with('concert.venue', 'tickets')
            ->orderBy('id', 'desc') 
            ->get();

        return view('bookings.index', compact('bookings'));
    }

    public function create(Concert $concert)
    {
        $mismatches = $this->concertSeatSyncService->ensureIntegrityOrSync($concert);
        if (!empty($mismatches)) {
            return back()->withErrors(['general' => 'Seat inventory is being synchronized. Please try again in a moment.']);
        }

        $totalSold = $concert->bookings->sum(fn($b) => $b->tickets->count());
        $remaining = $concert->venue->capacity - $totalSold;
        $concert->load('venue', 'concertTicketTypes.ticketType');
        return view('bookings.create', compact('concert', 'remaining'));
    }

    public function getSeats(Request $request, Concert $concert)
    {
        $ticketTypeId = $request->query('concert_ticket_type_id');
        $mismatches = $this->concertSeatSyncService->ensureIntegrityOrSync($concert);
        if (!empty($mismatches)) {
            return response()->json([
                'error' => 'Seat allocation mismatch detected. Booking is temporarily blocked until synchronization completes.',
            ], 422);
        }

        $availableSeatsQuery = ConcertSeat::where('concert_seats.concert_id', $concert->id)
            ->where('concert_seats.status', 'available')
            ->join('seats', 'concert_seats.seat_id', '=', 'seats.id')
            ->where('seats.venue_id', $concert->venue_id)
            ->select('seats.id', 'seats.seat_number', 'seats.section', 'concert_seats.status');

        if ($ticketTypeId) {
            $concertTicketType = $concert->concertTicketTypes()->find($ticketTypeId);
            if (!$concertTicketType) {
                return response()->json([]);
            }

            $seatSection = $this->getSeatSectionFromTicketType($concertTicketType->ticketType->name ?? '');
            if ($seatSection) {
                $availableSeatsQuery->where('seats.section', $seatSection);
            }
            $availableSeatsQuery->where('concert_seats.concert_ticket_type_id', $concertTicketType->id);
        }

        $seats = $availableSeatsQuery
            ->orderBy('seats.seat_number')
            ->get();

        return response()->json($seats);
    }

    public function store(Request $request, Concert $concert)
    {
        $request->validate([
            'cart_items' => 'required|json',
        ]);

        $cartItems = json_decode($request->cart_items, true);
        if (!is_array($cartItems) || empty($cartItems)) {
            return back()->withErrors(['cart_items' => 'Please add at least one ticket.']);
        }

        // Store cart items in session for review
        session(['booking_cart' => [
            'concert_id' => $concert->id,
            'cart_items' => $cartItems
        ]]);

        return redirect()->route('bookings.review', $concert);
    }

    public function review(Concert $concert)
    {
        $cartData = session('booking_cart');
        if (!$cartData || $cartData['concert_id'] != $concert->id) {
            return redirect()->route('bookings.create', $concert)->withErrors(['general' => 'Session expired. Please select tickets again.']);
        }

        $cartItems = $cartData['cart_items'];
        $cartTotals = $this->calculateCartTotals($concert, $cartItems);
        $totalQuantity = $cartTotals['totalQuantity'];
        $totalPrice = $cartTotals['totalPrice'];
        $priceRecords = $cartTotals['priceRecords'];

        $selectedSeats = [];

        foreach ($cartItems as $item) {
            $ticketTypeId = $item['concert_ticket_type_id'] ?? null;
            if (!$ticketTypeId || !isset($priceRecords[$ticketTypeId])) {
                return redirect()->route('bookings.create', $concert)->withErrors(['cart_items' => 'Invalid ticket type selected.']);
            }

            if (isset($item['seat_id'])) {
                $selectedSeats[] = $item;
            }
        }

        $concert->load('venue');
        return view('bookings.review', compact('concert', 'cartItems', 'totalPrice', 'totalQuantity', 'selectedSeats', 'priceRecords'));
    }

    public function checkout(Concert $concert)
    {
        $cartData = session('booking_cart');
        if (!$cartData || $cartData['concert_id'] != $concert->id) {
            return redirect()->route('bookings.create', $concert)->withErrors(['general' => 'Session expired. Please select tickets again.']);
        }

        $cartItems = $cartData['cart_items'];
        $cartTotals = $this->calculateCartTotals($concert, $cartItems);
        $totalQuantity = $cartTotals['totalQuantity'];
        $totalPrice = $cartTotals['totalPrice'];
        $priceRecords = $cartTotals['priceRecords'];

        foreach ($cartItems as $item) {
            $ticketTypeId = $item['concert_ticket_type_id'] ?? null;
            if (!$ticketTypeId || !isset($priceRecords[$ticketTypeId])) {
                return redirect()->route('bookings.create', $concert)->withErrors(['cart_items' => 'Invalid ticket type selected.']);
            }
        }

        $concert->load('venue');
        return view('bookings.checkout', compact('concert', 'cartItems', 'totalPrice', 'totalQuantity', 'priceRecords'));
    }

    public function confirmPayment(Request $request, Concert $concert)
    {
        $mismatches = $this->concertSeatSyncService->ensureIntegrityOrSync($concert);
        if (!empty($mismatches)) {
            return back()->withErrors(['general' => 'Seat inventory mismatch detected. Please retry booking after synchronization.']);
        }

        $request->validate([
            'card_number' => ['required', 'string', 'regex:/^[0-9 ]{13,19}$/'],
            'expiry' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'cvv' => ['required', 'digits_between:3,4'],
            'cardholder_name' => ['required', 'string', 'max:100'],
            'terms' => ['accepted'],
        ]);

        $cartData = session('booking_cart');
        if (!$cartData || $cartData['concert_id'] != $concert->id) {
            return redirect()->route('bookings.create', $concert)->withErrors(['general' => 'Session expired. Please select tickets again.']);
        }

        $cartItems = $cartData['cart_items'];
        $ticketTypes = $concert->concertTicketTypes->keyBy('id');
        $priceRecords = $ticketTypes->mapWithKeys(fn($type) => [$type->id => $type->price])->all();
        $seatRequiredTypes = ['VIP Seated', 'LBB', 'UBB', 'LBA', 'UBA'];

        $totalQuantity = 0;
        $seatItems = [];
        $autoAssignItems = [];

        foreach ($cartItems as $item) {
            $ticketTypeId = $item['concert_ticket_type_id'] ?? null;
            if (!$ticketTypeId || !isset($ticketTypes[$ticketTypeId])) {
                return back()->withErrors(['cart_items' => 'Invalid ticket type selected.']);
            }

            $concertTicketType = $ticketTypes[$ticketTypeId];
            $ticketTypeSlug = $concertTicketType->ticketType->name ?? '';
            $ticketTypeLabel = $concertTicketType->custom_name ?: ($concertTicketType->ticketType->description ? $concertTicketType->ticketType->description . ' (' . $ticketTypeSlug . ')' : $ticketTypeSlug);
            $requiresSeat = in_array($ticketTypeSlug, $seatRequiredTypes, true);

            if ($requiresSeat) {
                if (empty($item['seat_id'])) {
                    return back()->withErrors(['cart_items' => 'Please select a seat for each reserved ticket.']);
                }

                $seatItems[] = [
                    'concert_ticket_type_id' => $ticketTypeId,
                    'ticket_type' => $ticketTypeLabel,
                    'seat_id' => $item['seat_id'],
                ];
                $totalQuantity += 1;
            } else {
                if (!isset($item['quantity']) || !is_numeric($item['quantity'])) {
                    return back()->withErrors(['cart_items' => 'Invalid ticket quantity.']);
                }

                $quantity = (int) $item['quantity'];
                if ($quantity < 1 || $quantity > 5) {
                    return back()->withErrors(['cart_items' => 'Quantity must be between 1 and 5.']);
                }

                $autoAssignItems[] = [
                    'concert_ticket_type_id' => $ticketTypeId,
                    'ticket_type' => $ticketTypeLabel,
                    'quantity' => $quantity,
                ];
                $totalQuantity += $quantity;
            }
        }

        if ($totalQuantity < 1) {
            return back()->withErrors(['cart_items' => 'Please add at least one ticket.']);
        }

        if ($totalQuantity > 5) {
            return back()->withErrors(['cart_items' => 'Maximum 5 tickets per booking.']);
        }

        $totalSold = $concert->bookings->sum(fn($b) => $b->tickets->count());
        if ($totalSold + $totalQuantity > $concert->venue->capacity) {
            return back()->withErrors(['general' => 'Not enough remaining capacity.']);
        }

        $totalPrice = 0;
        foreach ($seatItems as $item) {
            $totalPrice += $priceRecords[$item['concert_ticket_type_id']];
        }
        foreach ($autoAssignItems as $item) {
            $totalPrice += $priceRecords[$item['concert_ticket_type_id']] * $item['quantity'];
        }

        DB::transaction(function () use ($concert, $seatItems, $autoAssignItems, $totalPrice, $totalQuantity, $priceRecords, $ticketTypes) {
            $booking = Booking::create([
                'user_id' => Auth::id(),
                'concert_id' => $concert->id,
                'total_price' => $totalPrice,
                'status' => 'confirmed',
            ]);

            foreach ($seatItems as $item) {
                $concertTicketType = $ticketTypes[$item['concert_ticket_type_id']];
                $ticketTypeSlug = $concertTicketType->ticketType->name ?? '';
                $ticketTypeLabel = $concertTicketType->custom_name ?: ($concertTicketType->ticketType->description ? $concertTicketType->ticketType->description . ' (' . $ticketTypeSlug . ')' : $ticketTypeSlug);
                $seatSection = $this->getSeatSectionFromTicketType($ticketTypeSlug);

                $seat = Seat::find($item['seat_id']);
                if (!$seat || $seat->venue_id !== $concert->venue_id || ($seatSection !== null && $seat->section !== $seatSection)) {
                    throw new \Exception('Invalid seat selection.');
                }

                $concertSeat = ConcertSeat::firstOrCreate([
                    'concert_id' => $concert->id,
                    'seat_id' => $item['seat_id'],
                ], [
                    'concert_ticket_type_id' => $item['concert_ticket_type_id'],
                    'status' => 'available',
                ]);

                if ($concertSeat->concert_ticket_type_id === null) {
                    $concertSeat->concert_ticket_type_id = $item['concert_ticket_type_id'];
                    $concertSeat->save();
                }

                if ((int) $concertSeat->concert_ticket_type_id !== (int) $item['concert_ticket_type_id'] || $concertSeat->status !== 'available') {
                    throw new \Exception('Seat no longer available');
                }

                $concertSeat->update(['status' => 'reserved']);

                Ticket::create([
                    'booking_id' => $booking->id,
                    'concert_ticket_type_id' => $item['concert_ticket_type_id'],
                    'seat_id' => $item['seat_id'],
                    'ticket_type' => $ticketTypeLabel,
                    'price_at_purchase' => $priceRecords[$item['concert_ticket_type_id']],
                    'qr_code' => uniqid(),
                ]);
            }

            foreach ($autoAssignItems as $item) {
                $concertTicketType = $ticketTypes[$item['concert_ticket_type_id']];
                $ticketTypeSlug = $concertTicketType->ticketType->name ?? '';
                $ticketTypeLabel = $concertTicketType->custom_name ?: ($concertTicketType->ticketType->description ? $concertTicketType->ticketType->description . ' (' . $ticketTypeSlug . ')' : $ticketTypeSlug);
                $seatSection = $this->getSeatSectionFromTicketType($ticketTypeSlug);
                // VIP Standing has no seats

                if ($ticketTypeSlug === 'VIP Standing') {
                    // No seats for VIP Standing
                    for ($i = 0; $i < $item['quantity']; $i++) {
                        Ticket::create([
                            'booking_id' => $booking->id,
                            'concert_ticket_type_id' => $item['concert_ticket_type_id'],
                            'seat_id' => null, // No seat assigned
                            'ticket_type' => $ticketTypeLabel,
                            'price_at_purchase' => $priceRecords[$item['concert_ticket_type_id']],
                            'qr_code' => uniqid(),
                        ]);
                    }
                } else {
                    // Assign seats for other types
                    $availableSeatsQuery = ConcertSeat::where('concert_id', $concert->id)
                        ->where('status', 'available')
                        ->where('concert_ticket_type_id', $item['concert_ticket_type_id']);

                    if ($seatSection) {
                        $availableSeatsQuery->whereHas('seat', function ($query) use ($seatSection) {
                            $query->where('section', $seatSection);
                        });
                    }

                    if ($ticketTypeSlug === 'GEN AD' && $item['quantity'] >= 3) {
                        // For GEN AD with 3+ tickets, assign consecutive seats
                        $availableSeats = $availableSeatsQuery
                            ->join('seats', 'concert_seats.seat_id', '=', 'seats.id')
                            ->orderBy('seats.seat_number')
                            ->select('concert_seats.*')
                            ->get();

                        // Find consecutive seats
                        $consecutiveSeats = [];
                        $currentConsecutive = [];
                        
                        foreach ($availableSeats as $concertSeat) {
                            $seatNumber = (int) $concertSeat->seat->seat_number;
                            
                            if (empty($currentConsecutive)) {
                                $currentConsecutive[] = $concertSeat;
                            } elseif ((int) end($currentConsecutive)->seat->seat_number + 1 === $seatNumber) {
                                $currentConsecutive[] = $concertSeat;
                                if (count($currentConsecutive) >= $item['quantity']) {
                                    $consecutiveSeats = array_slice($currentConsecutive, -$item['quantity']);
                                    break;
                                }
                            } else {
                                $currentConsecutive = [$concertSeat];
                            }
                        }

                        if (count($consecutiveSeats) >= $item['quantity']) {
                            $availableSeats = collect($consecutiveSeats);
                        } else {
                            // Fallback to random if no consecutive seats found
                            $availableSeats = $availableSeatsQuery->inRandomOrder()
                                ->limit($item['quantity'])
                                ->get();
                        }
                    } else {
                        $availableSeats = $availableSeatsQuery->inRandomOrder()
                            ->limit($item['quantity'])
                            ->get();
                    }

                    if ($availableSeats->count() < $item['quantity']) {
                        throw new \Exception('Not enough seats available');
                    }

                    foreach ($availableSeats as $concertSeat) {
                        $concertSeat->update(['status' => 'reserved']);

                        Ticket::create([
                            'booking_id' => $booking->id,
                            'concert_ticket_type_id' => $item['concert_ticket_type_id'],
                            'seat_id' => $concertSeat->seat_id,
                            'ticket_type' => $ticketTypeLabel,
                            'price_at_purchase' => $priceRecords[$item['concert_ticket_type_id']],
                            'qr_code' => uniqid(),
                        ]);
                    }
                }
            }

            Payment::create([
                'booking_id' => $booking->id,
                'amount' => $totalPrice,
                'payment_method' => 'credit_card',
                'status' => 'paid',
            ]);

            // Log the booking activity
            ActivityLog::record([
                'user_id' => Auth::id(),
                'action' => 'create',
                'entity_type' => 'booking',
                'entity_id' => $booking->id,
                'description' => 'Booked tickets for concert: ' . $concert->title . ' (' . $totalQuantity . ' tickets, ₱' . number_format($totalPrice, 2) . ')',
            ]);

            return $booking;
        });

        // Clear session
        session()->forget('booking_cart');

        // Get the latest booking for this user and concert
        $booking = Booking::where('user_id', Auth::id())
            ->where('concert_id', $concert->id)
            ->latest()
            ->first();

        return redirect()->route('bookings.tickets', ['booking' => $booking->id]);
    }

    public function tickets(Booking $booking)
    {
        if ($booking->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
        $booking->load('concert.venue', 'tickets.seat', 'payment');
        return view('bookings.tickets', compact('booking'));
    }

    public function show(Booking $booking)
    {
        if ($booking->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
        $booking->load('concert.venue', 'payment');
        $tickets = $booking->tickets()->with('seat')->paginate(1);
        return view('bookings.show', compact('booking', 'tickets'));
    }

    private function calculateCartTotals(Concert $concert, array $cartItems): array
    {
        $ticketTypes = $concert->concertTicketTypes->keyBy('id');
        $priceRecords = $ticketTypes->mapWithKeys(fn($type) => [$type->id => $type->price])->all();

        $totalQuantity = 0;
        $totalPrice = 0;

        foreach ($cartItems as $item) {
            $ticketTypeId = $item['concert_ticket_type_id'] ?? null;
            $quantity = $item['quantity'] ?? 1;
            $totalQuantity += $quantity;
            $totalPrice += $priceRecords[$ticketTypeId] * $quantity;
        }

        return [
            'totalQuantity' => $totalQuantity,
            'totalPrice' => $totalPrice,
            'priceRecords' => $priceRecords,
        ];
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
