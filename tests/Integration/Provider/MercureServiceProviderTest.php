<?php

namespace Anik\Mercure\Tests\Integration\Provider;

use Anik\Mercure\Channel\MercureChannel;
use Anik\Mercure\Tests\TestCase;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Notifications\ChannelManager;
use InvalidArgumentException;
use Symfony\Component\Mercure\Publisher;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;

class MercureServiceProviderTest extends TestCase
{
    public function testServiceProviderBindsComponents()
    {
        $this->assertTrue(app('mercure') instanceof PublisherInterface);
        $this->assertTrue(app(PublisherInterface::class) instanceof PublisherInterface);
        $this->assertTrue(app(Publisher::class) instanceof PublisherInterface);
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
     * @define-env registerNotificationDriver
     */
    public function testRegistersNotificationDriverThroughEnvironment()
    {
        $this->assertTrue(app(ChannelManager::class)->driver('mercure') instanceof MercureChannel);
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
     * @define-env registerBroadcastingDriver
     */
    public function testRegistersBroadcastingDriverThroughEnvironment()
    {
        $this->assertTrue(app(BroadcastManager::class)->driver('mercure') instanceof Broadcaster);
    }
}
