<?php


namespace Alchemy\Phrasea\SearchEngine;


use Alchemy\Phrasea\Cache\Cache;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Flag;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\GlobalStructure;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\MetadataHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;
use Alchemy\Phrasea\Utilities\Stopwatch;
use databox;

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
     * @param int $what    bitmask of what should be included in this structure, in fields, ...
     *
     * @return GlobalStructure
     */
    public function getGlobalStructureFromDataboxes(array $databoxes, $what = Structure::WITH_EVERYTHING)
    {
        $fields = [];
        $flags = [];

        $stopwatch = new Stopwatch("getGlobalStructureFromDataboxes");
        foreach ($databoxes as $databox) {

            // we will cache both FIELDS and FLAGS in the same entry :
            // it's small data and $what seems always WITH_EVERYTHING
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
                    $data['fields'][] = Field::createFromLegacyField($fieldStructure, $what);
                }
                foreach ($databox->getStatusStructure() as $status) {
                    $data['flags'][] = Flag::createFromLegacyStatus($status);
                }
                $this->cache->save($k, $data);
            }

            if($what & Structure::STRUCTURE_WITH_FIELDS) {
                $fields = array_merge($fields, $data['fields']);
            }

            if($what & Structure::STRUCTURE_WITH_FLAGS) {
                $flags  = array_merge($flags, $data['flags']);
            }
        }
        $stopwatch->lap('loop0');
        $r = new GlobalStructure($fields, $flags, MetadataHelper::createTags());

        $stopwatch->log();

        return $r;
    }

    public function deleteFromCache($databox)
    {
        $k = $this->getCacheKey("FieldsAndFlags", $databox);
        $this->cache->delete($k);
    }

    private function getCacheKey($what, databox $db)
    {
        return "es_db-" . $db->get_sbas_id() . '_' . $what;
    }
}