<x-app-layout>
    <section class="admin-dashboard" id="adminDashboard">
        <div class="admin-shell">
            @include('admin.partials.sidebar')

            <main class="admin-main">
                @include('admin.partials.flash')
                <header class="admin-header" id="dashboard-overview">
                    <div>
                        <h2>Welcome back, {{ auth()->user()->name ?? 'Admin' }}</h2>
                        <p>Review operational performance, platform health, and current booking activity.</p>
                    </div>
                    <div class="admin-header-actions">
                        <button type="button" class="ad-btn ad-icon-btn" id="themeToggleBtn" aria-label="Toggle theme" title="Toggle theme">
                            <span id="themeToggleIcon" aria-hidden="true">◐</span>
                        </button>
                        <a href="{{ route('admin.concerts.create') }}" class="ad-btn ad-btn-primary">Create Concert</a>
                    </div>
                </header>

                <section class="ad-grid ad-summary-grid">
                    <article class="ad-card">
                        <p class="ad-card-label">Tickets Sold</p>
                        <p class="ad-card-value">{{ number_format($metrics['tickets_sold']) }}</p>
                        <p class="ad-card-trend">Live from sold concert seats</p>
                    </article>
                    <article class="ad-card">
                        <p class="ad-card-label">Revenue</p>
                        <p class="ad-card-value">₱{{ number_format($metrics['revenue'], 2) }}</p>
                        <p class="ad-card-trend">Paid transactions</p>
                    </article>
                    <article class="ad-card">
                        <p class="ad-card-label">Active Users</p>
                        <p class="ad-card-value">{{ number_format($metrics['users']) }}</p>
                        <p class="ad-card-trend">Registered accounts</p>
                    </article>
                    <article class="ad-card">
                        <p class="ad-card-label">Total Concerts</p>
                        <p class="ad-card-value">{{ number_format($metrics['concerts']) }}</p>
                        <p class="ad-card-trend">{{ number_format($metrics['bookings']) }} bookings recorded</p>
                    </article>
                </section>

                <section class="ad-grid ad-two-col" style="margin-top: 1rem;">
                    <article class="ad-card">
                        <h3 class="ad-panel-title">Sales Trend (Line Chart)</h3>
                        <div class="ad-line-chart">
                            <svg viewBox="0 0 1000 260" preserveAspectRatio="none" role="img" aria-label="Monthly sales line chart">
                                <polyline points="0,198 120,180 240,154 360,166 480,132 600,118 720,96 840,76 960,58"
                                    fill="none" stroke="#ff6600" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" />
                                <polygon points="0,198 120,180 240,154 360,166 480,132 600,118 720,96 840,76 960,58 960,260 0,260"
                                    fill="rgba(255,102,0,0.15)" />
                            </svg>
                        </div>
                    </article>

                    <article class="ad-card">
                        <h3 class="ad-panel-title">Revenue by Channel (Bar Chart)</h3>
                        <div class="ad-bar-wrap">
                            <div class="ad-bar-item">
                                <span>Online</span>
                                <span class="ad-bar-track"><span class="ad-bar-fill" style="width: 92%;"></span></span>
                                <strong>$320k</strong>
                            </div>
                            <div class="ad-bar-item">
                                <span>Mobile App</span>
                                <span class="ad-bar-track"><span class="ad-bar-fill" style="width: 78%;"></span></span>
                                <strong>$270k</strong>
                            </div>
                            <div class="ad-bar-item">
                                <span>Walk-in</span>
                                <span class="ad-bar-track"><span class="ad-bar-fill" style="width: 47%;"></span></span>
                                <strong>$152k</strong>
                            </div>
                            <div class="ad-bar-item">
                                <span>Partners</span>
                                <span class="ad-bar-track"><span class="ad-bar-fill" style="width: 33%;"></span></span>
                                <strong>$98k</strong>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="ad-card" style="margin-top: 1rem;">
                    <h3 class="ad-panel-title">Recent Activity and Transactions</h3>
                    <div class="ad-table-wrap">
                        <table class="ad-table">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Activity</th>
                                    <th>Reference</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $booking)
                                    <tr>
                                        <td>{{ $booking->id }}</td>
                                        <td>{{ optional($booking->concert)->title ?? 'Deleted Concert' }} - {{ optional($booking->user)->name ?? 'Deleted User' }}</td>
                                        <td>#BOOK-{{ str_pad((string)$booking->id, 6, '0', STR_PAD_LEFT) }}</td>
                                        <td>₱{{ number_format((float)$booking->total_price, 2) }}</td>
                                        <td>
                                            <span class="ad-status {{ $booking->status === 'confirmed' ? 'success' : ($booking->status === 'pending' ? 'pending' : 'info') }}">
                                                {{ ucfirst($booking->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">No bookings found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

            </main>
        </div>
    </section>

    @include('admin.partials.theme-script')
</x-app-layout>
