<div>
@if($isPaid)
    <div class="mt-6 p-6 bg-green-50 border border-green-200 rounded-xl text-center">
        <flux:heading size="lg" class="text-green-800">Success!</flux:heading>
        <flux:text class="mb-4">Your payment was verified. Download your ticket below.</flux:text>
        
        <flux:button 
            wire:click="downloadTicket" 
            variant="primary" 
            icon="document-arrow-down"
        >
            Download PDF Ticket
        </flux:button>
    </div>
@else
<div  @if($isProcessing) wire:poll.3s="checkPaymentStatus" @endif class="max-w-md mx-auto p-6 bg-white shadow rounded-lg">
    {{-- Display the Event Banner --}}
    @if($event->banner_path)
        <img src="{{ asset('storage/' . $event->banner_path) }}" class="rounded-lg mb-4">
    @endif

    <flux:heading size="xl">{{ $event->name }}</flux:heading>
    <flux:text variant="subtle">{{ $event->venue }}</flux:text>

    <div class="mt-6 space-y-4">
        {{-- TICKET SELECTION --}}
        <flux:radio.group wire:model="selectedTicketType" label="Choose your ticket">
            @foreach($event->ticketTypes as $type)
                <flux:radio value="{{ $type->id }}" label="{{ $type->name }} (KES {{ $type->price }})" />
            @endforeach
        </flux:radio.group>

        {{-- USER DETAILS --}}
        <flux:input wire:model="name" label="Your Name" placeholder="Full Name" />
        <flux:input wire:model="email" label="Email Address" />
        <flux:input wire:model="phone" label="M-Pesa Number" placeholder="254712345678" />

        {{-- THE BUTTON --}}
        <flux:button 
        wire:click="buy" 
        variant="primary" 
        class="w-full" 
        wire:loading.attr="disabled"
    >
        <flux:icon.credit-card wire:loading.remove class="mr-2" />
        <span wire:loading.remove>Pay with M-Pesa</span>
        
        <flux:spacer wire:loading />
        <span wire:loading>Awaiting M-Pesa...</span>
    </flux:button>
    </div>

    @if (session()->has('message'))
        <div class="mt-4 text-green-600 font-bold">
            {{ session('message') }}
        </div>
    @endif
</div>
@endif
</div>