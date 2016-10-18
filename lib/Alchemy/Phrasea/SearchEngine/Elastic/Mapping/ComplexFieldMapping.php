<?php

/*
 * This file is part of phrasea-4.0.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Mapping;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;

class ComplexFieldMapping extends FieldMapping
{
    /**
     * @var FieldMapping[]
     */
    private $children = [];

    /**
     * @param FieldMapping $child
     */
    public function addChild(FieldMapping $child)
    {
        $this->children[] = $child;
    }

    /**
     * @return FieldMapping[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->buildArray([ ]);
    }
}
