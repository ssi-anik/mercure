<?php

declare(strict_types=1);

namespace Anik\Mercure\Factory;

use Anik\Mercure\Exception\MercureException;
use Firebase\JWT\JWT;
use Symfony\Component\Mercure\Jwt\StaticJwtProvider;
use Symfony\Component\Mercure\Publisher as MercurePublisher;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

final class Publisher
{
    private function __construct()
    {
    }

    /**
     * @param array $config
     *
     * @return PublisherInterface
     * @throws MercureException|Throwable
     */
    public static function instance(array $config): PublisherInterface
    {
        $hubUrl = throw_unless($config['url'] ?? false, new MercureException('URL for the hub must be provided.'));

        if (isset($config['jwt'])) {
            $jwtProvider = new StaticJwtProvider($config['jwt']);
        } elseif (isset($config['provider']) && is_callable($config['provider'])) {
            $jwtProvider = $config['provider'];
        } elseif (isset($config['secret']) && isset($config['payload'])) {
            $algo = $config['algo'] ?? 'HS256';
            $jwtProvider = new StaticJwtProvider(JWT::encode($config['payload'], $config['secret'], $algo));
        } else {
            throw new MercureException(
                'Value missing in configuration. Configuration must have jwt or provider or secret and payload.'
            );
        }

        $httpClient = ($config['http_client'] ?? null) instanceof HttpClientInterface ? $config['http_client'] : null;

        return new MercurePublisher($hubUrl, $jwtProvider, $httpClient);
    }
}
