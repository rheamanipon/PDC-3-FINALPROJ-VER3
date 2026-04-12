<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold mb-4">{{ $venue->name }} Details</h2>
            <p><strong>Location:</strong> {{ $venue->location }}</p>
            <p><strong>Capacity:</strong> {{ $venue->capacity }}</p>
        </div>
    </div>
</x-app-layout>