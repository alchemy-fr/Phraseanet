<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Media;

interface TechnicalDataSet extends \ArrayAccess, \Countable, \Traversable
{
    /**
     * Get technical data values indexed by name
     * @return mixed[]
     */
    public function getValues();

    /**
     * Return whether a given set is empty.
     *
     * @return bool
     */
    public function isEmpty();
}
