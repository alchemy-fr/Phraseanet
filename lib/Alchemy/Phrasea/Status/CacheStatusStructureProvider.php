<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Status;

use Alchemy\Phrasea\Application;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Provides status structure definition using cache if possible
 */
class CacheStatusStructureProvider implements StatusStructureProviderInterface
{
    private $cache;
    private $provider;

    public function __construct(Cache $cache, StatusStructureProviderInterface $provider)
    {
        $this->cache = $cache;
        $this->provider = $provider;
    }

    public function getStructure(\databox $databox)
    {
        if (false !== ($status = $this->cache->fetch($this->get_cache_key($databox->get_sbas_id())))) {

            return new StatusStructure($databox, new ArrayCollection(json_decode($status, true)));
        }

        $structure = $this->provider->getStructure($databox);

        $this->cache->save($this->get_cache_key($databox->get_sbas_id()), json_encode($structure->toArray()));

        return $structure;
    }

    public function deleteStatus(StatusStructure $structure, $bit)
    {
        $databox = $structure->getDatabox();

        $this->provider->deleteStatus($structure, $bit);

        $this->cache->save($this->get_cache_key($databox->get_sbas_id()), json_encode($structure->toArray()));

        return $structure;
    }

    public function updateStatus(StatusStructure $structure, $bit, array $properties)
    {
        $databox = $structure->getDatabox();

        $this->provider->updateStatus($structure, $bit, $properties);

        // note : cache->save(...) should be ok but it crashes some callers, ex. adding a sb (?)
        $this->cache->delete($this->get_cache_key($databox->get_sbas_id()));

        return $structure;
    }

    private function get_cache_key($databox_id)
    {
        return sprintf('status_%s', $databox_id);
    }
}
