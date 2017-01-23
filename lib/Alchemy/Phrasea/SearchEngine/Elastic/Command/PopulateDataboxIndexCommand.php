<?php

/*
 * This file is part of phrasea-4.0.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Command;

class PopulateDataboxIndexCommand 
{
    /**
     * @var int[]
     */
    private $databoxIds = [];

    private $indexMask = 0;

    /**
     * @param int[] $databoxIds
     * @param int $indexMask
     */
    public function __construct(array $databoxIds, $indexMask)
    {
        $this->databoxIds = $databoxIds;
        $this->indexMask = $indexMask;
    }

    /**
     * @return bool
     */
    public function hasDataboxFilter()
    {
        return ! empty($this->databoxIds);
    }

    /**
     * @return int[]
     */
    public function getDataboxIds()
    {
        return $this->databoxIds;
    }

    /**
     * @return int
     */
    public function getIndexMask()
    {
        return $this->indexMask;
    }
}
