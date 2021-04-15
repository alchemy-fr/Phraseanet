<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus;

class Filter
{
    private $databox_ids;
    private $paths;

    public static function childOfConcepts($databox_id, array $concepts)
    {
        return new self([$databox_id], Concept::toPathArray($concepts));
    }

    public static function byDatabox($databox_id)
    {
        return new self([$databox_id], []);
    }

    public static function byDataboxes($databox_ids)
    {
        return new self($databox_ids, []);
    }

    public static function dump(Filter $filter)
    {
        return $filter->getQueryFilter();   // perfect as an array
    }

    private function __construct($databox_ids, array $paths)
    {
        $this->databox_ids = $databox_ids;
        $this->paths = $paths;
    }

    public function getQueryFilter()
    {
        $filter = [
            'terms' => [
                'databox_id' => $this->databox_ids
            ]
        ];
        if(count($this->paths) > 0) {
            $filter['terms']['path'] = $this->paths;
        }

        return $filter;
    }

    public function getQueryFilters()
    {
        $filters = [
            [
                'terms' => [
                    'databox_id' => $this->databox_ids
                ]
            ]
        ];
        if(!empty($this->paths)) {
            if (count($this->paths) == 1) {
                $filters[] = [
                    'term' => [
                        'path' => $this->paths[0]
                    ]
                ];
            }
            else {
                $filters[] = [
                    'terms' => [
                        'path' => $this->paths
                    ]
                ];
            }
        }

        return $filters;
    }

}
