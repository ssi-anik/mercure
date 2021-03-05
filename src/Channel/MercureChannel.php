<?php

namespace Anik\Mercure\Channel;

use Anik\Mercure\Adapter\Mercure;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;

class MercureChannel
{
    private $mercure;

    public function __construct(Mercure $mercure)
    {
        $this->mercure = $mercure;
    }

    public function send($notifiable, Notification $notification)
    {
        $channels = Arr::wrap($notification->broadcastOn());
        $payload = $this->getData($notifiable, $notification);
        if (method_exists($notification, 'broadcastType')) {
            $event = $notification->broadcastType();
        } elseif (method_exists($notification, 'broadcastAs')) {
            $event = $notification->broadcastAs();
        } else {
            $event = get_class($notification);
        }

        $this->mercure->publish($channels, $event, $payload);
    }

    /**
     * @param mixed $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     *
     * @return array
     */
    protected function getData($notifiable, Notification $notification): array
    {
        if (method_exists($notification, 'toMercure')) {
            return (array)$notification->toMercure($notifiable);
        }

        if (method_exists($notification, 'toArray')) {
            return (array)$notification->toArray($notifiable);
        }

        return [];
    }
}
