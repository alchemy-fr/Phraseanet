<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

class ElasticSearchManagementService
{
    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @var PropertyAccess
     */
    private $configuration;

    /**
     * @param Indexer $indexer
     * @param PropertyAccess $configuration
     */
    public function __construct(Indexer $indexer, PropertyAccess $configuration)
    {
        $this->indexer = $indexer;
        $this->configuration = $configuration;
    }

    /**
     * Creates all configured indices
     */
    public function createIndices()
    {
        if (! $this->indexer->indexExists()) {
            $this->indexer->createIndex();
        }
    }

    /**
     * Drops all configured indices
     */
    public function dropIndices()
    {
        if ($this->indexer->indexExists()) {
            $this->indexer->deleteIndex();
        }
    }

    /**
     * @return bool
     */
    public function indexExists()
    {
        return $this->indexer->indexExists();
    }

    public function getCurrentConfiguration()
    {
        $options = ElasticsearchOptions::fromArray($this->configuration->get(['main', 'search-engine', 'options'], []));

        if (empty($options->getIndexName())) {
            $options->setIndexName(strtolower(sprintf(
                'phraseanet_%s',
                str_replace([ '/', '.' ], [ '', '' ], $this->configuration->get(['main', 'key']))
            )));
        }

        return $options;
    }

    /**
     * Updates the search engine server configuration
     *
     * @param ElasticsearchOptions $options
     */
    public function updateConfiguration(ElasticsearchOptions $options)
    {
        $this->configuration->set(['main', 'search-engine', 'options'], $options->toArray());
    }
}
