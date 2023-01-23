<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator;

use Alchemy\Phrasea\Application;
use databox;
use Doctrine\DBAL\Connection;
use media_Permalink_Adapter;

class SubDefinitionHydrator implements HydratorInterface
{
    const ORIENTATION_SQUARE = 'S';
    const ORIENTATION_LANDSCAPE = 'L';
    const ORIENTATION_PORTRAIT = 'P';
    const ORIENTATION_UNKNOW = '';

//    const ORIENTATION_SQUARE = 1;
//    const ORIENTATION_LANDSCAPE = 2;
//    const ORIENTATION_PORTRAIT = 3;
//    const ORIENTATION_UNKNOW = 0;


    /** @var Application */
    private $app;

    /** @var databox */
    private $databox;

    /** @var  boolean */
    private $populatePermalinks;

    public function __construct(Application $app, databox $databox, $populatePermalinks)
    {
        $this->app = $app;
        $this->databox = $databox;
        $this->populatePermalinks = $populatePermalinks;
    }

    public function hydrateRecords(array &$records)
    {
        if ($this->populatePermalinks) {
            $this->hydrateRecordsWithPermalinks($records);
        }
        else {
            $this->hydrateRecordsWithoutPermalinks($records);
        }
    }

    private function hydrateRecordsWithPermalinks(&$records)
    {
        foreach (array_keys($records) as $rid) {

            $record = &$records[$rid];

            try {
                $subdefs = $this->databox->getRecordRepository()->find($rid)->get_subdefs();

                $pls = array_map(
                /** media_Permalink_Adapter|null $plink */
                    function ($plink) {
                        return $plink ? ((string)$plink->get_url()) : null;
                    },
                    media_Permalink_Adapter::getMany($this->app, $subdefs, false) // false: don't create missing plinks
                );

                foreach ($subdefs as $subdef) {
                    $name = $subdef->get_name();
                    if (substr(($path = $subdef->get_path()), -1) !== '/') {
                        $path .= '/';
                    }
                    $record['subdefs'][$name] = [
                        'path'      => $path . $subdef->get_file(),
                        'width'     => $subdef->get_width(),
                        'height'    => $subdef->get_height(),
                        'size'      => $subdef->get_size(),
                        'mime'      => $subdef->get_mime(),
                        'permalink' => array_key_exists($name, $pls) ? $pls[$name] : null
                    ];
                    if ($name == "thumbnail") {
                        $this->setOrientation($record, $subdef->get_width(), $subdef->get_height());
                    }
                }
            }
            catch (\Exception $e) {
                // cant get record ? ignore
            }
        }
    }

    private function hydrateRecordsWithoutPermalinks(&$records)
    {
        $sql = <<<SQL
            SELECT
              s.record_id,
              s.name,
              s.height,
              s.width,
              s.size,
              s.mime,
              CONCAT(TRIM(TRAILING '/' FROM s.path), '/', s.file) AS path
            FROM subdef s
            WHERE s.record_id IN (?)
            ORDER BY s.record_id
SQL;
        $statement = $this->databox->get_connection()->executeQuery($sql,
            [array_keys($records)],
            [Connection::PARAM_INT_ARRAY]
        );

        $current_rid = null;
        $record = null;
        while ($subdef = $statement->fetch()) {
            $rid = $subdef['record_id'];
            $record = &$records[$rid];
            $name = $subdef['name'];
            $record['subdefs'][$name] = [
                'path'      => $subdef['path'],
                'width'     => $subdef['width'],
                'height'    => $subdef['height'],
                'size'      => $subdef['size'],
                'mime'      => $subdef['mime'],
                'permalink' => null
            ];
            if ($name == "thumbnail") {
                $this->setOrientation($record, $subdef['width'], $subdef['height']);
            }
        }
    }

    private function setOrientation(&$record, $w, $h)
    {
        $o = self::ORIENTATION_UNKNOW;
        if ($w !== '' && $h !== '' && !is_null($w) && !is_null($h)) {
            $w = (int)$w;
            $h = (int)$h;
            $o = $w == $h ? self::ORIENTATION_SQUARE : ($w > $h ? self::ORIENTATION_LANDSCAPE : self::ORIENTATION_PORTRAIT);
        }
        if(!array_key_exists('metadata_tags', $record)) {
            $record['metadata_tags'] = [];
        }
        $record['metadata_tags']['ThumbnailOrientation'] = $o;
    }
}

