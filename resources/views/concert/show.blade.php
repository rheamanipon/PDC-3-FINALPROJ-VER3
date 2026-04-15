<x-app-layout>
    <div>
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
                <div class="sticky top-12" style="background: #05050a; border: 1px solid #1a1a1a; padding: 2.5rem;">
                    <div style="margin-bottom: 2rem; text-align: center;">
                        <div style="margin: 0 auto 1rem auto; width: 240px; padding: 1rem 0; border-radius: 1.5rem; border: 1px solid rgba(148, 163, 184, 0.25);">
                            <span style="font-size: 0.9rem; font-weight: 800; letter-spacing: 0.15em; text-transform: uppercase; color: #e0e7ff;">Stage</span>
                        </div>
                        <p style="margin: 0; font-size: 0.76rem; font-weight: 700; color: #94a3b8; letter-spacing: 0.15em; text-transform: uppercase;">Seating layout</p>
                    </div>

                    @php
                        $sections = $concert->concertSeats->groupBy(fn($seat) => $seat->seat->section);
                        $orderedSections = collect();
                        $order = ['floor', 'lower', 'upper', 'balcony'];
                        foreach ($order as $orderName) {
                            foreach ($sections as $name => $group) {
                                if (str_contains(strtolower($name), $orderName) && !$orderedSections->has($name)) {
                                    $orderedSections->put($name, $group);
                                }
                            }
                        }
                        foreach ($sections as $name => $group) {
                            if (!$orderedSections->has($name)) {
                                $orderedSections->put($name, $group);
                            }
                        }
                    @endphp

                    @foreach($orderedSections as $section => $seats)
                        <div style="margin-bottom: 0.75rem; background: #0b0b16; border: 1px solid rgba(148, 163, 184, 0.12); border-radius: 1.25rem; padding: 0.75rem;">
                            <div style="display: grid; grid-template-columns: repeat(10, minmax(16px, 1fr)); gap: 3px; margin-bottom: 0.75rem;">
                                @foreach($seats->sortBy(fn($s) => $s->seat->seat_number) as $concertSeat)
                                    @php
                                        $seatStyle = $concertSeat->status === 'available'
                                            ? 'background: rgba(129, 140, 248, 0.18); border: 1px solid rgba(129, 140, 248, 0.35);'
                                            : 'background: rgba(71, 85, 105, 0.25); border: 1px solid rgba(71, 85, 105, 0.45);';
                                    @endphp
                                        <div style="aspect-ratio: 1.3 / 1; border-radius: 0.65rem; {{ $seatStyle }} display: flex; align-items: center; justify-content: center; font-size: 0.55rem; font-weight: 700; color: #fff;">{{ preg_replace('/[^0-9]/', '', $concertSeat->seat->seat_number) }}</div>
                                @endforeach
                            </div>
                            <div style="text-align: center; color: #94a3b8; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;">
                                {{ $section }}
                            </div>
                        </div>
                    @endforeach

                    <div style="display: flex; justify-content: start; gap: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(148, 163, 184, 0.12);">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 10px; height: 10px; border-radius: 9999px; background: rgba(129, 140, 248, 0.5);"></div>
                            <span style="font-size: 0.75rem; font-weight: 700; color: #c7d2fe; text-transform: uppercase; letter-spacing: 0.08em;">Available</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 10px; height: 10px; border-radius: 9999px; background: rgba(71, 85, 105, 0.7);"></div>
                            <span style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em;">Unavailable</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-6" style="margin-bottom: 6rem; display: flex; flex-direction: column; gap: 1rem;">
            
            @auth
                <a href="{{ route('bookings.create', ['concert' => $concert->id]) }}" class="btn-primary" style="width: 100%; height: 80px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 900; letter-spacing: 0.4em; border-radius: 0; color: #000; border: none; transition: 0.3s; cursor: pointer;">
                    SELECT A SEAT
                </a>
            @else
                <a href="{{ route('login') }}" style="width: 100%; height: 80px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 900; letter-spacing: 0.4em; border: 1px solid #27272a; color: #fff; text-decoration: none;">
                    LOGIN TO BOOK
                </a>
            @endauth
        </div>
    </div>
</x-app-layout>