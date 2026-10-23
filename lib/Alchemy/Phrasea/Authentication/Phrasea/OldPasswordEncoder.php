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

use Symfony\Component\PasswordHasher\LegacyPasswordHasherInterface;

class OldPasswordEncoder implements LegacyPasswordHasherInterface
{
    /**
     * {@inheritdoc}
     */
    public function hash(string $plainPassword, ?string $salt = null): string
    {
        return hash('sha256', $plainPassword);
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
