<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic;

use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\RecordIndex;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\TermIndex;
use \databox;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\GlobalStructure;

class IndexLocator
{
    /**
     * @var \ArrayAccess
     */
    private $container;

    /** @var  string[] */
    private $locales;

    /**
     * @var
     */
    private $recordIndexKey;
    /**
     * @var
     */
    private $termIndexKey;

    /** @var  GlobalStructure[] */
    private $structureByDatabox;

    /**
     * @param \ArrayAccess $container
     * @param string $recordIndexKey
     * @param string $termIndexKey
     */
    public function __construct(\ArrayAccess $container, Array $locales, $recordIndexKey, $termIndexKey)
    {
        $this->container = $container;
        $this->locales = $locales;
        $this->recordIndexKey = $recordIndexKey;
        $this->termIndexKey = $termIndexKey;

        $this->structureByDatabox = [];
    }

    /**
     * @return TermIndex
     */
    public function getTermIndex()
    {
        return $this->container[$this->termIndexKey];
    }

    /**
     * @param databox $databox
     * @return RecordIndex
     */
    public function getRecordIndex(databox $databox)
    {
        // return $this->container[$this->recordIndexKey];

        $sbas_id = $databox->get_sbas_id();
        if(!array_key_exists($sbas_id, $this->structureByDatabox)) {
            $this->structureByDatabox[$sbas_id] = GlobalStructure::createFromDatabox($databox);
        }

        return new Indexer\RecordIndex($this->structureByDatabox[$sbas_id], $this->locales);
    }
}
