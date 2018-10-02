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

class ComplexPropertiesMapping extends ComplexMapping
{

    public function __construct($name)
    {
        parent::__construct($name, FieldMapping::TYPE_OBJECT);
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return [ 'properties' => parent::getProperties() ];
    }
}
