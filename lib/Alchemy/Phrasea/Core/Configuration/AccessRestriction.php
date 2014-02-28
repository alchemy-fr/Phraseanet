<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Cache\Cache;
use Alchemy\Phrasea\Model\Entities\Collection;
use Alchemy\Phrasea\Model\Entities\Databox;
use Psr\Log\LoggerInterface;

class AccessRestriction
{
    private $conf;
    private $appbox;
    private $logger;
    private $cache;

    public function __construct(Cache $cache, PropertyAccess $conf, \appbox $appbox, LoggerInterface $logger)
    {
        $this->conf = $conf;
        $this->appbox = $appbox;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    /**
     * Returns true if a configuration is set.
     *
     * @return Boolean
     */
    public function isRestricted()
    {
        $this->load();

        return $this->cache->fetch('restricted');
    }

    /**
     * Returns true if a databox is available given a configuration.
     *
     * @param \databox $databox
     *
     * @return Boolean
     */
    public function isDataboxAvailable(\databox $databox)
    {
        if (!$this->isRestricted()) {
            return true;
        }

        return in_array($databox->get_sbas_id(), $this->cache->fetch('available_databoxes'), true);
    }

    /**
     * Returns true if a collection is available given a configuration.
     *
     * @param \collection $collection
     *
     * @return Boolean
     */
    public function isCollectionAvailable(\collection $collection)
    {
        if (!$this->isRestricted()) {
            return true;
        }

        $availableCollections = $this->cache->fetch('available_collections_'.$collection->get_databox()->get_sbas_id()) ?: [];

        return in_array($collection->get_base_id(), $availableCollections, true);
    }

    private function load()
    {
        if ($this->cache->fetch('loaded')) {
            return;
        }

        $this->cache->save('loaded', true);

        $allowedDataboxIds = array_map(function ($dbConf) {
            return $dbConf['id'];
        }, $this->conf->get('databoxes', []));

        if (count($allowedDataboxIds) === 0) {
            $this->cache->save('restricted', false);

            return;
        }

        $this->cache->save('restricted', true);

        $databoxIds = array_map(function (\databox $databox) { return $databox->get_sbas_id(); }, $this->appbox->get_databoxes());
        $errors = array_diff($allowedDataboxIds, $databoxIds);

        if (count($errors) > 0) {
            $this->logger->error(sprintf('Misconfiguration for allowed databoxes : ids %s do not exist', implode(', ', $errors)));
        }

        $allowedDataboxIds = array_intersect($allowedDataboxIds, $databoxIds);
        $this->cache->save('available_databoxes', $allowedDataboxIds);

        $this->loadCollections();
    }

    private function loadCollections()
    {
        $allowedDataboxIds = $this->cache->fetch('available_databoxes');

        foreach ($this->conf->get('databoxes') as $databox) {
            if (!in_array($databox['id'], $allowedDataboxIds, true)) {
                continue;
            }

            $collections = isset($databox['collections']) ? (is_array($databox['collections']) ? $databox['collections'] : [$databox['collections']]) : [];

            $availableBaseIds = array_map(function (\collection $collection) { return $collection->get_base_id(); }, $this->appbox->get_databox($databox['id'])->get_collections());
            $errors = array_diff($collections, $availableBaseIds);

            if (count($errors) > 0) {
                $this->logger->error(sprintf('Misconfiguration for allowed collections : ids %s do not exist', implode(', ', $errors)));
            }

            $collections = array_intersect($collections, $availableBaseIds);

            $this->cache->save('available_collections_'.$databox['id'], $collections);
        }
    }
}
