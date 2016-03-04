<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model;

interface RecordReferenceInterface
{
    /**
     * @return string
     */
    public function getId();

    /**
     * @return int
     */
    public function getDataboxId();

    /**
     * @return int
     */
    public function getRecordId();
}
