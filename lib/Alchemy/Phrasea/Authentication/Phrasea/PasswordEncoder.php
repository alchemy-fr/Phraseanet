<?php

namespace Alchemy\Phrasea\Authentication\Phrasea;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class PasswordEncoder implements PasswordEncoderInterface
{
    private $key;

    public function __construct($key)
    {
        if ('' === trim($key)) {
            throw new InvalidArgumentException('You must provide a non empty key');
        }
        $this->key = $key;
    }

    public function encodePassword($raw, $salt)
    {
        return hash_hmac('sha512', $raw . $salt, $this->key);
    }

    public function isPasswordValid($encoded, $raw, $salt)
    {
        return $this->encodePassword($raw, $salt) === $encoded;
    }
}
