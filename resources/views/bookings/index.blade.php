<x-app-layout>
    <!-- PAGE HEADER -->
    <div style="margin-bottom: 3rem; display: flex; flex-direction: column; gap: 1rem; justify-content: space-between; align-items: flex-start;">
        <div>
            <p style="color: var(--accent-primary); font-weight: 700; text-transform: uppercase; font-size: 0.875rem; letter-spacing: 0.1em; margin-bottom: 0.5rem;">My Bookings</p>
            <h1 class="page-title" style="font-size: 3.5rem;">YOUR RESERVATIONS</h1>
        </div>
        <a href="{{ route('home') }}" class="btn btn-secondary">Browse More Concerts</a>
    </div>

    <!-- BOOKINGS GRID -->
    <div class="grid-2 gap-8">
        @forelse($bookings as $booking)
            <a href="{{ route('bookings.show', $booking) }}" style="text-decoration: none;">
                <div class="card" style="cursor: pointer; overflow: hidden; display: flex; flex-direction: column; height: 100%;">
                    <!-- Event Image Placeholder -->
                    <div style="background: linear-gradient(135deg, rgba(255, 102, 0, 0.8), rgba(255, 20, 147, 0.6)), url('https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=500&h=250&fit=crop'); background-size: cover; background-position: center; height: 250px; width: 100%;"></div>
                    
                    <div class="card-header">
                        <div style="flex: 1;">
                            <h3 class="card-title" style="margin-bottom: 0.25rem;">{{ $booking->concert->title }}</h3>
                            <p class="card-subtitle">{{ $booking->concert->artist }}</p>
                        </div>
                    </div>

                    <div class="card-body" style="flex: 1;">
                        <p style="color: var(--text-secondary); margin-bottom: 0.5rem; font-weight: 500;">📅 {{ $booking->concert->date->format('M d, Y') }}</p>
                        <p style="color: var(--text-tertiary); font-size: 0.875rem; margin-bottom: 1rem;">{{ $booking->concert->venue->name }} • {{ $booking->concert->venue->location }}</p>
                        
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <span class="badge badge-info" style="padding: 0.5rem 1rem;">{{ $booking->tickets->count() }} Ticket{{ $booking->tickets->count() !== 1 ? 's' : '' }}</span>
                            <span class="badge {{ $booking->status === 'confirmed' ? 'badge-success' : ($booking->status === 'cancelled' ? 'badge-danger' : 'badge-warning') }}" style="padding: 0.5rem 1rem;">{{ strtoupper($booking->status) }}</span>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div>
                            <p style="color: var(--text-tertiary); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Total Price</p>
                            <p style="font-size: 1.5rem; font-weight: 800; color: var(--accent-primary);">${{ number_format($booking->total_price, 2) }}</p>
                        </div>
                        <span class="btn btn-primary" style="margin-left: auto;">VIEW DETAILS →</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="card" style="grid-column: 1 / -1; padding: 3rem; text-align: center;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📋</div>
                <h3 style="font-size: 2rem; margin-bottom: 0.5rem; text-transform: uppercase;">No Bookings Yet</h3>
                <p style="color: var(--text-secondary); font-size: 1.1rem; margin-bottom: 2rem;">Explore concerts and reserve your next live event!</p>
                <a href="{{ route('home') }}" class="btn btn-primary">Browse Concerts</a>
            </div>
        @endforelse
    </div>
</x-app-layout>
