<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Databox;

class ClosureDataboxBoundRepositoryFactory implements DataboxBoundRepositoryFactory
{
    /**
     * @var callable
     */
    private $factory;

    public function __construct(callable $factory)
    {
        $this->factory = $factory;
    }

    public function createRepositoryFor($databoxId)
    {
        $factory = $this->factory;

        return $factory($databoxId);
    }
}
