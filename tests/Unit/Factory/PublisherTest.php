<?php

namespace Anik\Mercure\Tests\Unit\Factory;

use Anik\Mercure\Exception\MercureException;
use Anik\Mercure\Factory\Publisher;
use Anik\Mercure\Tests\TestCase;
use Firebase\JWT\JWT;
use Symfony\Component\Mercure\Publisher as MercurePublisher;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;

class PublisherTest extends TestCase
{
    public function testFactoryShouldReturnPublisherInterface()
    {
        $publisher = Publisher::instance($this->config());
        $this->assertTrue($publisher instanceof PublisherInterface);
        $this->assertTrue($publisher instanceof MercurePublisher);
    }

    public function testShouldThrowExceptionForNoUrl()
    {
        $config = $this->config(['unset' => ['url']]);
        $this->expectException(MercureException::class);
        Publisher::instance($config);
    }

    public function testShouldThrowExceptionForNoJwtOrProviderOrSecretAndPayload()
    {
        $config = $this->config(['unset' => ['secret', 'jwt']]);
        $this->expectException(MercureException::class);
        Publisher::instance($config);

        $config = $this->config(['unset' => ['payload', 'jwt']]);
        $this->expectException(MercureException::class);
        Publisher::instance($config);
    }

    public function testFactoryUsesHttpClient()
    {
        $config = $this->configWithHttpClient(null, 'id');

        $response = Publisher::instance($config)(new Update([]));
        $this->assertSame($response, 'id');
    }

    public function testFactoryUsesJwtProvider()
    {
        $config = $this->configWithHttpClient(function ($_, $__, $options) {
            $authHeader = $options['normalized_headers']['authorization'][0];
            $this->assertSame($authHeader, 'Authorization: Bearer ' . static::ANOTHER_JWT);
        }, '', [
            'unset' => ['jwt'],
            'set' => [
                'provider' => function () {
                    return static::ANOTHER_JWT;
                },
            ],
        ]);

        Publisher::instance($config)(new Update([]));
    }

    public function testFactoryCanGenerateJwtOnTheFly()
    {
        $config = $this->config(['unset' => ['jwt', 'provider']]);

        $config['http_client'] = $this->getMockedHttpClient(function ($method, $url, $options = []) {
            $token = substr($options['normalized_headers']['authorization'][0] ?? '', 22);
            // payload becomes an object when decoded
            $payload = json_decode(json_encode(JWT::decode($token, static::SECRET, ['HS256'])), true);
            $this->assertSame($this->config()['payload'], $payload);
        });

        Publisher::instance($config)(new Update([]));
    }

    public function testFactoryUseAlgoWhenGeneratesJwt()
    {
        $config = $this->configWithHttpClient(function ($_, $__, $options) {
            $authorization = $options['normalized_headers']['authorization'][0] ?? '';
            $token = substr($authorization, 22);
            [$header] = explode('.', $token);
            $this->assertSame('HS512', json_decode(base64_decode($header), true)['alg']);
        }, '', ['set' => ['algo' => 'HS512'], 'unset' => ['jwt', 'provider']]);

        Publisher::instance($config)(new Update([]));
    }
}
