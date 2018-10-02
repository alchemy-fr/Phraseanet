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

interface DataboxGroupable
{
    /**
     * Group instance by Databox Id
     *
     * @return array<int,array>
     */
    public function groupByDatabox();

    /**
     * Returns databoxes ids
     *
     * @return int[]
     */
    public function getDataboxIds();

    /**
     * @param int $databoxId
     * @return array
     */
    public function getDataboxGroup($databoxId);

    /**
     * Reorder groups if needed
     *
     * @return void
     */
    public function reorderGroups();
}
