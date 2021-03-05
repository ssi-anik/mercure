<?php

declare(strict_types=1);

namespace Anik\Mercure\Provider;

use Anik\Mercure\Adapter\Mercure;
use Anik\Mercure\Broadcaster\MercureBroadcaster;
use Anik\Mercure\Channel\MercureChannel;
use Anik\Mercure\Factory\Publisher as PublisherFactory;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\Mercure\Publisher;
use Symfony\Component\Mercure\PublisherInterface;

class MercureServiceProvider extends ServiceProvider
{
    public const CONFIG_PATH = __DIR__ . '/../config/mercure.php';

    public function boot()
    {
        if ($this->app->runningInConsole() && !Str::contains($this->app->version(), 'Lumen')) {
            $this->publishes([
                self::CONFIG_PATH => config_path('mercure.php'),
            ]);
        }

        if ($this->getConfig('enable_broadcasting')) {
            $this->extendBroadcasting();
        }

        if ($this->getConfig('enable_notification')) {
            $this->extendNotification();
        }
    }

    public function register()
    {
        $this->mergeConfiguration();
        $this->registerPublisher();
    }

    protected function extendBroadcasting()
    {
        $this->app->make(BroadcastManager::class)->extend('mercure', function ($app, array $config) {
            $connection = $config['connection'] ?? $this->getConfig('default');
            $hubConfig = $this->getConfig('connections.' . $connection);

            return new MercureBroadcaster(new Mercure(PublisherFactory::instance($hubConfig)));
        });
    }

    protected function extendNotification()
    {
        $this->app->make(ChannelManager::class)->extend('mercure', function ($app) {
            $config = $this->getConfig('connections.' . $this->getConfig('default'));

            return new MercureChannel(new Mercure(PublisherFactory::instance($config)));
        });

        $this->app->bind(MercureChannel::class, function ($app) {
            return $app->make(ChannelManager::class)->channel('mercure');
        });
    }

    protected function mergeConfiguration()
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'mercure');
    }

    protected function registerPublisher()
    {
        $this->app->bind(Publisher::class, function (): PublisherInterface {
            $config = $this->getConfig(sprintf('connections.%s', $this->getConfig('default')));

            return PublisherFactory::instance($config);
        });

        $this->app->alias(Publisher::class, PublisherInterface::class);
        $this->app->alias(Publisher::class, 'mercure');
    }

    protected function getConfig(string $key, $default = null)
    {
        return config('mercure.' . $key, $default);
    }

    public function provides(): array
    {
        return ['mercure', PublisherInterface::class, Publisher::class, MercureChannel::class];
    }
}
