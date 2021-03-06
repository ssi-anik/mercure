<?php

namespace Anik\Mercure\Tests;

use Anik\Mercure\Adapter\Mercure;
use Anik\Mercure\Provider\MercureServiceProvider;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public const URL = 'http://127.0.0.1:3000/.well-known/mercure';
    public const JWT = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdfX0.OwYVEF9qsVOpHeCx-iBV5jMVl0BVGivm0v8fsJTW5rw';
    public const ANOTHER_JWT = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdfSwiZXh0cmFfZGF0YSI6ImhlcmUifQ.bcEXNZ5sfW5WXWs7ekkMFI540X5UELrOi9tgav3eE3Q';
    public const SECRET = 'secret';

    protected function getPackageProviders($app): array
    {
        return [
            MercureServiceProvider::class,
        ];
    }

    protected function config(array $options = []): array
    {
        $config = [
            'url' => static::URL,
            'jwt' => static::JWT,
            'provider' => null,
            'secret' => static::SECRET,
            'payload' => [
                'mercure' => [
                    'publish' => [
                        '*',
                    ],
                ],
            ],
            'algo' => 'HS256',
            'http_client' => null,
        ];

        foreach ($options['unset'] ?? [] as $key) {
            unset($config[$key]);
        }

        return array_merge($config, $options['set'] ?? []);
    }

    protected function getMockedHttpClient(callable $callback = null, string $response = ''): MockHttpClient
    {
        return new MockHttpClient(function (
            string $method,
            string $url,
            array $options = []
        ) use (
            $callback,
            $response
        ): ResponseInterface {
            $callback ? call_user_func_array($callback, [$method, $url, $options]) : null;

            return new MockResponse($response);
        });
    }

    protected function configWithHttpClient(
        callable $callback = null,
        string $response = '',
        array $options = []
    ): array {
        $httpClient = $this->getMockedHttpClient($callback, $response);

        return $this->config(array_merge_recursive([
            'set' => [
                'http_client' => $httpClient,
            ],
        ], $options));
    }

    protected function mockHttpClientInConnection(
        callable $callback = null,
        string $response = '',
        string $connection = 'hub'
    ) {
        $key = "connections.{$connection}.http_client";
        $this->updateConfiguration($key, $this->getMockedHttpClient($callback, $response));
    }

    protected function updateConfiguration($key, $value)
    {
        config(["mercure.{$key}" => $value]);
    }

    protected function getPublisherContract(): PublisherInterface
    {
        return app(PublisherInterface::class);
    }

    protected function getMercureAdapter(): Mercure
    {
        return new Mercure($this->getPublisherContract());
    }

    protected function registerNotificationDriver($app)
    {
        $this->updateConfiguration('enable_notification', true);
    }

    protected function registerBroadcastingDriver($app)
    {
        $this->updateConfiguration('enable_broadcasting', true);
    }

    protected function registerMercureConfiguration($app)
    {
        $app->config->set(['broadcasting.default' => 'mercure']);
        $app->config->set(['broadcasting.connections.mercure' => ['driver' => 'mercure',],]);
    }
}
