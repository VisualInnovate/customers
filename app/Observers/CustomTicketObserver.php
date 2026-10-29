<?php

namespace App\Observers;

use App\Mail\CustomTicketCreatedMail;
use App\Models\CustomTicket;
use Illuminate\Support\Facades\Mail;

class CustomTicketObserver
{
    /**
     * Handle the CustomTicket "created" event.
     */
    public function created(CustomTicket $customTicket): void
    {
        Mail::to($customTicket->contact)->send(new CustomTicketCreatedMail($customTicket));
    }

    /**
     * Handle the CustomTicket "updated" event.
     */
    public function updated(CustomTicket $customTicket): void
    {
        //
    }

    /**
     * Handle the CustomTicket "deleted" event.
     */
    public function deleted(CustomTicket $customTicket): void
    {
        //
    }

    /**
     * Handle the CustomTicket "restored" event.
     */
    public function restored(CustomTicket $customTicket): void
    {
        //
    }

    /**
     * Handle the CustomTicket "force deleted" event.
     */
    public function forceDeleted(CustomTicket $customTicket): void
    {
        //
    }
}
