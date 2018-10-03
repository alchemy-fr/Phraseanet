<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media;

class RecordTechnicalDataSetRepositoryProvider
{
    /**
     * @var RecordTechnicalDataSetRepository[]
     */
    private $repositories = [];

    /**
     * @var RecordTechnicalDataSetRepositoryFactory
     */
    private $factory;

    public function __construct(RecordTechnicalDataSetRepositoryFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param int $databoxId
     * @return RecordTechnicalDataSetRepository
     */
    public function getRepositoryFor($databoxId)
    {
        if (!isset($this->repositories[$databoxId])) {
            $this->repositories[$databoxId] = $this->factory->createRepositoryForDatabox($databoxId);
        }

        return $this->repositories[$databoxId];
    }
}
