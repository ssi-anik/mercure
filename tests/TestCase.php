<?php

namespace Anik\Mercure\Tests;

use Anik\Mercure\Provider\MercureServiceProvider;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
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

    protected function getMockedHttpClient(callable $callback, string $response = ''): MockHttpClient
    {
        return new MockHttpClient(function (
            string $method,
            string $url,
            array $options = []
        ) use (
            $callback,
            $response
        ): ResponseInterface {
            call_user_func_array($callback, [$method, $url, $options]);

            return new MockResponse($response);
        });
    }

    protected function configWithHttpClient(callable $callback, string $response = '', array $options = []): array
    {
        $httpClient = $this->getMockedHttpClient($callback, $response);

        return $this->config(array_merge_recursive([
            'set' => [
                'http_client' => $httpClient,
            ],
        ], $options));
    }
}
