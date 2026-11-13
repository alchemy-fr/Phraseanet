<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Provider\Csrf;

use Pimple\Container;
use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;
use Symfony\Component\Security\Csrf\TokenStorage\ClearableTokenStorageInterface;

/**
 * Stores CSRF tokens in the "session" service, whether or not a request is being handled.
 */
class SessionTokenStorage implements ClearableTokenStorageInterface
{
    private $app;
    private $namespace;

    public function __construct(Container $app, string $namespace = '_csrf')
    {
        $this->app = $app;
        $this->namespace = $namespace;
    }

    public function getToken(string $tokenId): string
    {
        $session = $this->app['session'];

        if (!$session->has($this->namespace.'/'.$tokenId)) {
            throw new TokenNotFoundException('The CSRF token with ID '.$tokenId.' does not exist.');
        }

        return (string) $session->get($this->namespace.'/'.$tokenId);
    }

    public function setToken(string $tokenId, #[\SensitiveParameter] string $token): void
    {
        $this->app['session']->set($this->namespace.'/'.$tokenId, $token);
    }

    public function hasToken(string $tokenId): bool
    {
        return $this->app['session']->has($this->namespace.'/'.$tokenId);
    }

    public function removeToken(string $tokenId): ?string
    {
        return $this->app['session']->remove($this->namespace.'/'.$tokenId);
    }

    public function clear(): void
    {
        $session = $this->app['session'];

        foreach (array_keys($session->all()) as $key) {
            if (str_starts_with($key, $this->namespace.'/')) {
                $session->remove($key);
            }
        }
    }
}
