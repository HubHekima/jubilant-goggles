<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\PurchaseTicket;
use App\Livewire\Admin\Events\CreateEvent;
use App\Livewire\TicketScanner;


Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});
// admin ~ email: admin@bandname.com
// admin ~ password: password

Route::get('/event/{event:slug}', PurchaseTicket::class);
//access created event to test purchase ticket prompt generation : https://jubilant-goggles.main-je398z.laravel.cloud/event/conference
//daraja prompt == sandox

// routes/web.php


Route::middleware(['auth', 'role:super-admin'])->prefix('admin')->group(function () {
    Route::get('/events/create', CreateEvent::class)->name('admin.events.create');
});

Route::get('/scanner', TicketScanner::class)->name('scanner');

require __DIR__.'/settings.php';
