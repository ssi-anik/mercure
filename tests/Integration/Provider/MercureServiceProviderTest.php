<?php

namespace Anik\Mercure\Tests\Integration\Provider;

use Anik\Mercure\Channel\MercureChannel;
use Anik\Mercure\Tests\TestCase;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\RoutesNotifications;
use InvalidArgumentException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\Mercure\Publisher;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;

class MercureServiceProviderTest extends TestCase
{
    public function testServiceProviderBindsComponents()
    {
        $this->assertInstanceOf(PublisherInterface::class, app('mercure'));
        $this->assertInstanceOf(PublisherInterface::class, app(PublisherInterface::class));
        $this->assertInstanceOf(PublisherInterface::class, app(Publisher::class));
    }

    public function testUsesDefaultConnectionWhenCreatingInstance()
    {
        $url = 'http://mercure.test/.well-known/mercure';
        $this->updateConfiguration('default', 'new-connection');
        $this->updateConfiguration('connections.new-connection', [
            'url' => $url,
            'jwt' => static::JWT,
        ]);
        $this->mockHttpClientInConnection(function ($_, $hubUrl) use ($url) {
            $this->assertSame($hubUrl, $url);
        }, '', 'new-connection');
        app(PublisherInterface::class)(new Update(['t']));
    }

    public function testDoesNotRegisterNotificationDriverByDefault()
    {
        $this->expectException(InvalidArgumentException::class);
        app(ChannelManager::class)->driver('mercure');
    }

    /**
     * @define-env enableNotificationDriver
     */
    public function testRegistersNotificationDriverThroughEnvironment()
    {
        $this->assertInstanceOf(MercureChannel::class, app(ChannelManager::class)->driver('mercure'));
    }

    /**
     * @define-env registerMercureConfiguration
     */
    public function testDoesNotRegisterBroadcastingDriverByDefault()
    {
        $this->expectException(InvalidArgumentException::class);
        app(BroadcastManager::class)->driver('mercure');
    }

    /**
     * @define-env registerMercureConfiguration
     * @define-env enableBroadcastingDriver
     */
    public function testRegistersBroadcastingDriverThroughEnvironment()
    {
        $this->assertInstanceOf(Broadcaster::class, app(BroadcastManager::class)->driver('mercure'));
    }

    public function testBoundMercureChannelShouldReturnItself()
    {
        $channel = app(MercureChannel::class);
        $this->assertInstanceOf(MercureChannel::class, $channel);
    }

    /**
     * @define-env registerMercureConfiguration
     * @define-env enableBroadcastingDriver
     */
    public function testBroadcastEventThroughMercure()
    {
        /**
         * as the http client is not provided, will create the default client thus it'll throw exception
         */
        $this->expectException(TransportException::class);
        app(BroadcastManager::class)->event(new class implements ShouldBroadcastNow {
            public function broadcastOn(): array
            {
                return [new PrivateChannel('private-1')];
            }
        });
    }

    /**
     * @define-env enableNotificationDriver
     */
    public function testNotificationThroughMercureDriver()
    {
        /**
         * as the http client is not provided, will create the default client thus it'll throw exception
         */
        $this->expectException(TransportException::class);

        (new class extends Model {
            use RoutesNotifications;
        })->notify(new class extends Notification {
            public function broadcastOn(): array
            {
                return [new PrivateChannel('private-2')];
            }

            public function via($notifiable): array
            {
                return ['mercure'];
            }
        });
    }

    /**
     * @define-env enableNotificationDriver
     */
    public function testNotificationThroughMercureChannelInstance()
    {
        /**
         * as the http client is not provided, will create the default client thus it'll throw exception
         */
        $this->expectException(TransportException::class);

        (new class extends Model {
            use RoutesNotifications;
        })->notify(new class extends Notification {
            public function broadcastOn(): array
            {
                return [new PrivateChannel('private-2')];
            }

            public function via($notifiable): array
            {
                return [MercureChannel::class];
            }
        });
    }
}
