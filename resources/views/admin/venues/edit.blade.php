<x-app-layout>
    <section class="admin-dashboard" id="adminDashboard">
        <div class="admin-shell">
            @include('admin.partials.sidebar')
            <main class="admin-main">
                @include('admin.partials.flash')
                <header class="admin-header">
                    <div><h2>Edit Venue</h2><p>Revise venue specifications and operational details with full traceability.</p></div>
                    <div class="admin-header-actions">
                        <button type="button" class="ad-btn ad-icon-btn" id="themeToggleBtn"><span id="themeToggleIcon">◐</span></button>
                        <a href="{{ route('admin.venues.index') }}" class="ad-btn">Back</a>
                    </div>
                </header>
                <section class="ad-card">
                    <h3 class="ad-panel-title">Edit Venue Information</h3>
                    @if(!empty($isUsedByConcerts) && $isUsedByConcerts)
                        <div style="margin-bottom: 1rem; padding: 0.7rem 0.9rem; border: 1px solid rgba(251,191,36,0.45); border-radius: 0.5rem; background: rgba(251,191,36,0.08); color: #fde68a;">
                            This venue is already used by concerts. Only capacity increase is allowed.
                        </div>
                    @endif
                    <form method="POST" action="{{ route('admin.venues.update', $venue) }}" enctype="multipart/form-data" class="ad-form-grid-3">
                        @csrf @method('PUT')
                        <div class="ad-field">
                            <label class="ad-label" for="name">Venue Name</label>
                            <input class="ad-input" id="name" type="text" name="name" value="{{ old('name', $venue->name) }}" required {{ !empty($isUsedByConcerts) && $isUsedByConcerts ? 'readonly' : '' }}>
                        </div>
                        <div class="ad-field">
                            <label class="ad-label" for="location">Location</label>
                            <input class="ad-input" id="location" type="text" name="location" value="{{ old('location', $venue->location) }}" required {{ !empty($isUsedByConcerts) && $isUsedByConcerts ? 'readonly' : '' }}>
                        </div>
                        <div class="ad-field">
                            <label class="ad-label" for="capacity">Capacity</label>
                            <input class="ad-input" id="capacity" type="number" name="capacity" value="{{ old('capacity', $venue->capacity) }}" min="{{ !empty($isUsedByConcerts) && $isUsedByConcerts ? $venue->capacity : 1 }}" required>
                        </div>

                        <div class="ad-field ad-field-full">
                            <div class="ad-actions-row">
                                <button class="ad-btn ad-btn-primary" type="submit">Update Venue</button>
                            </div>
                        </div>

                    </form>
                </section>
            </main>
        </div>
    </section>
    @include('admin.partials.theme-script')
</x-app-layout>
