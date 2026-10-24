<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media;

use Firebase\JWT\Key;

/**
 * Exposes secrets indexed by key id as php-jwt keys for a given algorithm.
 */
class JwtKeySet implements \ArrayAccess
{
    /** @var array|\ArrayAccess */
    private $secrets;

    /** @var string */
    private $algorithm;

    /**
     * @param array|\ArrayAccess $secrets
     * @param string $algorithm
     */
    public function __construct($secrets, $algorithm)
    {
        $this->secrets = $secrets;
        $this->algorithm = $algorithm;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->secrets[$offset]);
    }

    public function offsetGet(mixed $offset): Key
    {
        return new Key($this->secrets[$offset], $this->algorithm);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('This ArrayAccess is non mutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('This ArrayAccess is non mutable.');
    }
}
