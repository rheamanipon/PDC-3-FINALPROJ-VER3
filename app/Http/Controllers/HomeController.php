<?php

namespace App\Http\Controllers;

use App\Models\Concert;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $query = Concert::with('venue')->where('date', '>=', now()->toDateString());

        if ($request->filled('location')) {
            $query->whereHas('venue', function ($venueQuery) use ($request) {
                $venueQuery->where('location', $request->input('location'));
            });
        }

        $concerts = $query->orderBy('date')->get();

        $locations = Concert::with('venue')
            ->where('date', '>=', now()->toDateString())
            ->get()
            ->pluck('venue.location')
            ->unique()
            ->sort()
            ->values();

        $trending = Concert::with('venue')
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->take(4)
            ->get();

        return view('home', compact('concerts', 'locations', 'trending'));
    }

    public function show(Concert $concert)
    {
        $concert->load('venue', 'ticketPrices');
        return view('concert.show', compact('concert'));
    }
}
