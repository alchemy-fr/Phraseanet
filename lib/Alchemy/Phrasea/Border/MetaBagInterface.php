<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border;

interface MetaBagInterface
{
    /**
     * Converts a MetaBag to an acceptable array of metadata for a record update
     *
     * The structure of the array depends of the target databox description
     * structure.
     *
     * @param \databox_descriptionStructure $structure
     *
     * @return array
     */
    public function toMetadataArray(\databox_descriptionStructure $structure);
}
