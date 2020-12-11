<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Delegate\FetcherDelegateInterface;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Fetcher;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\CoreHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\FlagHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\MetadataHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\SubDefinitionHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\ThesaurusHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\TitleHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\CandidateTerms;


class DataboxFetcherFactory
{
    /**
     * @var PropertyAccess       phraseanet configuration
     */
    private $conf;

    /**
     * @var Application
     */
    private $app;

    /**
     * @var string
     */
    private $structureKey;

    /**
     * @var string
     */
    private $thesaurusKey;

    /**
     * @var RecordHelper
     */
    private $recordHelper;

    /** @var  ElasticsearchOptions */
    private $options;

    /** @var  boolean */
    private $populatePermalinks;

    /**
     * @param PropertyAccess $conf
     * @param RecordHelper $recordHelper
     * @param ElasticsearchOptions $options
     * @param Application $app
     * @param string $structureKey
     * @param string $thesaurusKey
     */
    public function __construct(PropertyAccess $conf, RecordHelper $recordHelper, ElasticsearchOptions $options, Application $app, $structureKey, $thesaurusKey)
    {
        $this->conf         = $conf;
        $this->recordHelper = $recordHelper;
        $this->options      = $options;
        $this->app          = $app;
        $this->structureKey = $structureKey;
        $this->thesaurusKey = $thesaurusKey;
        $this->populatePermalinks = $conf->get(['main', 'search-engine', 'options', 'populate_permalinks'], false) ;
    }

    /**
     * @param \databox $databox
     * @param FetcherDelegateInterface $fetcherDelegate
     * @return Fetcher
     */
    public function createFetcher(\databox $databox, FetcherDelegateInterface $fetcherDelegate = null)
    {
        $connection = $databox->get_connection();

        $candidateTerms = new CandidateTerms($databox);
        $fetcher = new Fetcher(
            $databox,
            $this->options,
            [
                new CoreHydrator($databox->get_sbas_id(), $databox->get_viewname(), $this->recordHelper),
                new TitleHydrator($connection, $this->recordHelper),
                new MetadataHydrator($this->conf, $connection, $this->getStructure(), $this->recordHelper),
                new FlagHydrator($this->getStructure(), $databox),
                new ThesaurusHydrator($this->getStructure(), $this->getThesaurus(), $candidateTerms),
                new SubDefinitionHydrator($this->app, $databox, $this->populatePermalinks)
            ],
            $fetcherDelegate
        );

        $fetcher->setBatchSize(200);
        $fetcher->onDrain(function() use ($candidateTerms) {
            $candidateTerms->save();
        });

        return $fetcher;
    }

    /**
     * @return Structure
     */
    private function getStructure()
    {
        return $this->app[$this->structureKey];
    }

    /**
     * @return Thesaurus
     */
    private function getThesaurus()
    {
        return $this->app[$this->thesaurusKey];
    }
}
