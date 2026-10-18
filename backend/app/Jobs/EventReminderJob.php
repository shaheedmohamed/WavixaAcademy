<?php

namespace App\Jobs;

use App\Models\Event;
use App\Notifications\EventReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sends an in-app reminder to registrants ~24h before an event starts.
 */
class EventReminderJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $events = Event::where('status', 'published')
            ->whereBetween('start_at', [now()->addHours(23), now()->addHours(25)])
            ->with('registrations.user')
            ->get();

        foreach ($events as $event) {
            foreach ($event->registrations->where('status', 'registered') as $registration) {
                $alreadySent = $registration->user->notifications()
                    ->where('type', EventReminderNotification::class)
                    ->where('data->event_id', $event->id)
                    ->exists();

                if (! $alreadySent) {
                    $registration->user->notify(new EventReminderNotification($event));
                }
            }
        }
    }
}
