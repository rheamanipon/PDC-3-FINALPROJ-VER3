<x-app-layout>
    <div class="admin-container">
        <!-- PAGE HEADER -->
        <div class="admin-header">
            <div>
                <h1>MANAGE CONCERTS</h1>
                <p>Create, edit, and delete concert events</p>
            </div>
            <a href="{{ route('admin.concerts.create') }}" class="btn btn-primary">+ Add New Concert</a>
        </div>

        <!-- CONCERTS TABLE -->
        <div class="table-wrapper">
            @if($concerts->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Concert</th>
                            <th>Artist</th>
                            <th>Venue</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($concerts as $concert)
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-primary);">{{ $concert->title }}</div>
                                </td>
                                <td>{{ $concert->artist }}</td>
                                <td>{{ $concert->venue->name }}</td>
                                <td>{{ $concert->date->format('M d, Y') }}</td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('admin.concerts.show', $concert) }}" class="action-btn action-view">View</a>
                                        <a href="{{ route('admin.concerts.edit', $concert) }}" class="action-btn action-edit">Edit</a>
                                        <form action="{{ route('admin.concerts.destroy', $concert) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn action-delete" onclick="return confirm('Delete this concert? This action cannot be undone.')">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">🎤</div>
                                        <h3>No Concerts Yet</h3>
                                        <p>Start by creating your first concert event.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>

        <!-- PAGINATION -->
        @if($concerts->hasPages())
            <div style="display: flex; justify-content: center; margin-top: 2rem;">
                {{ $concerts->links() }}
            </div>
        @endif
    </div>
</x-app-layout>