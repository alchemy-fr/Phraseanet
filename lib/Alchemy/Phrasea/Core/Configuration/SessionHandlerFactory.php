<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Cache\ConnectionFactory;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Utilities\RedisSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcacheSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\WriteCheckSessionHandler;

class SessionHandlerFactory
{
    private $connectionFactory;

    public function __construct(ConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * Creates a SessionHandlerInterface given a conf.
     *
     * @param PropertyAccess $conf
     *
     * @return \SessionHandlerInterface
     *
     * @throws \Alchemy\Phrasea\Exception\RuntimeException
     */
    public function create(PropertyAccess $conf)
    {
        $type = $conf->get(['main', 'session', 'type'], 'file');
        $options = $conf->get(['main', 'session', 'options'], []);
        $serverOpts = [
            'expiretime' => $conf->get(['main', 'session', 'ttl'], 86400),
            'prefix'     => $conf->get(['main', 'key']),
        ];

        switch (strtolower($type)) {
            case 'memcache':
                return new WriteCheckSessionHandler(
                    new MemcacheSessionHandler(
                        $this->connectionFactory->getMemcacheConnection($options, $serverOpts)
                    )
                );
            case 'memcached':
                return new WriteCheckSessionHandler(
                    new MemcachedSessionHandler(
                        $this->connectionFactory->getMemcachedConnection($options, $serverOpts)
                    )
                );
            case 'file':
                return new NativeFileSessionHandler(isset($options['save-path']) ? $options['save-path'] : null);
            case 'redis':
                return new WriteCheckSessionHandler(
                    new RedisSessionHandler(
                        $this->connectionFactory->getRedisConnection($options, $serverOpts)
                    )
                );
            case 'native':
                return new NativeSessionHandler();
        }

        throw new RuntimeException(sprintf('Unable to create the specified session handler "%s"', $type));
    }
}
