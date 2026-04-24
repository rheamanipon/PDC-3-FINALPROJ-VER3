<x-app-layout>
    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 3rem;">
            <h1 style="font-size: 3rem; font-weight: 800; color: #ffffff; margin-bottom: 0.5rem;">ALL CONCERTS</h1>
            <p style="color: #888; font-size: 1.125rem;">Discover and book tickets for upcoming concerts</p>
        </div>

        <!-- Filters -->
        <div style="background: #0a0a0a; border: 1px solid #1a1a1a; border-radius: 0.5rem; padding: 2rem; margin-bottom: 3rem;">
            <form method="GET" action="{{ route('concerts.index') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <div>
                    <label for="search" style="display: block; color: #555; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.1em; margin-bottom: 0.75rem;">Search</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Concert or artist name" style="width: 100%; padding: 0.75rem; background: #111; border: 1px solid #333; border-radius: 0.25rem; color: #fff; font-size: 0.9rem;">
                </div>
                <div>
                    <label for="location" style="display: block; color: #555; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.1em; margin-bottom: 0.75rem;">Location</label>
                    <select name="location" id="location" style="width: 100%; padding: 0.75rem; background: #111; border: 1px solid #333; border-radius: 0.25rem; color: #fff; font-size: 0.9rem;">
                        <option value="" style="background: #111; color: #fff;">All Locations</option>
                        @foreach($locations as $location)
                            <option value="{{ $location }}" {{ request('location') == $location ? 'selected' : '' }} style="background: #111; color: #fff;">{{ $location }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date" style="display: block; color: #555; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.1em; margin-bottom: 0.75rem;">Date</label>
                    <input type="date" name="date" id="date" value="{{ request('date') }}" style="width: 100%; padding: 0.75rem; background: #111; border: 1px solid #333; border-radius: 0.25rem; color: #fff; font-size: 0.9rem;">
                </div>
                <div style="display: flex; align-items: end;">
                    <button type="submit" style="width: 100%; padding: 0.75rem; background: #ff6600; border: none; border-radius: 0.25rem; color: #000; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; cursor: pointer; transition: background 0.3s;">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Concerts Grid -->
        @if($concerts->count() > 0)
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
                @foreach($concerts as $concert)
                    <div style="background: #0a0a0a; border: 1px solid #1a1a1a; border-radius: 0.5rem; overflow: hidden; transition: all 0.3s ease; display: flex; flex-direction: column; height: 100%;">
                        @if($concert->poster_url)
                            <div style="width: 100%; height: 200px; background: url('{{ asset('storage/' . $concert->poster_url) }}'); background-size: cover; background-position: center;"></div>
                        @else
                            <div style="width: 100%; height: 200px; background: linear-gradient(135deg, #ff6600, #ff1493); display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                                🎤
                            </div>
                        @endif
                        <div style="padding: 1.5rem; display: flex; flex-direction: column; flex: 1;">
                            <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: #ffffff;">{{ $concert->title }}</h3>
                            <p style="color: #ff6600; font-weight: 600; margin-bottom: 0.5rem;">by {{ $concert->artist }}</p>
                            <p style="color: #777; font-size: 0.875rem; margin-bottom: 0.5rem;">{{ $concert->venue->location }}</p>
                            <p style="color: #888; font-size: 0.875rem; margin-bottom: auto;">{{ $concert->date->format('M d, Y') }} at {{ $concert->time->format('g:i A') }}</p>
                            <a href="{{ route('concerts.show', $concert) }}" style="display: flex; align-items: center; justify-content: center; width: 100%; height: 45px; background: #ff6600; border: none; border-radius: 0.25rem; color: #000; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; text-decoration: none; transition: background 0.3s;">
                                Book Now
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div style="text-align: center;">
                {{ $concerts->links() }}
            </div>
        @else
            <div style="text-align: center; padding: 4rem 2rem;">
                <div style="font-size: 4rem; margin-bottom: 2rem;">🎟️</div>
                <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; color: #ffffff;">No concerts found</h3>
                <p style="color: #888; font-size: 1rem;">Try adjusting your filters or check back later for new events.</p>
            </div>
        @endif
    </div>
</x-app-layout></content>
<parameter name="filePath">c:\xampp\htdocs\Updated_PDC03_FinalProject\resources\views\concert\index.blade.php