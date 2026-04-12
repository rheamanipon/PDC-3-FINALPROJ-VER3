<x-app-layout>
    <div style="margin-bottom: 3rem;">
        <p style="color: var(--accent-primary); font-weight: 700; text-transform: uppercase; font-size: 0.875rem; letter-spacing: 0.1em; margin-bottom: 0.5rem;">Seat Selection</p>
        <h1 class="page-title" style="font-size: 3.5rem;">CHOOSE YOUR SEATS</h1>
        <p style="color: var(--text-secondary); font-size: 1.1rem; margin-top: 1rem;">{{ $concert->title }} • {{ $concert->date->format('M d, Y') }}</p>
    </div>

    <div class="grid-2 gap-8">
        <!-- MAIN: SEAT SELECTION FORM -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">AVAILABLE SEATS</h3>
                    <p style="color: var(--text-secondary); font-size: 0.95rem;">{{ $concert->venue->name }}</p>
                </div>
            </div>

            <form action="{{ route('bookings.store', $concert) }}" method="POST">
                @csrf
                
                <div class="card-body">
                    <!-- Seat Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(70px, 1fr)); gap: 1rem; margin-bottom: 2rem; max-height: 400px; overflow-y: auto;">
                        @foreach($concert->concertSeats->sortBy(fn($seat) => $seat->seat->seat_number) as $concertSeat)
                            @php
                                $seat = $concertSeat->seat;
                                $isAvailable = $concertSeat->status === 'available';
                                $bgColor = $isAvailable ? 'var(--accent-primary)' : ($concertSeat->status === 'reserved' ? 'var(--bg-tertiary)' : '#333');
                                $textColor = $isAvailable ? '#000' : 'var(--text-tertiary)';
                            @endphp
                            <label style="cursor: {{ $isAvailable ? 'pointer' : 'not-allowed' }}; opacity: {{ $isAvailable ? '1' : '0.5' }};">
                                <input 
                                    type="checkbox" 
                                    name="seat_ids[]" 
                                    value="{{ $concertSeat->id }}"
                                    style="display: none;"
                                    @if(!$isAvailable) disabled @endif
                                />
                                <div style="padding: 1rem; background-color: {{ $bgColor }}; color: {{ $textColor }}; text-align: center; border-radius: 0; font-weight: 700; font-size: 0.875rem; border: 2px solid transparent; transition: all 0.2s ease; cursor: {{ $isAvailable ? 'pointer' : 'not-allowed' }};" class="seat-btn">
                                    <div>{{ $seat->seat_number }}</div>
                                    <div style="font-size: 0.7rem; margin-top: 0.25rem; opacity: 0.8;">{{ $seat->section }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <!-- Legend -->
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; padding: 1.5rem; background-color: rgba(255, 102, 0, 0.1); border-left: 3px solid var(--accent-primary);">
                        <div style="text-align: center;">
                            <div style="width: 20px; height: 20px; background-color: var(--accent-primary); margin: 0 auto 0.5rem; border-radius: 0;"></div>
                            <p style="font-size: 0.75rem; color: var(--text-tertiary); text-transform: uppercase;">Available</p>
                        </div>
                        <div style="text-align: center;">
                            <div style="width: 20px; height: 20px; background-color: var(--bg-tertiary); margin: 0 auto 0.5rem; border-radius: 0;"></div>
                            <p style="font-size: 0.75rem; color: var(--text-tertiary); text-transform: uppercase;">Reserved</p>
                        </div>
                        <div style="text-align: center;">
                            <div style="width: 20px; height: 20px; background-color: #333; margin: 0 auto 0.5rem; border-radius: 0;"></div>
                            <p style="font-size: 0.75rem; color: var(--text-tertiary); text-transform: uppercase;">Sold</p>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">COMPLETE BOOKING</button>
                </div>
            </form>
        </div>

        <!-- SIDEBAR: ORDER SUMMARY -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">ORDER SUMMARY</h3>
            </div>

            <div class="card-body">
                <div style="border-left: 3px solid var(--accent-primary); padding-left: 1.5rem; margin-bottom: 2rem;">
                    <h4 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">{{ $concert->title }}</h4>
                    <p style="color: var(--accent-secondary); font-weight: 600; margin-bottom: 1rem;">by {{ $concert->artist }}</p>
                    
                    <div style="display: flex; flex-direction: column; gap: 0.75rem; color: var(--text-secondary); font-size: 0.95rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span>Concert</span>
                            <span>{{ $concert->title }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Date</span>
                            <span>{{ $concert->date->format('M d, Y') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Time</span>
                            <span>{{ $concert->time->format('g:i A') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Venue</span>
                            <span>{{ $concert->venue->name }}</span>
                        </div>
                    </div>
                </div>

                <div style="border-top: 2px solid rgba(255, 102, 0, 0.3); padding-top: 1.5rem;">
                    <h4 style="font-size: 0.875rem; font-weight: 600; text-transform: uppercase; color: var(--text-tertiary); letter-spacing: 0.05em; margin-bottom: 1rem;">Price Information</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        @foreach($concert->ticketPrices as $price)
                            <div style="display: flex; justify-content: space-between; padding: 0.75rem; background-color: rgba(255, 102, 0, 0.05); border-left: 2px solid var(--accent-primary);">
                                <span style="font-weight: 600;">{{ $price->section }}</span>
                                <span style="color: var(--accent-primary); font-weight: 700;">${{ number_format($price->price, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
