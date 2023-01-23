<?php


namespace Alchemy\Phrasea\SearchEngine;


use Alchemy\Phrasea\Cache\Cache;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Flag;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\GlobalStructure;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\MetadataHelper;
use databox;

// use Alchemy\Phrasea\Utilities\Stopwatch;

class SearchEngineStructure
{

    /** @var Cache */
    private  $cache;

    /**
     * SearchEngineStructure constructor.
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param databox[] $databoxes
     */
    public function getGlobalStructureConflictsFromDataboxes(array $databoxes)
    {
        $fieldsByName = [];
        foreach ($databoxes as $databox) {
            foreach ($databox->get_meta_structure() as $fieldStructure) {
                $name = $fieldStructure->get_name();
            }
        }
    }

    /**
     * @param databox[] $databoxes
     *
     * @return GlobalStructure
     */
    public function getGlobalStructureFromDataboxes(array $databoxes)
    {
        $fields = [];
        $flags = [];

//        $stopwatch = new Stopwatch("getGlobalStructureFromDataboxes");
        foreach ($databoxes as $databox) {

            // we will cache both FIELDS and FLAGS in the same entry : it's small data
            $k = $this->getCacheKey("FieldsAndFlags", $databox);
            try {
                $data = $this->cache->get($k);
            }
            catch(\Exception $e) {
                $data = false;
            }
            if($data === false) {
                $data = [
                    'fields' => [],
                    'flags'  => []
                ];
                foreach ($databox->get_meta_structure() as $fieldStructure) {
                    $data['fields'][] = Field::createFromLegacyField($fieldStructure);
                }
                foreach ($databox->getStatusStructure() as $status) {
                    $data['flags'][] = Flag::createFromLegacyStatus($status);
                }
                $this->cache->save($k, $data);
            }

            $fields = array_merge($fields, $data['fields']);
            $flags  = array_merge($flags, $data['flags']);
        }
//        $stopwatch->lap('loop0');

        // tags does not depends on db
        $k = $this->getCacheKey("Tags");
        try {
            $tags = $this->cache->get($k);
        }
        catch(\Exception $e) {
            $tags = false;
        }
        if($tags === false) {
            $this->cache->save($k, ($tags = MetadataHelper::createTags()));
            // nb : tags is a hardcoded list, we don't need to clear this cache
        }

//        $r = new GlobalStructure($fields, $flags, $tags);
//        $stopwatch->log();
//        return $r;

        return new GlobalStructure($fields, $flags, $tags);
    }

    public function deleteFromCache($databox)
    {
        $k = $this->getCacheKey("FieldsAndFlags", $databox);
        $this->cache->delete($k);
    }

    /**
     * build a cache key to store es data, related to a db or not (if db is null)
     *
     * @param $what
     * @param databox|null $db
     * @return string
     */
    private function getCacheKey($what, databox $db = null)
    {
        return 'es' . ($db ? ('_db-'.$db->get_sbas_id()) : '') . '_' . $what;
    }
}