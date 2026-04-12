<x-app-layout>
    <div class="admin-container">
        <!-- PAGE HEADER -->
        <div style="margin-bottom: 3rem;">
            <h1 style="font-size: 3.5rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.5rem;">CREATE CONCERT</h1>
            <p style="font-size: 1.1rem; color: var(--accent-primary); font-weight: 600;">Add a new concert event to your platform</p>
        </div>

        <!-- FORM CARD -->
        <div class="admin-form">
            <form action="{{ route('admin.concerts.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- BASIC INFO SECTION -->
                <h3 style="font-size: 1.5rem; font-weight: 700; text-transform: uppercase; margin-top: 0; margin-bottom: 1.5rem; color: var(--accent-primary);">Basic Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="title" class="form-label">Concert Title</label>
                        <input type="text" name="title" id="title" value="{{ old('title') }}" placeholder="e.g., Summer Music Festival" required>
                        @error('title') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="artist" class="form-label">Artist / Performer</label>
                        <input type="text" name="artist" id="artist" value="{{ old('artist') }}" placeholder="e.g., The Weeknd" required>
                        @error('artist') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- EVENT DETAILS SECTION -->
                <h3 style="font-size: 1.5rem; font-weight: 700; text-transform: uppercase; margin-top: 2rem; margin-bottom: 1.5rem; color: var(--accent-primary);">Event Details</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="venue_id" class="form-label">Venue</label>
                        <select name="venue_id" id="venue_id" required>
                            <option value="">-- Select a Venue --</option>
                            @foreach($venues as $venue)
                                <option value="{{ $venue->id }}" {{ old('venue_id') == $venue->id ? 'selected' : '' }}>{{ $venue->name }} (Cap. {{ $venue->capacity }})</option>
                            @endforeach
                        </select>
                        @error('venue_id') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="date" class="form-label">Event Date</label>
                        <input type="date" name="date" id="date" value="{{ old('date') }}" required>
                        @error('date') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="time" class="form-label">Event Time</label>
                        <input type="time" name="time" id="time" value="{{ old('time') }}" required>
                        @error('time') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- DESCRIPTION SECTION -->
                <h3 style="font-size: 1.5rem; font-weight: 700; text-transform: uppercase; margin-top: 2rem; margin-bottom: 1.5rem; color: var(--accent-primary);">Description & Media</h3>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label for="description" class="form-label">Event Description</label>
                    <textarea name="description" id="description" rows="5" placeholder="Describe the concert, atmosphere, special guests, etc." style="resize: vertical;">{{ old('description') }}</textarea>
                    @error('description') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="poster" class="form-label">Poster Image (Optional)</label>
                    <input type="file" name="poster" id="poster" accept="image/*" style="padding: 1rem; border: 2px dashed var(--border-color);">
                    <p class="form-help">Upload a high-quality poster image (JPG, PNG, WebP)</p>
                    @error('poster') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <!-- ACTIONS -->
                <div class="form-actions">
                    <a href="{{ route('admin.concerts.index') }}" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Concert</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>