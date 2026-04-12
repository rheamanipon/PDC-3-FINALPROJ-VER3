<x-app-layout>
    <!-- HERO SECTION -->
    <div style="margin-bottom: 4rem;">
        <h1 class="page-title" style="font-size: 4.5rem; margin-bottom: 1rem;">LIVE MUSIC AWAITS</h1>
        <p class="page-subtitle" style="font-size: 1.5rem; color: var(--accent-primary); font-weight: 600;">Book Your Tickets Now</p>
    </div>

    @if($concerts->count() > 0)
        <div class="grid-3 mb-12">
            @foreach($concerts->take(6) as $concert)
                <a href="{{ route('concerts.show', $concert) }}" class="concert-card" style="text-decoration: none; display: block; transition: all 0.3s ease;">
                    <div class="card">
                        <!-- Event Image Placeholder -->
                        <div class="card-image" style="background: linear-gradient(135deg, rgba(255, 102, 0, 0.8), rgba(255, 20, 147, 0.6)), url('https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=500&h=280&fit=crop'); background-size: cover; background-position: center; display: flex; align-items: flex-end; padding: 1.5rem; color: white; position: relative;"></div>
                        
                        <div class="card-header" style="padding: 1.5rem 1.5rem 0 1.5rem;">
                            <div style="width: 100%;">
                                <h3 class="card-title" style="font-size: 1.4rem;">{{ $concert->title }}</h3>
                                <p class="card-subtitle" style="color: var(--accent-primary); font-weight: 600;">{{ $concert->artist }}</p>
                            </div>
                        </div>

                        <div class="card-body" style="padding: 1rem 1.5rem;">
                            <p style="color: var(--text-secondary); margin-bottom: 0.5rem; font-weight: 500;">📍 {{ $concert->venue->location }}</p>
                            <p style="color: var(--accent-primary); margin-bottom: 0.75rem; font-weight: 600; font-size: 0.95rem;">📅 {{ $concert->date->format('M d, Y') }} • {{ $concert->time->format('g:i A') }}</p>
                            <p style="color: var(--text-tertiary); font-size: 0.875rem;">{{ Str::limit($concert->description, 80) }}</p>
                        </div>

                        <div class="card-footer" style="justify-content: center;">
                            <span class="btn btn-primary" style="width: auto;">BOOK NOW</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="card" style="padding: 3rem; text-align: center;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🎭</div>
            <h3 style="font-size: 2rem; margin-bottom: 0.5rem; text-transform: uppercase;">No Concerts Available</h3>
            <p style="color: var(--text-secondary); font-size: 1.1rem;">Check back soon for upcoming events!</p>
        </div>
    @endif

    @if($concerts->count() > 6)
        <div style="display: flex; justify-content: center; gap: 1.5rem; margin-top: 3rem;">
            <button class="btn btn-outline">← Previous</button>
            <button class="btn btn-primary">Next →</button>
        </div>
    @endif
</x-app-layout>
