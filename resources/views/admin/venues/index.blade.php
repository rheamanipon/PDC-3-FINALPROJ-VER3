<x-app-layout>
    <div class="admin-container">
        <!-- PAGE HEADER -->
        <div class="admin-header">
            <div>
                <h1>MANAGE VENUES</h1>
                <p>Configure venues and their capacities</p>
            </div>
            <a href="{{ route('admin.venues.create') }}" class="btn btn-primary">+ Add New Venue</a>
        </div>

        <!-- VENUES TABLE -->
        <div class="table-wrapper">
            @if($venues->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Venue Name</th>
                            <th>Location</th>
                            <th>Capacity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($venues as $venue)
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-primary);">{{ $venue->name }}</div>
                                </td>
                                <td>{{ $venue->location }}</td>
                                <td>
                                    <div style="font-weight: 600; font-size: 1.1rem; color: var(--accent-primary);">{{ number_format($venue->capacity) }}</div>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('admin.venues.show', $venue) }}" class="action-btn action-view">View</a>
                                        <a href="{{ route('admin.venues.edit', $venue) }}" class="action-btn action-edit">Edit</a>
                                        <form action="{{ route('admin.venues.destroy', $venue) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn action-delete" onclick="return confirm('Delete this venue? This action cannot be undone.')">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">🏛️</div>
                                        <h3>No Venues Yet</h3>
                                        <p>Start by creating your first venue.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>

        <!-- PAGINATION -->
        @if($venues->hasPages())
            <div style="display: flex; justify-content: center; margin-top: 2rem;">
                {{ $venues->links() }}
            </div>
        @endif
    </div>
</x-app-layout>