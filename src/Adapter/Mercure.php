<?php

namespace Anik\Mercure\Adapter;

use Illuminate\Support\Arr;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;
use Throwable;

class Mercure
{
    private $publisher;

    public function __construct(PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * @param array $channels
     * @param string $event
     * @param array $payload
     *
     * @return string|null
     * @throws Throwable
     */
    public function publish(array $channels, string $event, array $payload = []): ?string
    {
        if (empty($channels)) {
            return;
        }

        $topics = array_map(function ($channel) {
            return (string)$channel;
        }, $channels);

        $private = (bool)Arr::pull($payload, '__msg_private', true);
        $id = Arr::pull($payload, '__msg_id');
        $retry = Arr::pull($payload, '__msg_retry');
        $type = Arr::pull($payload, '__msg_type', $event);
        $data = json_encode([
            'event' => $event,
            'data' => $payload,
        ]);

        ($this->publisher)(new Update($topics, $data, $private, $id, $type, $retry));
    }

    /**
     * @param array $channels
     * @param $event
     * @param array $payload
     *
     * @return string|bool
     */
    public function publishGracefully(array $channels, $event, array $payload = [])
    {
        try {
            return $this->publish($channels, $event, $payload);
        } catch (Throwable $t) {
            return false;
        }
    }
}
