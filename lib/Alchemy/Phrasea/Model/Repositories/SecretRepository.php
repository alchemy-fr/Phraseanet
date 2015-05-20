<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\Secret;
use Doctrine\ORM\EntityRepository;

/**
 * This repository implements ArrayAccess to be used with php-jwt library
 */
class SecretRepository extends EntityRepository implements \ArrayAccess
{
    public function offsetExists($offset)
    {
        return null !== $this->find($offset);
    }

    public function offsetGet($offset)
    {
        return $this->find($offset);
    }

    public function offsetSet($offset, $value)
    {
        throw new \LogicException('This ArrayAccess is non mutable.');
    }

    public function offsetUnset($offset)
    {
        throw new \LogicException('This ArrayAccess is non mutable.');
    }

    public function save(Secret $secret)
    {
        $this->_em->persist($secret);
        $this->_em->flush($secret);
    }
}
