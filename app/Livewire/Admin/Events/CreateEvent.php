<?php

namespace App\Livewire\Admin\Events;


use App\Models\Event;
use Livewire\Component;
use Livewire\WithFileUploads; 

class CreateEvent extends Component
{
    use WithFileUploads;

    // Event Details
    public $name, $venue, $starts_at, $description, $banner;

    // Ticket Types (starts with one empty row)
    public $ticketTypes = [['name' => '', 'price' => '', 'capacity' => '']];

    public function addTicketType()
    {
        $this->ticketTypes[] = ['name' => '', 'price' => '', 'capacity' => ''];
    }

    public function save()
    {
        $this->validate([
            'name' => 'required',
            'banner' => 'image|max:2048', // Max 2MB
            'ticketTypes.*.name' => 'required',
        ]);

        // 1. Upload Banner
        $path = $this->banner->store('banners', 'public');

        // 2. Create Event
        $event = Event::create([
            'name' => $this->name,
            'slug' => \Str::slug($this->name),
            'venue' => $this->venue,
            'starts_at' => $this->starts_at,
            'banner_path' => $path,
        ]);

        // 3. Create Ticket Types
        foreach ($this->ticketTypes as $type) {
            $event->ticketTypes()->create($type);
        }

        return redirect()->to('/dashboard');
    }
    public function render()
    {
        return view('livewire.admin.events.create-event');
    }
}
