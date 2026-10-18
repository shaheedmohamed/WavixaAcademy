<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public Event $event) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'event_reminder',
            'event_id' => $this->event->id,
            'title' => 'تذكير بفعالية',
            'body' => 'فعالية "'.$this->event->title.'" تبدأ '.$this->event->start_at->diffForHumans().'.',
            'url' => '/events/'.$this->event->slug,
        ];
    }
}
