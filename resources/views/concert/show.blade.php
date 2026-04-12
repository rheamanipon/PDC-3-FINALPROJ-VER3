<x-app-layout>
    <div x-data="{ 
        selectedSeat: null, 
        selectedSection: null, 
        selectedPrice: null,
        selectSeat(number, section, price) {
            this.selectedSeat = (this.selectedSeat === number) ? null : number;
            this.selectedSection = section;
            this.selectedPrice = price;
        }
    }">
        <div style="margin-bottom: 3rem; border-radius: 0; overflow: hidden;">
            <div style="position: relative; height: 500px; overflow: hidden;">
                @if($concert->poster_url)
                    <img src="{{ asset('storage/' . $concert->poster_url) }}" alt="{{ $concert->title }}" style="width: 100%; height: 100%; object-fit: cover;" />
                @else
                    <div style="width: 100%; height: 100%; background: #111; display: flex; align-items: center; justify-content: center; color: #444; font-size: 1.5rem;">
                        📸 No Image Available
                    </div>
                @endif
                <div style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(10,10,10,1) 100%);"></div>
                
                <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 3rem 2rem; color: white;">
                    <p style="color: var(--accent-primary); font-size: 1rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 1rem;">{{ $concert->venue->location }}</p>
                    <h1 style="font-size: 4rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.5rem; line-height: 1;">{{ $concert->title }}</h1>
                    <p style="font-size: 1.5rem; color: var(--accent-secondary); font-weight: 700;">by {{ $concert->artist }}</p>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-12 gap-12" style="margin-bottom: 4rem;">
            
            <div class="lg:col-span-7" style="display: flex; flex-direction: column; gap: 2rem;">
                <div class="card" style="padding: 2rem; background: #0a0a0a; border: 1px solid #1a1a1a;">
                    <h3 class="card-title" style="margin-bottom: 1.5rem; color: #fff;">EVENT DETAILS</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div style="border-bottom: 2px solid #222; padding-bottom: 1.5rem;">
                            <p style="color: #555; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.1em; margin-bottom: 0.75rem;">Venue</p>
                            <p style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: #fff;">{{ $concert->venue->name }}</p>
                            <p style="color: #777; font-size: 0.95rem;">Capacity: {{ $concert->venue->capacity }}</p>
                        </div>
                        <div style="border-bottom: 2px solid #222; padding-bottom: 1.5rem;">
                            <p style="color: #555; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.1em; margin-bottom: 0.75rem;">Date & Time</p>
                            <p style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: #fff;">{{ $concert->date->format('M d, Y') }}</p>
                            <p style="color: #777; font-size: 0.95rem;">{{ $concert->time->format('g:i A') }}</p>
                        </div>
                    </div>
                    <div>
                        <p style="color: #555; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.1em; margin-bottom: 1rem;">About This Event</p>
                        <p style="line-height: 1.8; color: #888;">{{ $concert->description }}</p>
                    </div>
                </div>

                <div class="card" style="padding: 2rem; background: #0a0a0a; border: 1px solid #1a1a1a;">
                    <h3 class="card-title" style="margin-bottom: 1.5rem; color: #fff;">TICKET PRICES</h3>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        @foreach($concert->ticketPrices as $price)
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-left: 3px solid #333; background-color: #111;">
                                <span style="font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #eee;">{{ $price->section }}</span>
                                <span style="font-size: 1.5rem; font-weight: 800; color: #fff;">${{ number_format($price->price, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="sticky top-12" style="background: #000; border: 1px solid #1a1a1a; padding: 2.5rem;">
                    <div style="border-top: 1px solid #27272a; padding-top: 1rem; margin-bottom: 3rem; text-align: center;">
                        <span style="font-size: 9px; font-weight: 900; color: #52525b; text-transform: uppercase; letter-spacing: 0.8em;">Stage Area</span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(10, 1fr); gap: 6px; margin-bottom: 2.5rem;">
                        @foreach($concert->concertSeats->sortBy(fn($s) => $s->seat->seat_number) as $concertSeat)
                            @php
                                $isAvailable = $concertSeat->status === 'available';
                                $price = $concert->ticketPrices->where('section', $concertSeat->seat->section)->first()->price ?? 0;
                            @endphp
                            <button type="button"
                                @click="selectSeat('{{ $concertSeat->seat->seat_number }}', '{{ $concertSeat->seat->section }}', '{{ number_format($price, 2) }}')"
                                :class="selectedSeat === '{{ $concertSeat->seat->seat_number }}' 
                                    ? 'bg-zinc-700 text-white border border-zinc-500' 
                                    : '{{ $isAvailable ? 'bg-zinc-900 text-zinc-500 hover:bg-zinc-800 hover:text-white' : 'border border-zinc-900 text-zinc-900 cursor-not-allowed' }}'"
                                style="aspect-ratio: 1/1; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 900; transition: all 0.2s; border: none; cursor: pointer;"
                                {{ !$isAvailable ? 'disabled' : '' }}>
                                {{ $concertSeat->seat->seat_number }}
                            </button>
                        @endforeach
                    </div>

                    <div style="display: flex; justify-content: start; gap: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #18181b;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 8px; height: 8px; background: #18181b;"></div>
                            <span style="font-size: 8px; font-weight: 700; color: #71717a; text-transform: uppercase;">Available</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 8px; height: 8px; background: #3f3f46;"></div>
                            <span style="font-size: 8px; font-weight: 700; color: #71717a; text-transform: uppercase;">Selected</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-6" style="margin-bottom: 6rem; display: flex; flex-direction: column; gap: 1rem;">
            
            <div x-show="selectedSeat" x-transition 
                 style="background: #0a0a0a; border: 1px solid #1a1a1a; padding: 2.5rem; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p style="font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.2em; color: #555; margin-bottom: 0.5rem;">Booking Confirmation</p>
                    <h4 style="font-size: 2rem; font-weight: 900; text-transform: uppercase; color: #fff;">Section <span x-text="selectedSection"></span> — Seat #<span x-text="selectedSeat"></span></h4>
                </div>
                <div style="text-align: right;">
                    <p style="font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.2em; color: #555; margin-bottom: 0.5rem;">Total Admission</p>
                    <h4 style="font-size: 2.5rem; font-weight: 900; letter-spacing: -0.05em; color: #fff;">$<span x-text="selectedPrice"></span></h4>
                </div>
            </div>

            @auth
                <a :href="selectedSeat ? `{{ route('bookings.create', ['concert' => $concert->id]) }}?seat=${selectedSeat}` : '#'" :class="!selectedSeat && 'opacity-20 pointer-events-none'"class="btn-primary" style="width: 100%; height: 80px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 900; letter-spacing: 0.4em; border-radius: 0;  color: #000; border: none; transition: 0.3s; cursor: pointer;">
                    <span x-text="selectedSeat ? 'PROCEED TO BOOKING' : 'SELECT A SEAT TO BOOK'"></span>
                </a>

            @else

                <a href="{{ route('login') }}" style="width: 100%; height: 80px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 900; letter-spacing: 0.4em; border: 1px solid #27272a; color: #fff; text-decoration: none;">
                    LOGIN TO BOOK
                </a>
            @endauth
        </div>
    </div>
</x-app-layout>