<?php

/*
 * This file has been written by Markus Bachmann and released under the MIT
 * license.
 */

namespace Alchemy\Phrasea\Utilities;

/**
 * RedisSessionHandler
 *
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class RedisSessionHandler implements \SessionHandlerInterface
{
    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @var integer
     */
    private $lifetime;

    /**
     * @var string Key prefix for shared environments.
     */
    private $prefix;

    /**
     * Constructor
     *
     * @param \Redis $redis   The redis instance
     * @param array  $options An associative array of Memcached options
     *
     * @throws \InvalidArgumentException When unsupported options are passed
     */
    public function __construct(\Redis $redis, array $options = [])
    {
        $this->redis = $redis;

        if ($diff = array_diff(array_keys($options), ['prefix', 'expiretime'])) {
            throw new \InvalidArgumentException(sprintf(
                'The following options are not supported "%s"', implode(', ', $diff)
            ));
        }

        $this->lifetime = isset($options['expiretime']) ? (int) $options['expiretime'] : 86400;
        $this->prefix = isset($options['prefix']) ? $options['prefix'] : 'sf2s';
    }

    /**
     * {@inheritDoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function read($sessionId)
    {
        return $this->redis->get($this->prefix.$sessionId) ?: '';
    }

    /**
     * {@inheritDoc}
     */
    public function write($sessionId, $data)
    {
        return $this->redis->setex($this->prefix.$sessionId, $this->lifetime, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function destroy($sessionId)
    {
        return 1 === $this->redis->del($this->prefix.$sessionId);
    }

    /**
     * {@inheritDoc}
     */
    public function gc($lifetime)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function close()
    {
        return true;
    }
}
