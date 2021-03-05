<?php

namespace Unit\Factory;

use Anik\Mercure\Exception\MercureException;
use Anik\Mercure\Factory\Publisher;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mercure\Publisher as MercurePublisher;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Contracts\HttpClient\ResponseInterface;

class PublisherFactoryTest extends TestCase
{
    public const URL = 'http://127.0.0.1:9000/.well-known/mercure';
    public const JWT = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdfX0.OwYVEF9qsVOpHeCx-iBV5jMVl0BVGivm0v8fsJTW5rw';
    public const ANOTHER_JWT = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdfSwiZXh0cmFfZGF0YSI6ImhlcmUifQ.bcEXNZ5sfW5WXWs7ekkMFI540X5UELrOi9tgav3eE3Q';
    public const SECRET = 'secret';

    protected function config(): array
    {
        return [
            'url' => self::URL,
            'jwt' => self::JWT,
            'provider' => null,
            'secret' => self::SECRET,
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
    }

    public function testFactoryShouldReturnPublisherInterface()
    {
        $publisher = Publisher::instance($this->config());
        $this->assertTrue($publisher instanceof PublisherInterface);
        $this->assertTrue($publisher instanceof MercurePublisher);
    }

    public function testShouldReceiveExceptionForNoUrl()
    {
        $config = $this->config();
        unset($config['url']);
        $this->expectException(MercureException::class);
        Publisher::instance($config);
    }

    public function testShouldReceiveExceptionForNoJwtOrProviderOrSecretAndPayload()
    {
        $config1 = $config2 = $this->config();
        unset($config1['secret'], $config1['jwt']);
        $this->expectException(MercureException::class);
        Publisher::instance($config1);

        unset($config2['payload'], $config2['jwt']);
        $this->expectException(MercureException::class);
        Publisher::instance($config2);
    }

    public function testFactoryUsesHttpClient()
    {
        $config = $this->config();
        $config['http_client'] = new MockHttpClient(
            function (string $method, string $url, array $options = []): ResponseInterface {
                return new MockResponse('id');
            }
        );

        $response = Publisher::instance($config)(new Update([]));
        $this->assertSame($response, 'id');
    }

    public function testFactoryUsesJwtProvider()
    {
        $config = $this->config();
        unset($config['jwt']);

        $config['provider'] = function () {
            return self::ANOTHER_JWT;
        };

        $config['http_client'] = new MockHttpClient(
            function (string $method, string $url, array $options = []): ResponseInterface {
                $this->assertSame(
                    $options['normalized_headers']['authorization'][0],
                    'Authorization: Bearer ' . self::ANOTHER_JWT
                );

                return new MockResponse();
            }
        );

        Publisher::instance($config)(new Update([]));
    }

    public function testFactoryCanGenerateJwtOnTheFly()
    {
        $config = $this->config();
        unset($config['jwt'], $config['provider']);

        $config['http_client'] = new MockHttpClient(
            function (string $method, string $url, array $options = []): ResponseInterface {
                $authorization = $options['normalized_headers']['authorization'][0] ?? '';
                $token = substr($authorization, 22);
                // payload becomes an object when decoded
                $payload = json_decode(json_encode(JWT::decode($token, self::SECRET, ['HS256'])), true);
                $this->assertSame($this->config()['payload'], $payload);
                return new MockResponse();
            }
        );

        Publisher::instance($config)(new Update([]));
    }

    public function testFactoryUseAlgoWhenGeneratesJWT()
    {
        $config = $this->config();
        $config['algo'] = 'HS512';
        unset($config['jwt'], $config['provider']);

        $config['http_client'] = new MockHttpClient(
            function (string $method, string $url, array $options = []): ResponseInterface {
                $authorization = $options['normalized_headers']['authorization'][0] ?? '';
                $token = substr($authorization, 22);
                [$header] = explode('.', $token);
                $this->assertSame('HS512', json_decode(base64_decode($header), true)['alg']);
                return new MockResponse();
            }
        );

        Publisher::instance($config)(new Update([]));
    }
}
