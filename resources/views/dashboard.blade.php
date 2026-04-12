<x-app-layout>
    <div style="margin-bottom: 4rem;">
        <h1 class="page-title" style="font-size: 3.5rem;">DASHBOARD</h1>
        <p class="page-subtitle" style="font-size: 1.2rem; color: var(--accent-primary);">Welcome, {{ auth()->user()->name }}</p>
    </div>

    @if(auth()->user()->role === 'admin')
        <!-- ADMIN STATS -->
        <div class="grid-3 mb-12">
            <div class="card">
                <div class="card-body" style="padding: 2rem 1.5rem;">
                    <p style="color: var(--text-tertiary); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.1em; margin-bottom: 0.5rem;">Total Users</p>
                    <p style="font-size: 2.5rem; font-weight: 800; color: var(--accent-primary); margin-bottom: 0.5rem;">{{ \App\Models\User::where('role', 'user')->count() }}</p>
                    <p style="color: var(--text-tertiary); font-size: 0.875rem;">Regular users</p>
                </div>
            </div>

            <div class="card">
                <div class="card-body" style="padding: 2rem 1.5rem;">
                    <p style="color: var(--text-tertiary); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.1em; margin-bottom: 0.5rem;">Active Events</p>
                    <p style="font-size: 2.5rem; font-weight: 800; color: var(--accent-secondary); margin-bottom: 0.5rem;">{{ \App\Models\Concert::count() }}</p>
                    <p style="color: var(--text-tertiary); font-size: 0.875rem;">Concerts live</p>
                </div>
            </div>

            <div class="card">
                <div class="card-body" style="padding: 2rem 1.5rem;">
                    <p style="color: var(--text-tertiary); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.1em; margin-bottom: 0.5rem;">Total Bookings</p>
                    <p style="font-size: 2.5rem; font-weight: 800; color: var(--accent-tertiary); margin-bottom: 0.5rem;">{{ \App\Models\Booking::count() }}</p>
                    <p style="color: var(--text-tertiary); font-size: 0.875rem;">Reservations made</p>
                </div>
            </div>
        </div>

        <!-- ADMIN ACTIONS -->
        <div class="grid-2 gap-8" style="margin-bottom: 3rem;">
            <a href="{{ route('admin.concerts.index') }}" style="text-decoration: none;">
                <div class="card" style="padding: 2.5rem; text-align: center; cursor: pointer; position: relative;">
                    <div style="font-size: 3.5rem; margin-bottom: 1.5rem;">🎤</div>
                    <h3 class="card-title" style="font-size: 1.75rem; margin-bottom: 0.5rem;">MANAGE CONCERTS</h3>
                    <p style="color: var(--text-tertiary); text-transform: uppercase; font-size: 0.875rem;">Create, edit & delete events</p>
                </div>
            </a>

            <a href="{{ route('admin.venues.index') }}" style="text-decoration: none;">
                <div class="card" style="padding: 2.5rem; text-align: center; cursor: pointer;">
                    <div style="font-size: 3.5rem; margin-bottom: 1.5rem;">🏛️</div>
                    <h3 class="card-title" style="font-size: 1.75rem; margin-bottom: 0.5rem;">MANAGE VENUES</h3>
                    <p style="color: var(--text-tertiary); text-transform: uppercase; font-size: 0.875rem;">Configure venues & capacities</p>
                </div>
            </a>
        </div>
    @else
        <!-- USER DASHBOARD -->
        <div class="grid-2 gap-8">
            <div class="card">
                <div class="card-header" style="padding: 1.5rem;">
                    <h3 class="card-title" style="font-size: 1.5rem;">QUICK ACTIONS</h3>
                </div>
                <div class="card-body" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
                    <a href="{{ route('home') }}" class="btn btn-primary" style="width: 100%; text-align: center;">Browse Concerts</a>
                    <a href="{{ route('bookings.index') }}" class="btn btn-secondary" style="width: 100%; text-align: center;">My Bookings</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header" style="padding: 1.5rem;">
                    <h3 class="card-title" style="font-size: 1.5rem;">ACTIVITY</h3>
                </div>
                <div class="card-body" style="padding: 1.5rem; text-align: center;">
                    <p style="color: var(--text-tertiary); font-size: 0.95rem;">No bookings yet. Start exploring concerts now!</p>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
