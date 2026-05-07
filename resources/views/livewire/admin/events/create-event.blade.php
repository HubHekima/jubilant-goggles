{{-- resources/views/livewire/create-event.blade.php --}}
<div class="max-w-3xl mx-auto space-y-8">
    <flux:heading size="xl">Create New Event</flux:heading>

    <div class="grid grid-cols-1 gap-6">
        {{-- Banner Upload with Preview --}}
        <div class="space-y-2">
            <flux:label>Event Banner</flux:label>
            @if ($banner)
                <img src="{{ $banner->temporaryUrl() }}" class="h-40 w-full object-cover rounded-lg mb-2">
            @endif
            <flux:input type="file" wire:model="banner" />
        </div>

        <flux:input wire:model="name" label="Event Name" />
        <flux:input wire:model="venue" label="Venue Location" />
        <flux:input type="datetime-local" wire:model="starts_at" label="Date & Time" />
    </div>

    {{-- Dynamic Ticket Pricing Section --}}
    <div class="bg-gray-50 p-6 rounded-xl space-y-4">
        <flux:heading size="lg">Ticket Types & Pricing</flux:heading>
        
        @foreach($ticketTypes as $index => $type)
            <div class="flex gap-4 items-end border-b pb-4">
                <flux:input wire:model="ticketTypes.{{$index}}.name" label="Type (e.g. VIP)" />
                <flux:input type="number" wire:model="ticketTypes.{{$index}}.price" label="Price (KES)" />
                <flux:input type="number" wire:model="ticketTypes.{{$index}}.capacity" label="Qty" />
            </div>
        @endforeach

        <flux:button wire:click="addTicketType" variant="subtle" size="sm" icon="plus">
            Add Another Tier
        </flux:button>
    </div>

    <flux:button wire:click="save" variant="primary" class="w-full">Create Event & Start Selling</flux:button>
</div>

