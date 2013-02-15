<?php

namespace Alchemy\Phrasea\Authentication\Phrasea;

use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class OldPasswordEncoder implements PasswordEncoderInterface
{
    public function encodePassword($raw, $salt)
    {
        return hash('sha256', $raw);
    }

    public function isPasswordValid($encoded, $raw, $salt)
    {
        return $this->encodePassword($raw, $salt) === $encoded;
    }
}
