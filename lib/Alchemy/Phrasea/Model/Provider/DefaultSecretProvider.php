<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Model\Provider;

use Alchemy\Phrasea\Model\Entities\Secret;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Repositories\SecretRepository;
use RandomLib\Generator;

/**
 * This provider implements ArrayAccess to be used with php-jwt library
 */
class DefaultSecretProvider implements SecretProvider, \ArrayAccess
{
    /** @var SecretRepository */
    private $repository;
    /** @var Generator */
    private $generator;
    /** @var Secret[] */
    private $secrets = [];

    public function __construct(SecretRepository $repository, Generator $generator)
    {
        $this->repository = $repository;
        $this->generator = $generator;
    }

    public function getSecretForUser(User $user)
    {
        $userKey = '_' . (string) $user->getId();

        if (isset($this->secrets[$userKey])) {
            return $this->secrets[$userKey];
        }

        if (null === $secret = $this->repository->findOneBy(['creator' => $user], ['created' => 'DESC'])) {
            $token = $this->generator->generateString(64, Generator::CHAR_ALNUM | Generator::CHAR_SYMBOLS);

            $secret = new Secret($user, $token);
            $this->repository->save($secret);
        }

        $this->secrets[$userKey] = $secret;

        return $secret;
    }

    public function offsetExists($offset)
    {
        return null !== $this->repository->find($offset);
    }

    public function offsetGet($offset)
    {
        $secret = $this->repository->find($offset);
        if (!$secret instanceof Secret) {
            throw new \RuntimeException('Undefined index: ' . $offset);
        }

        return $secret->getToken();
    }

    public function offsetSet($offset, $value)
    {
        throw new \LogicException('This ArrayAccess is non mutable.');
    }

    public function offsetUnset($offset)
    {
        throw new \LogicException('This ArrayAccess is non mutable.');
    }
}
