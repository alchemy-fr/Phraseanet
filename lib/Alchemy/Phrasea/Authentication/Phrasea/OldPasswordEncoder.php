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

use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class OldPasswordEncoder implements PasswordEncoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function encodePassword($raw, $salt)
    {
        return hash('sha256', $raw);
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordValid($encoded, $raw, $salt)
    {
        return $this->encodePassword($raw, $salt) === $encoded;
    }
}
