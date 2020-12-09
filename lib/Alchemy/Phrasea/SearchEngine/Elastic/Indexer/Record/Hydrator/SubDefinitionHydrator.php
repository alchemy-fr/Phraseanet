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

    /** @var  bool */
    private $populatePermalinks;

    public function __construct(Application $app, databox $databox, bool $populatePermalinks)
    {
        $this->app = $app;
        $this->databox = $databox;
        $this->populatePermalinks = $populatePermalinks;
    }

    public function hydrateRecords(array &$records)
    {
        foreach(array_keys($records) as $rid) {
            try {
                $subdefs = $this->databox->getRecordRepository()->find($rid)->get_subdefs();
                $pls = [];
                if ($this->populatePermalinks) {
                    $pls = array_map(
                    /** media_Permalink_Adapter|null $plink */
                        function($plink) {
                            return $plink ? ((string) $plink->get_url()) : null;
                        },
                        media_Permalink_Adapter::getMany($this->app, $subdefs, false) // false: don't create missing plinks
                    );
                }

                foreach($subdefs as $subdef) {
                    $name = $subdef->get_name();
                    if(substr(($path = $subdef->get_path()), -1) !== '/') {
                        $path .= '/';
                    }
                    $records[$rid]['subdefs'][$name] = array(
                        'path' => $path . $subdef->get_file(),
                        'width' => $subdef->get_width(),
                        'height' => $subdef->get_height(),
                        'size' => $subdef->get_size(),
                        'mime' => $subdef->get_mime(),
                        'permalink' => array_key_exists($name, $pls) ? $pls[$name] : null
                    );

                }
            }
            catch (\Exception $e) {
                // cant get record ? ignore
            }

        }
    }

}
