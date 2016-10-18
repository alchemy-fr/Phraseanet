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

class RawFieldMapping extends FieldMapping
{

    /**
     * @param string $type
     */
    public function __construct($type)
    {
        parent::__construct('raw', $type);
    }

    /**
     * @return array
     */
    protected function getProperties()
    {
        return [ 'index' => 'not_analyzed' ];
    }
}
