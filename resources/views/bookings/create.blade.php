<x-app-layout>
    <div style="margin-bottom: 2rem;">
        <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; align-items: center;">
            <div style="text-align: center; padding: 1rem; background: rgba(255, 102, 0, 0.08); border-left: 4px solid var(--accent-primary);">
                <p style="font-size: 0.75rem; letter-spacing: 0.15em; text-transform: uppercase; margin-bottom: 0.5rem; color: var(--text-tertiary);">Step 1</p>
                <h3 style="margin: 0; font-size: 1rem; font-weight: 700;">Choose Seat</h3>
            </div>
            <div style="text-align: center; padding: 1rem; background: rgba(0, 0, 0, 0.03); border-left: 4px solid transparent;">
                <p style="font-size: 0.75rem; letter-spacing: 0.15em; text-transform: uppercase; margin-bottom: 0.5rem; color: var(--text-tertiary);">Step 2</p>
                <h3 style="margin: 0; font-size: 1rem; font-weight: 700;">Review Order</h3>
            </div>
            <div style="text-align: center; padding: 1rem; background: rgba(0, 0, 0, 0.03); border-left: 4px solid transparent;">
                <p style="font-size: 0.75rem; letter-spacing: 0.15em; text-transform: uppercase; margin-bottom: 0.5rem; color: var(--text-tertiary);">Step 3</p>
                <h3 style="margin: 0; font-size: 1rem; font-weight: 700;">Checkout</h3>
            </div>
            <div style="text-align: center; padding: 1rem; background: rgba(0, 0, 0, 0.03); border-left: 4px solid transparent;">
                <p style="font-size: 0.75rem; letter-spacing: 0.15em; text-transform: uppercase; margin-bottom: 0.5rem; color: var(--text-tertiary);">Step 4</p>
                <h3 style="margin: 0; font-size: 1rem; font-weight: 700;">Get Ticket</h3>
            </div>
        </div>
    </div>

    <div style="margin-bottom: 3rem;">
        <p style="color: var(--accent-primary); font-weight: 700; text-transform: uppercase; font-size: 0.875rem; letter-spacing: 0.1em; margin-bottom: 0.5rem;">Seat Selection</p>
        <h1 class="page-title" style="font-size: 3.5rem;">CHOOSE YOUR SEATS</h1>
        <p style="color: var(--text-secondary); font-size: 1.1rem; margin-top: 1rem;">{{ $concert->title }} • {{ $concert->date->format('M d, Y') }}</p>
    </div>

    <div class="grid-2 gap-8">
        <!-- MAIN: SEAT SELECTION FORM -->
        <div class="card no-hover">
            <div class="card-header">
                <div>
                    <h3 class="card-title">AVAILABLE SEATS</h3>
                    <p style="color: var(--text-secondary); font-size: 0.95rem;">{{ $concert->venue->name }}</p>
                </div>
            </div>

            <form action="{{ route('bookings.store', $concert) }}" method="POST">
                @csrf
                @php $priceMap = $concert->ticketPrices->pluck('price', 'section'); @endphp

                <div class="card-body">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                        <div style="min-width: 220px;">
                            <label for="ticket_quantity" style="display: block; font-weight: 700; margin-bottom: 0.5rem;">Ticket Quantity</label>
                            <select id="ticket_quantity" name="ticket_quantity" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid rgba(0,0,0,0.12); border-radius: 0; font-size: 0.95rem;">
                                @for($i = 1; $i <= 5; $i++)
                                    <option value="{{ $i }}">{{ $i }} Ticket{{ $i > 1 ? 's' : '' }}</option>
                                @endfor
                            </select>
                        </div>
                        <div style="flex: 1; min-width: 240px; color: var(--text-secondary);">
                            <p style="margin: 0 0 0.25rem; font-weight: 700;">Select up to 5 available seats.</p>
                            <p style="margin: 0; font-size: 0.95rem;">Your chosen seat count must match the ticket quantity.</p>
                        </div>
                    </div>

                    <!-- Seat Map -->
                    <div style="margin-bottom: 2rem;">
                        <div style="margin-bottom: 1.5rem; padding: 1rem; border: 1px solid rgba(148,163,184,0.12); border-radius: 1rem; text-align: center;">
                            <span style="display: inline-block; padding: 0.85rem 1.5rem; border-radius: 9999px; color: var(--accent-primary); font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase;">Stage</span>
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
                            <div style="margin-bottom: 0.75rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(148,163,184,0.12); border-radius: 1rem; padding: 0.75rem;">
                                <div style="display: grid; grid-template-columns: repeat(10, minmax(16px, 1fr)); gap: 0.4rem; margin-bottom: 0.75rem;">
                                    @foreach($seats->sortBy(fn($s) => $s->seat->seat_number) as $concertSeat)
                                        @php
                                            $seatStatus = $concertSeat->status;
                                            $seatPrice = $priceMap[$concertSeat->seat->section] ?? 0;
                                            $selectable = $seatStatus === 'available';
                                        @endphp
                                        <label style="display: block; cursor: {{ $selectable ? 'pointer' : 'not-allowed' }};">
                                            <input
                                                type="checkbox"
                                                name="seat_ids[]"
                                                value="{{ $concertSeat->id }}"
                                                data-price="{{ $seatPrice }}"
                                                data-section="{{ $concertSeat->seat->section }}"
                                                data-seat-number="{{ $concertSeat->seat->seat_number }}"
                                                style="display: none;"
                                                @if(!$selectable) disabled @endif
                                            />
                                            <div class="seat-block {{ $seatStatus }}" style="aspect-ratio: 1.3 / 1;">
                                                <span style="font-size: 0.72rem; font-weight: 700;">{{ preg_replace('/[^0-9]/', '', $concertSeat->seat->seat_number) }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                <div style="text-align: center; color: var(--text-secondary); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;">
                                    {{ $section }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Legend -->
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; padding: 1.5rem; background-color: rgba(20, 20, 20, 0.85); border-left: 3px solid var(--accent-primary);">
                        <div style="text-align: center;">
                            <div style="width: 20px; height: 20px; background-color: #ff6600; margin: 0 auto 0.5rem; border-radius: 0;"></div>
                            <p style="font-size: 0.75rem; color: #ddd; text-transform: uppercase;">Available</p>
                        </div>
                        <div style="text-align: center;">
                            <div style="width: 20px; height: 20px; background-color: #4b5563; margin: 0 auto 0.5rem; border-radius: 0;"></div>
                            <p style="font-size: 0.75rem; color: #ddd; text-transform: uppercase;">Reserved</p>
                        </div>
                        <div style="text-align: center;">
                            <div style="width: 20px; height: 20px; background-color: #111827; margin: 0 auto 0.5rem; border-radius: 0;"></div>
                            <p style="font-size: 0.75rem; color: #ddd; text-transform: uppercase;">Sold</p>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button id="checkout-button" type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">REVIEW ORDER</button>
                </div>
            </form>
        </div>

        <!-- SIDEBAR: ORDER SUMMARY -->
        <div class="card no-hover">
            <div class="card-header">
                <h3 class="card-title">ORDER SUMMARY</h3>
            </div>

            <div class="card-body">
                <div style="display: grid; gap: 1rem; margin-bottom: 1.5rem; padding: 1rem; background-color: rgba(255, 102, 0, 0.05); border-left: 4px solid var(--accent-primary);">
                    <div style="display: flex; justify-content: space-between; color: var(--text-secondary);">
                        <span>Tickets Selected</span>
                        <span id="selected-count">0</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; color: var(--text-secondary);">
                        <span>Seat IDs</span>
                        <span id="selected-seats">None</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-weight: 700;">
                        <span>Total</span>
                        <span id="selected-total">$0.00</span>
                    </div>
                </div>

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

    <style>
        .seat-block {
            aspect-ratio: 1.4 / 1;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            min-height: 40px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .seat-block.available {
            background: #ff6600;
            border: 1px solid #ff6600;
            color: #000;
        }

        .seat-block.reserved {
            background: #4b5563;
            border: 1px solid #4b5563;
            color: #fff;
            opacity: 0.9;
        }

        .seat-block.sold {
            background: #111827;
            border: 1px solid #111827;
            color: #fff;
            opacity: 0.9;
        }

        .seat-block.available:hover {
            transform: translateY(-2px);
        }

        .seat-block.selected {
            border-color: rgba(59, 130, 246, 0.75);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18);
            background: rgba(59, 130, 246, 0.35);
        }

        .card.no-hover:hover {
            transform: none !important;
            box-shadow: var(--shadow-md) !important;
        }

        .card.no-hover:hover::after {
            opacity: 0 !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const quantitySelect = document.getElementById('ticket_quantity');
            const seatInputs = Array.from(document.querySelectorAll('input[name="seat_ids[]"]'));
            const selectedCount = document.getElementById('selected-count');
            const selectedSeats = document.getElementById('selected-seats');
            const selectedTotal = document.getElementById('selected-total');
            const checkoutButton = document.getElementById('checkout-button');

            function updateSummary() {
                const selected = seatInputs.filter(input => input.checked);
                const maxTickets = parseInt(quantitySelect.value, 10);
                const totalPrice = selected.reduce((sum, input) => sum + Number(input.dataset.price || 0), 0);
                selectedCount.textContent = selected.length;
                selectedSeats.textContent = selected.length ? selected.map(input => input.dataset.seatNumber).join(', ') : 'None';
                selectedTotal.textContent = `$${totalPrice.toFixed(2)}`;

                seatInputs.forEach(input => {
                    const seatCard = input.closest('label').querySelector('.seat-block');
                    seatCard?.classList.toggle('selected', input.checked);
                });

                if (selected.length > maxTickets) {
                    checkoutButton.disabled = true;
                    checkoutButton.textContent = 'Reduce seats to proceed';
                } else if (selected.length === 0) {
                    checkoutButton.disabled = true;
                    checkoutButton.textContent = 'Select seats to proceed';
                } else {
                    checkoutButton.disabled = false;
                    checkoutButton.textContent = 'PROCEED TO CHECKOUT';
                }
            }

            function enforceLimits(event) {
                const selected = seatInputs.filter(input => input.checked);
                const maxTickets = parseInt(quantitySelect.value, 10);
                if (selected.length > maxTickets) {
                    event.target.checked = false;
                    alert(`You can only select up to ${maxTickets} seat${maxTickets > 1 ? 's' : ''}.`);
                }
            }

            quantitySelect.addEventListener('change', function () {
                const maxTickets = parseInt(this.value, 10);
                const selected = seatInputs.filter(input => input.checked);
                if (selected.length > maxTickets) {
                    selected.slice(maxTickets).forEach(input => {
                        input.checked = false;
                        input.closest('label').querySelector('.seat-btn')?.classList.remove('selected');
                    });
                    alert(`Ticket quantity updated to ${maxTickets}. Extra seats were unselected.`);
                }
                updateSummary();
            });

            seatInputs.forEach(input => {
                input.addEventListener('change', function (event) {
                    enforceLimits(event);
                    updateSummary();
                });
            });

            updateSummary();
        });
    </script>
</x-app-layout>
