<?php

declare(strict_types=1);

namespace Anik\Mercure\Broadcaster;

use Anik\Mercure\Adapter\Mercure;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class MercureBroadcaster implements Broadcaster
{
    private $mercure;

    public function __construct(Mercure $mercure)
    {
        $this->mercure = $mercure;
    }

    public function auth($request)
    {
        /**
         * Mercure Hub handles the auth through subscriber jwt.
         * Application isn't even get informed if someone is connected.
         *
         * But, if someone wants to check through the `/broadcasting/auth` endpoint.
         * He should find AccessDeniedHttpException
         */

        // package `symfony/http-kernel` is the dependency of Laravel/Lumen. Thus the exception will be available.
        throw new AccessDeniedHttpException();
    }

    public function validAuthenticationResponse($request, $result)
    {
        /**
         * Clients can connect to the mercure hub using
         * the provided topic name and information with the subscription jwt
         *
         * This code is not meant to be reached. As it will only reachable from the `self::auth()` method.
         */

        // package `symfony/http-kernel` is the dependency of Laravel/Lumen. Thus the exception will be available.
        throw new AccessDeniedHttpException();
    }

    public function broadcast(array $channels, $event, array $payload = [])
    {
        $this->mercure->publish($channels, $event, $payload);
    }
}
