<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Databox\DataboxIterator;
use Alchemy\Phrasea\SearchEngine\Elastic\Command\PopulateDataboxIndexCommand;

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
     * @var DataboxIterator
     */
    private $databoxes;

    /**
     * @param Indexer $indexer
     * @param PropertyAccess $configuration
     * @param DataboxIterator $databoxes
     */
    public function __construct(Indexer $indexer, PropertyAccess $configuration, DataboxIterator $databoxes)
    {
        $this->indexer = $indexer;
        $this->configuration = $configuration;
        $this->databoxes = $databoxes;
    }

    /**
     * Creates all configured indices
     * @param bool $force Whether to ignore existing indices (existing indices will not be modified)
     */
    public function createIndices($force = false)
    {
        if ($this->indexer->indexExists()) {
            if (! $force) {
                throw new IndexAlreadyExistsException();
            }

            $this->indexer->deleteIndex();
        }

        if (! $this->indexer->indexExists()) {
            $this->indexer->createIndex();
        }
    }

    /**
     * Drops all configured indices
     */
    public function dropIndices()
    {
        if (! $this->indexer->indexExists()) {
            throw new MissingIndexException();
        }

        $this->indexer->deleteIndex();
    }

    /**
     * @return bool
     */
    public function indexExists()
    {
        return $this->indexer->indexExists();
    }

    public function populateRecordIndex()
    {

    }

    public function populateThesaurusIndex()
    {

    }

    /**
     * @return ElasticsearchOptions
     */
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

    /**
     * @param PopulateDataboxIndexCommand $populateCommand
     */
    public function populateIndices(PopulateDataboxIndexCommand $populateCommand)
    {
        if (! $this->indexExists()) {
            throw new \RuntimeException('Indices must be created before running populate.');
        }

        $indexMask = $populateCommand->getIndexMask();
        $databoxes = iterator_to_array($this->databoxes);

        if ($populateCommand->hasDataboxFilter()) {
            $databoxes = array_filter($databoxes, function (\databox $databox) use ($populateCommand) {
                return in_array($databox->get_sbas_id(), $populateCommand->getDataboxIds());
            });
        }

        foreach ($databoxes as $databox) {
            $this->indexer->populateIndex($indexMask, $databox);
        }
    }
}
