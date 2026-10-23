<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication\Phrasea;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Symfony\Component\PasswordHasher\LegacyPasswordHasherInterface;

class PasswordEncoder implements LegacyPasswordHasherInterface
{
    private $key;

    public function __construct($key)
    {
        if ('' === trim($key)) {
            throw new InvalidArgumentException('You must provide a non empty key');
        }
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function hash(string $plainPassword, ?string $salt = null): string
    {
        return hash_hmac('sha512', $plainPassword . $salt, $this->key);
    }

    /**
     * {@inheritdoc}
     */
    public function verify(string $hashedPassword, string $plainPassword, ?string $salt = null): bool
    {
        return hash_equals($hashedPassword, $this->hash($plainPassword, $salt));
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return false;
    }
}
