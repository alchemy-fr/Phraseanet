<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Status;

/**
 * Interface defined for status definition providers
 */
interface StatusStructureProviderInterface
{
    /**
     * Returns a status structure for a given databox
     *
     * @param \databox $databox
     *
     * @return StatusStructure
     */
    public function getStructure(\databox $databox);

    /**
     * Deletes status at nth bit from given status structure
     *
     * @param StatusStructure $structure
     * @param int             $bit
     *
     * @return StatusStructure
     */
    public function deleteStatus(StatusStructure $structure, $bit);

    /**
     * Updates status at nth bit from given status structure
     *
     * @param StatusStructure $structure
     * @param int             $bit
     *
     * @return StatusStructure
     */
    public function updateStatus(StatusStructure $structure, $bit, array $properties);
}
