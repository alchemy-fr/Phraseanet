<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Authentication\Phrasea;

use App\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class SimplePasswordEncoder implements PasswordEncoderInterface
{

    /**
     * {@inheritdoc}
     */
    public function encodePassword($raw, $salt)
    {
        return hash('sha512', $raw . $salt);
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordValid($encoded, $raw, $salt)
    {
        return $this->encodePassword($raw, $salt) === $encoded;
    }
}
