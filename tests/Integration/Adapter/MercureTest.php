<?php

namespace Anik\Mercure\Tests\Integration\Adapter;

use Anik\Mercure\Tests\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;

class MercureTest extends TestCase
{
    public function testShouldThrowExceptionForUrl()
    {
        $this->expectException(TransportException::class);
        $this->getMercureAdapter()->publish(['channel-1'], 'event', []);
    }

    public function testGracefulPublishReturnsBooleanOnError()
    {
        $response = $this->getMercureAdapter()->publishGracefully(['channel-1'], 'event', []);
        $this->assertFalse($response);
    }

    public function testAdapterCanExtractInfoFromPayload()
    {
        $payload = [
            'name' => 'anik/mercure',
            'role' => 'developer',
            '__msg_private' => true,
            '__msg_retry' => 5,
            '__msg_type' => 'custom-type',
            '__msg_id' => 'custom-id',
        ];

        $this->mockHttpClientInConnection(function ($method, $url, $options) use ($payload) {
            parse_str($options['body'], $parts);
            $this->assertSame($parts['private'], 'on');
            $this->assertSame((int)$parts['retry'], $payload['__msg_retry']);
            $this->assertSame($parts['id'], $payload['__msg_id']);
            $this->assertSame($parts['type'], $payload['__msg_type']);
        });
        $this->getMercureAdapter()->publish(['c1'], '', $payload);
    }

    public function testAdapterRemovesInformationKeysFromPayload()
    {
        $payload = [
            'name' => 'anik/mercure',
            'role' => 'developer',
            '__msg_private' => true,
            '__msg_id' => 'custom-id',
            '_it_will_be_there' => 'yes',
            '__also_it_will_be_there' => 'oh-yes',
        ];

        $this->mockHttpClientInConnection(function ($method, $url, $options) {
            parse_str($options['body'], $parts);
            $this->assertStringContainsString('_it_will_be', $parts['data']);
            $this->assertStringContainsString('__also_it_will_be', $parts['data']);
            $this->assertStringNotContainsString('__msg_id', $parts['data']);
            $this->assertSame($parts['id'], 'custom-id');
        });
        $this->getMercureAdapter()->publish(['c1'], '', $payload);
    }

    public function testAdapterReturnsStringOnSuccess()
    {
        $payload = [
            'name' => 'anik/mercure',
            'role' => 'developer',
        ];

        $this->mockHttpClientInConnection();
        $this->assertTrue(is_string($this->getMercureAdapter()->publish(['c1'], '', $payload)));
    }

    public function testAdapterSendsMessageInEventAndDataFormat()
    {
        $payload = [
            'name' => 'anik/mercure',
            'role' => 'developer',
        ];

        $this->mockHttpClientInConnection(function ($method, $url, $options) {
            parse_str($options['body'], $parts);
            $msg = json_decode($parts['data'], true);
            $this->assertArrayHasKey('data', $msg);
            $this->assertArrayHasKey('event', $msg);
        });
        $this->getMercureAdapter()->publish(['c1'], '', $payload);
    }

    public function testAdapterCanSendsMessageToMultipleTopics()
    {
        $this->mockHttpClientInConnection(function ($method, $url, $options) {
            $this->assertStringContainsString('topic=channel1', $options['body']);
            $this->assertStringContainsString('topic=channel2', $options['body']);
        });
        $this->getMercureAdapter()->publish(['channel1', 'channel2'], 'event', []);
    }

    public function testAdapterMessageIsPublishedAsPrivate()
    {
        $this->mockHttpClientInConnection(function ($method, $url, $options) {
            $this->assertStringContainsString('private=on', $options['body']);
        });
        $this->getMercureAdapter()->publish(['channel'], 'event', []);
    }

    public function testAdapterCanSendMessageAsPublicIfSpecifiedInPayload()
    {
        $this->mockHttpClientInConnection(function ($method, $url, $options) {
            $this->assertStringNotContainsString('private', $options['body']);
        });
        $this->getMercureAdapter()->publish(['channel'], 'event', ['__msg_private' => false]);
    }

    public function testAdapterRejectsEmptyChannelsUpdate()
    {
        $this->mockHttpClientInConnection();
        $this->assertNull($this->getMercureAdapter()->publish([], 'event'));
    }
}
