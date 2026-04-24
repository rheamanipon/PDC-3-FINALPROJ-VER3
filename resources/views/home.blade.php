<x-app-layout>
    <!-- HERO SECTION -->
    <section style="max-width: 1400px; margin: 0 auto; padding: 3rem 2rem; text-align: center;">
        <h1 style="font-size: 3.5rem; font-weight: 800; margin-bottom: 1rem; color: #ffffff;">Book Tickets Of Your Favorite Singers!</h1>
        <p style="font-size: 1.125rem; color: #ff6600; font-weight: 600; margin-bottom: 3rem;">Make Your Night Unforgettable</p>
    </section>

    <!-- CAROUSEL SECTION -->
    <section style="max-width: 1400px; margin: 0 auto; padding: 2rem 2rem;">
        <div style="display: flex; gap: 1.5rem; overflow-x: auto; padding-bottom: 1rem;">
            @foreach($concerts->take(8) as $concert)
                <div style="flex: 0 0 300px; background-color: #1a1a1a; border-radius: 0.5rem; overflow: hidden; cursor: pointer; transition: all 0.3s ease; border: 1px solid #2d2d2d; display: flex; flex-direction: column;">
                    @if($concert->poster_url)
                    <div style="width: 100%; height: 200px; background: url('{{ asset('storage/' . $concert->poster_url) }}'); background-size: cover; background-position: center; display: flex; align-items: flex-end; padding: 1.5rem; color: white; font-size: 2rem;">
                        &nbsp;
                    </div>
                @else
                    <div style="width: 100%; height: 200px; background: linear-gradient(135deg, rgba(255, 102, 0, 0.8), rgba(255, 20, 147, 0.6)); display: flex; align-items: flex-end; padding: 1.5rem; color: white; font-size: 2rem;">
                        🎤
                    </div>
                @endif
                    <div style="padding: 1.5rem; display: flex; flex-direction: column; flex: 1;">
                        <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: #ffffff;">{{ $concert->title }}</h3>
                        <p style="color: #ff6600; font-weight: 600; margin-bottom: 0.5rem;">{{ $concert->artist }}</p>
                        <p style="color: #d0d0d0; font-size: 0.875rem; margin-bottom: auto;">{{ $concert->date->format('M d, Y') }}</p>
                        <a href="{{ route('concerts.show', $concert) }}" class="btn btn-primary" style="width: 100%; margin: 0; display: inline-flex; justify-content: center; height: 45px; align-items: center;">Book Now</a>
                    </div>
                </div>
            @endforeach
        </div>
        <div style="text-align: center; margin-top: 2rem;">
            <a href="{{ route('concerts.index') }}" class="btn btn-primary" style="padding: 0.75rem 3rem; font-size: 1.1rem;">View All Concerts</a>
        </div>
    </section>

    <!-- OUR BENEFITS SECTION -->
    <section style="max-width: 1400px; margin: 0 auto; padding: 3rem 2rem;">
        <h2 style="font-size: 2rem; font-weight: 700; text-align: center; margin-bottom: 3rem; color: #ffffff;">Our Benefits</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
            <div style="background-color: #1a1a1a; padding: 2rem; border-radius: 0.5rem; text-align: center; border: 1px solid #2d2d2d;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">⚡</div>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: #ffffff;">Instant Payment</h3>
                <p style="color: #a0a0a0; font-size: 0.875rem;">We provide a safe & secure payment platform.</p>
            </div>
            <div style="background-color: #1a1a1a; padding: 2rem; border-radius: 0.5rem; text-align: center; border: 1px solid #2d2d2d;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🛒</div>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: #ffffff;">Online Booking</h3>
                <p style="color: #a0a0a0; font-size: 0.875rem;">You can pay tickets directly from your home</p>
            </div>
            <div style="background-color: #1a1a1a; padding: 2rem; border-radius: 0.5rem; text-align: center; border: 1px solid #2d2d2d;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">♻️</div>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: #ffffff;">Refundable Tickets</h3>
                <p style="color: #a0a0a0; font-size: 0.875rem;">If any problem occurs, we refund your ticket.</p>
            </div>
            <div style="background-color: #1a1a1a; padding: 2rem; border-radius: 0.5rem; text-align: center; border: 1px solid #2d2d2d;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">💰</div>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: #ffffff;">Cheapest Tickets</h3>
                <p style="color: #a0a0a0; font-size: 0.875rem;">We offer the best prices you'll find anywhere else</p>
            </div>
        </div>
    </section>

    <!-- TIME IS RUNNING OUT SECTION -->
    <section style="max-width: 1400px; margin: 0 auto; padding: 3rem 2rem;">
        <h2 style="font-size: 2rem; font-weight: 700; text-align: center; margin-bottom: 1rem; color: #ffffff;">Time is Running Out!</h2>
        <p style="text-align: center; color: #a0a0a0; margin-bottom: 3rem;">Explore newly released events and book your tickets</p>
        
        <div style="display: flex; gap: 1.5rem; overflow-x: auto; padding-bottom: 1rem;">
            @foreach($concerts->take(5) as $concert)
                <div style="flex: 0 0 280px; border-radius: 0.5rem; overflow: hidden; border: 1px solid #2d2d2d; cursor: pointer; transition: all 0.3s ease; background-color: #1a1a1a; display: flex; flex-direction: column;">
                    @if($concert->poster_url)
                        <div style="width: 100%; height: 180px; background: url('{{ asset('storage/' . $concert->poster_url) }}'); background-size: cover; background-position: center;"></div>
                    @else
                        <div style="width: 100%; height: 180px; background: linear-gradient(135deg, #ff6600, #ff1493); display: flex; align-items: center; justify-content: center; font-size: 4rem;">
                            🎵
                        </div>
                    @endif
                    <div style="padding: 1.5rem; display: flex; flex-direction: column; flex: 1;">
                        <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: #ffffff;">{{ $concert->artist }}</h3>
                        <p style="color: #a0a0a0; font-size: 0.875rem; margin-bottom: auto;">{{ $concert->date->format('M d, Y') }}</p>
                        <a href="{{ route('concerts.show', $concert) }}" class="btn btn-secondary" style="width: 100%; margin: 0; display: inline-flex; justify-content: center; height: 45px; align-items: center;">Book Now</a>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <!-- 4 EASY STEPS SECTION -->
    <section style="max-width: 1400px; margin: 0 auto; padding: 3rem 2rem;">
        <h2 style="font-size: 2rem; font-weight: 700; text-align: center; margin-bottom: 3rem; color: #ffffff;">4 Easy Steps To Buy a Ticket!</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; text-align: center;">
            <div style="background-color: #1a1a1a; padding: 2rem; border-radius: 0.5rem; border: 1px solid #2d2d2d;">
                <div style="width: 60px; height: 60px; background-color: #ff6600; border-radius: 50%; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; color: #000;">1</div>
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; color: #ffffff;">Choose a Concert</h3>
                <p style="color: #a0a0a0; font-size: 0.875rem;">Select your favorite concert from our list</p>
            </div>
            <div style="background-color: #1a1a1a; padding: 2rem; border-radius: 0.5rem; border: 1px solid #2d2d2d;">
                <div style="width: 60px; height: 60px; background-color: #ff1493; border-radius: 50%; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; color: #000;">2</div>
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; color: #ffffff;">Choose Date & Time</h3>
                <p style="color: #a0a0a0; font-size: 0.875rem;">Pick the date and time that works best for you</p>
            </div>
            <div style="background-color: #1a1a1a; padding: 2rem; border-radius: 0.5rem; border: 1px solid #2d2d2d;">
                <div style="width: 60px; height: 60px; background-color: #00d9ff; border-radius: 50%; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; color: #000;">3</div>
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; color: #ffffff;">Pay Your Bill</h3>
                <p style="color: #a0a0a0; font-size: 0.875rem;">Complete the payment securely on our platform</p>
            </div>
            <div style="background-color: #1a1a1a; padding: 2rem; border-radius: 0.5rem; border: 1px solid #2d2d2d;">
                <div style="width: 60px; height: 60px; background-color: #ff6600; border-radius: 50%; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; color: #000;">4</div>
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; color: #ffffff;">Download Your Ticket</h3>
                <p style="color: #a0a0a0; font-size: 0.875rem;">Get your ticket and enjoy the concert!</p>
            </div>
        </div>
    </section>
</x-app-layout>
