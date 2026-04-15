<x-app-layout>
    <div class="admin-container">
        <!-- PAGE HEADER -->
        <div style="margin-bottom: 3rem;">
            <h1 style="font-size: 3.5rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.5rem;">EDIT CONCERT</h1>
            <p style="font-size: 1.1rem; color: var(--accent-primary); font-weight: 600;">Update concert details and upload a new poster image</p>
        </div>

        <!-- FORM CARD -->
        <div class="admin-form">
            <form action="{{ route('admin.concerts.update', $concert) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- BASIC INFO SECTION -->
                <h3 style="font-size: 1.5rem; font-weight: 700; text-transform: uppercase; margin-top: 0; margin-bottom: 1.5rem; color: var(--accent-primary);">Basic Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="title" class="form-label">Concert Title</label>
                        <input type="text" name="title" id="title" value="{{ old('title', $concert->title) }}" placeholder="e.g., Summer Music Festival" required>
                        @error('title') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="artist" class="form-label">Artist / Performer</label>
                        <input type="text" name="artist" id="artist" value="{{ old('artist', $concert->artist) }}" placeholder="e.g., The Weeknd" required>
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
                                <option value="{{ $venue->id }}" {{ old('venue_id', $concert->venue_id) == $venue->id ? 'selected' : '' }}>{{ $venue->name }} (Cap. {{ $venue->capacity }})</option>
                            @endforeach
                        </select>
                        @error('venue_id') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="date" class="form-label">Event Date</label>
                        <input type="date" name="date" id="date" value="{{ old('date', $concert->date?->format('Y-m-d')) }}" required>
                        @error('date') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="time" class="form-label">Event Time</label>
                        <input type="time" name="time" id="time" value="{{ old('time', $concert->time?->format('H:i')) }}" required>
                        @error('time') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- DESCRIPTION SECTION -->
                <h3 style="font-size: 1.5rem; font-weight: 700; text-transform: uppercase; margin-top: 2rem; margin-bottom: 1.5rem; color: var(--accent-primary);">Description & Media</h3>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label for="description" class="form-label">Event Description</label>
                    <textarea name="description" id="description" rows="5" placeholder="Describe the concert, atmosphere, special guests, etc." style="resize: vertical;">{{ old('description', $concert->description) }}</textarea>
                    @error('description') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                @if($concert->poster_url)
                    <div class="form-group" style="margin-bottom: 2rem;">
                        <label class="form-label">Current Poster</label>
                        <div style="max-width: 320px; border: 1px solid var(--border-color); padding: 0.5rem; background: #fff;">
                            <img src="{{ asset('storage/' . $concert->poster_url) }}" alt="{{ $concert->title }} poster" style="width: 100%; height: auto; display: block;" />
                        </div>
                    </div>
                @endif

                <div class="form-group">
                    <label for="poster" class="form-label">Upload New Poster Image</label>
                    <input type="file" name="poster" id="poster" accept="image/*" style="padding: 1rem; border: 2px dashed var(--border-color);">
                    <p class="form-help">Choose a new poster image to replace the current one, if needed.</p>
                    @error('poster') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <!-- ACTIONS -->
                <div class="form-actions">
                    <a href="{{ route('admin.concerts.index') }}" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Concert</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
