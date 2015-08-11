<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Model\Provider;

use Alchemy\Phrasea\Model\Repositories\SecretRepository;
use Entities\Secret;
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

    public function __construct(SecretRepository $repository, Generator $generator)
    {
        $this->repository = $repository;
        $this->generator = $generator;
    }

    public function getSecretForUser($userId)
    {
        $secret = $this->repository->findOneBy(['creatorId' => $userId], ['created' => 'DESC']);
        if ($secret) {
            return $secret;
        }

        $token = $this->generator->generateString(64, Generator::CHAR_ALNUM | Generator::CHAR_SYMBOLS);

        $secret = new Secret($userId, $token);
        $this->repository->save($secret);

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
