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

class DataboxBoundRepositoryProvider
{
    /**
     * Repositories indexed by databox id
     *
     * @var object[]
     */
    private $repositories = [];

    /**
     * @var DataboxBoundRepositoryFactory
     */
    private $factory;

    public function __construct(DataboxBoundRepositoryFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param int $databoxId
     * @return object
     */
    public function getRepositoryForDatabox($databoxId)
    {
        if (!isset($this->repositories[$databoxId])) {
            $this->repositories[$databoxId] = $this->factory->createRepositoryFor($databoxId);
        }

        return $this->repositories[$databoxId];
    }
}
