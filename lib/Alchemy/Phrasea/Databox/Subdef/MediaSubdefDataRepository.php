<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Databox\Subdef;

interface MediaSubdefDataRepository
{
    /**
     * @param int[] $recordIds
     * @param string[]|null $names null means to look for all available names
     * @return array[]
     */
    public function findByRecordIdsAndNames(array $recordIds, array $names = null);

    /**
     * @param array[] $subdefIds (should be a list of associative arrays with record_id and name keys)
     * @return int The number of affected rows
     */
    public function delete(array $subdefIds);

    /**
     * @param array $data
     * @return void
     */
    public function save(array $data);
}
