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

class ComplexFieldMapping extends ComplexMapping
{

    public function __construct($name, $type = null)
    {
        parent::__construct($name, $type ?: FieldMapping::TYPE_OBJECT);
    }

    /**
     * @return array
     */
    protected function getProperties()
    {
        return [ 'fields' => parent::getProperties() ];
    }
}
