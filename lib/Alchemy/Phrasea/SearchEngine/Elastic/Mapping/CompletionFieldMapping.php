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

class CompletionFieldMapping extends FieldMapping
{
    /**
     * @param string $name
     */
    public function __construct($name)
    {
        parent::__construct($name, 'completion');
    }

    /**
     * @return array
     */
    protected function getProperties()
    {
        return [
            'context' => [
                'base_id' => [
                    'type' => 'category',
                    'path' => 'base_id',
                    'default' => ''
                ],
                'record_type' => [
                    'type' => 'category',
                    'path' => 'record_type',
                    'default' => ''
                ]
            ],
            //    'analyzer' => 'simple',
            //    'search_analyzer' => 'simple',
            //    'payloads' => false
        ];
    }
}