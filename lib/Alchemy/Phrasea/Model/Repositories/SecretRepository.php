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

use Doctrine\ORM\EntityRepository;
use Entities\Secret;

class SecretRepository extends EntityRepository
{
    public function save(Secret $secret)
    {
        $this->_em->persist($secret);
        $this->_em->flush($secret);
    }
}
