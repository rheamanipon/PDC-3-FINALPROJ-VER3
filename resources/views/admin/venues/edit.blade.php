<x-app-layout>
    <div class="admin-container">
        <div style="margin-bottom: 3rem;" class="fade-in">
            <h1 style="font-size: 3.5rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.5rem;">EDIT VENUE</h1>
            <p style="font-size: 1.1rem; color: var(--accent-primary); font-weight: 600;">Update details for {{ $venue->name }}</p>
        </div>

        <div class="admin-form fade-in">
            <form action="{{ route('admin.venues.update', $venue) }}" method="POST">
                @csrf
                @method('PUT')

                <h3 style="font-size: 1.5rem; font-weight: 700; text-transform: uppercase; margin-top: 0; margin-bottom: 1.5rem; color: var(--accent-primary);">Modify Identity</h3>
                
                <div class="form-group">
                    <label for="name" class="form-label">Venue Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $venue->name) }}" required>
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="location" class="form-label">Location / City</label>
                        <input type="text" name="location" id="location" value="{{ old('location', $venue->location) }}" required>
                        @error('location') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="capacity" class="form-label">Max Capacity</label>
                        <input type="number" name="capacity" id="capacity" value="{{ old('capacity', $venue->capacity) }}" min="1" required>
                        @error('capacity') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('admin.venues.index') }}" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Venue</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>