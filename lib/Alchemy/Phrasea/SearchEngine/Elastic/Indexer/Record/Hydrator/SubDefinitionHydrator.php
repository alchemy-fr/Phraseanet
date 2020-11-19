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
    /** @var Application  */
    private $app;

    /** @var databox */
    private $databox;

    public function __construct(Application $app, databox $databox)
    {
        $this->app = $app;
        $this->databox = $databox;
    }

    public function hydrateRecords(array &$records)
    {
        $sql = <<<SQL
            SELECT
              s.record_id,
              s.name,
              s.height,
              s.width,
              CONCAT(TRIM(TRAILING '/' FROM s.path), '/', s.file) AS path
            FROM subdef s
            WHERE s.record_id IN (?)
            ORDER BY s.record_id
SQL;
        $statement = $this->databox->get_connection()->executeQuery($sql,
            array(array_keys($records)),
            array(Connection::PARAM_INT_ARRAY)
        );

        $current_rid = null;
        $record = null;
        $pls = [];
        while ($subdef = $statement->fetch()) {

            // too bad : to get permalinks we must instantiate a recordadapter
            if($subdef['record_id'] !== $current_rid) {
                // sql is ordered by rid so we won't find the same record twice.
                $current_rid = $subdef['record_id'];

                // getting all subdefs once is faster than getting subdef one by one in the main loop
                $pls = [];  // permalinks, by subdef name
                try {
                    $subdefs = $this->databox->getRecordRepository()->find($current_rid)->get_subdefs();
                    $pls = array_map(
                        function(media_Permalink_Adapter $plink) {
                            return (string) $plink->get_url();
                        },
                        media_Permalink_Adapter::getMany($this->app, $subdefs)
                    );
                }
                catch (\Exception $e) {
                    // cant get record ? ignore
                }
            }

            $name = $subdef['name'];
            $records[$subdef['record_id']]['subdefs'][$name] = array(
                // 'path' => $subdef['path'],
                'width' => $subdef['width'],
                'height' => $subdef['height'],
                'permalink' => array_key_exists($name, $pls) ? $pls[$name] : null
            );
        }
    }
}
