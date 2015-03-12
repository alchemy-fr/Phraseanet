<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer;

use Alchemy\Phrasea\SearchEngine\Elastic\ElasticSearchEngine;
use Alchemy\Phrasea\SearchEngine\Elastic\Exception\Exception;
use Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\BulkOperation;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Delegate\FetcherDelegate;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Delegate\FetcherDelegateInterface;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Delegate\RecordListFetcherDelegate;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Delegate\ScheduledFetcherDelegate;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Fetcher;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\CoreHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\MetadataHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\SubDefinitionHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\ThesaurusHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\TitleHydrator;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\RecordQueuer;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\StringUtils;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\CandidateTerms;
use databox;
use Iterator;
use media_subdef;

class RecordIndexer
{
    const TYPE_NAME = 'record';

    private $helper;

    private $thesaurus;

    /**
     * @var \appbox
     */
    private $appbox;

    /**
     * @var \Alchemy\Phrasea\SearchEngine\Elastic\ElasticSearchEngine
     */
    private $elasticSearchEngine;

    /**
     * @var array
     */
    private $locales;

    public function __construct(RecordHelper $helper, Thesaurus $thesaurus, ElasticSearchEngine $elasticSearchEngine, \appbox $appbox, array $locales)
    {
        $this->helper = $helper;
        $this->thesaurus = $thesaurus;
        $this->appbox = $appbox;
        $this->elasticSearchEngine = $elasticSearchEngine;
        $this->locales = $locales;
    }

    public function populateIndex(BulkOperation $bulk, array $databoxes)
    {
        foreach ($databoxes as $databox) {
            $fetcher = $this->createFetcherForDatabox($databox);
            $this->indexFromFetcher($bulk, $fetcher);
        }
    }

    public function indexScheduled(BulkOperation $bulk)
    {
        foreach ($this->appbox->get_databoxes() as $databox) {
            $this->indexScheduledInDatabox($bulk, $databox);
        }
    }

    private function indexScheduledInDatabox(BulkOperation $bulk, databox $databox)
    {
        // Make fetcher
        $delegate = new ScheduledFetcherDelegate();
        $fetcher = $this->createFetcherForDatabox($databox, $delegate);
        // Keep track of fetched records, flag them as "indexing"
        $fetched = array();
        $fetcher->setPostFetch(function(array $records) use ($databox, &$fetched) {
            // TODO Do not keep all indexed records in memory...
            $fetched += $records;
            RecordQueuer::didStartIndexingRecords($records, $databox);
        });
        // Perform indexing
        $this->indexFromFetcher($bulk, $fetcher);
        // Commit and remove "indexing" flag
        $bulk->flush();
        RecordQueuer::didFinishIndexingRecords($fetched, $databox);
    }

    public function index(BulkOperation $bulk, Iterator $records)
    {
        foreach ($this->createFetchersForRecords($records) as $fetcher) {
            $this->indexFromFetcher($bulk, $fetcher);
        }
    }

    public function delete(BulkOperation $bulk, Iterator $records)
    {
        foreach ($records as $record) {
            $params = array();
            $params['id'] = $record->getId();
            $params['type'] = self::TYPE_NAME;
            $bulk->delete($params);
        }
    }

    private function createFetchersForRecords(Iterator $records)
    {
        $fetchers = array();
        foreach ($this->groupRecordsByDatabox($records) as $group) {
            $databox = $group['databox'];
            $connection = $databox->get_connection();
            $delegate = new RecordListFetcherDelegate($group['records']);
            $fetchers[] = $this->createFetcherForDatabox($databox, $delegate);
        }

        return $fetchers;
    }

    private function createFetcherForDatabox(databox $databox, FetcherDelegateInterface $delegate = null)
    {
        $connection = $databox->get_connection();
        $candidateTerms = new CandidateTerms($databox);
        $fetcher = new Fetcher($connection, array(
            new CoreHydrator($databox->get_sbas_id(), $this->helper),
            new TitleHydrator($connection),
            new MetadataHydrator($connection),
            new ThesaurusHydrator($this->thesaurus, $candidateTerms, $this->helper),
            new SubDefinitionHydrator($connection)
        ), $delegate);
        $fetcher->setBatchSize(200);
        $fetcher->onDrain(function() use ($candidateTerms) {
            $candidateTerms->save();
        });

        return $fetcher;
    }

    private function groupRecordsByDatabox(Iterator $records)
    {
        $databoxes = array();
        foreach ($records as $record) {
            $databox = $record->get_databox();
            $hash = spl_object_hash($databox);
            $databoxes[$hash]['databox'] = $databox;
            $databoxes[$hash]['records'][] = $record;
        }

        return array_values($databoxes);
    }

    private function indexFromFetcher(BulkOperation $bulk, Fetcher $fetcher)
    {
        while ($record = $fetcher->fetch()) {
            $params = array();
            $params['id'] = $record['id'];
            $params['type'] = self::TYPE_NAME;
            $params['body'] = $this->transform($record);
            $bulk->index($params);
        }
    }

    public function getMapping()
    {
        $mapping = new Mapping();
        $mapping
            // Identifiers
            ->add('record_id', 'integer')  // Compound primary key
            ->add('databox_id', 'integer') // Compound primary key
            ->add('base_id', 'integer') // Unique collection ID
            ->add('collection_id', 'integer')->notIndexed() // Useless collection ID (local to databox)
            ->add('collection_name', 'string')->notAnalyzed() // Collection name (still indexed for facets)
            ->add('uuid', 'string')->notIndexed()
            ->add('sha256', 'string')->notIndexed()
            // Mandatory metadata
            ->add('original_name', 'string')->notIndexed()
            ->add('mime', 'string')->notIndexed()
            ->add('type', 'string')->notAnalyzed()
            ->add('record_type', 'string')->notAnalyzed() // record or story
            // Dates
            ->add('created_on', 'date')->format(Mapping::DATE_FORMAT_MYSQL)
            ->add('updated_on', 'date')->format(Mapping::DATE_FORMAT_MYSQL)
            // EXIF
            ->add('exif', $this->getExifMapping())
            // Status
            ->add('flags', $this->getFlagsMapping())
            // Keep some fields arround for display purpose
            ->add('subdefs', Mapping::disabledMapping())
            ->add('title', Mapping::disabledMapping())
        ;

        // Caption mapping
        $captionMapping = new Mapping();
        $mapping->add('caption', $captionMapping);
        $privateCaptionMapping = new Mapping();
        $mapping->add('private_caption', $privateCaptionMapping);
        // Inferred thesaurus concepts
        $conceptPathMapping = new Mapping();
        $mapping->add('concept_path', $conceptPathMapping);

        foreach ($this->helper->getFieldsStructure() as $name => $params) {
            $m = $params['private'] ? $privateCaptionMapping : $captionMapping;
            $m->add($name, $params['type']);

            if ($params['type'] === Mapping::TYPE_DATE) {
                $m->format(Mapping::DATE_FORMAT_CAPTION);
            }

            if ($params['type'] === Mapping::TYPE_STRING) {
                if (!$params['searchable'] && !$params['to_aggregate']) {
                    $m->notIndexed();
                } elseif (!$params['searchable'] && $params['to_aggregate']) {
                    $m->notAnalyzed();
                    $m->addRawVersion();
                } else {
                    $m->addRawVersion();
                    $m->addAnalyzedVersion($this->locales);
                }
            }

            if ($params['thesaurus_concept_inference']) {
                $conceptPathMapping
                    ->add($name, 'string')
                    ->analyzer('thesaurus_path', 'indexing')
                    ->analyzer('keyword', 'searching')
                    ->addRawVersion()
                ;
            }
        }

        return $mapping->export();
    }

    // @todo Add call to addAnalyzedVersion ?
    private function getExifMapping()
    {
        $mapping = new Mapping();
        $mapping
            ->add(media_subdef::TC_DATA_WIDTH, 'integer')
            ->add(media_subdef::TC_DATA_HEIGHT, 'integer')
            ->add(media_subdef::TC_DATA_COLORSPACE, 'string')->notAnalyzed()
            ->add(media_subdef::TC_DATA_CHANNELS, 'integer')
            ->add(media_subdef::TC_DATA_ORIENTATION, 'integer')
            ->add(media_subdef::TC_DATA_COLORDEPTH, 'integer')
            ->add(media_subdef::TC_DATA_DURATION, 'float')
            ->add(media_subdef::TC_DATA_AUDIOCODEC, 'string')->notAnalyzed()
            ->add(media_subdef::TC_DATA_AUDIOSAMPLERATE, 'integer')
            ->add(media_subdef::TC_DATA_VIDEOCODEC, 'string')->notAnalyzed()
            ->add(media_subdef::TC_DATA_FRAMERATE, 'float')
            ->add(media_subdef::TC_DATA_MIMETYPE, 'string')->notAnalyzed()
            ->add(media_subdef::TC_DATA_FILESIZE, 'long')
            // TODO use geo point type for lat/long
            ->add(media_subdef::TC_DATA_LONGITUDE, 'float')
            ->add(media_subdef::TC_DATA_LATITUDE, 'float')
            ->add(media_subdef::TC_DATA_FOCALLENGTH, 'float')
            ->add(media_subdef::TC_DATA_CAMERAMODEL, 'string')
            ->add(media_subdef::TC_DATA_FLASHFIRED, 'boolean')
            ->add(media_subdef::TC_DATA_APERTURE, 'float')
            ->add(media_subdef::TC_DATA_SHUTTERSPEED, 'float')
            ->add(media_subdef::TC_DATA_HYPERFOCALDISTANCE, 'float')
            ->add(media_subdef::TC_DATA_ISO, 'integer')
            ->add(media_subdef::TC_DATA_LIGHTVALUE, 'float')
        ;

        return $mapping;
    }

    private function getFlagsMapping()
    {
        $mapping = new Mapping();

        foreach ($this->appbox->get_databoxes() as $databox) {
            foreach ($databox->getStatusStructure() as $bit => $status) {
                $key = RecordHelper::normalizeFlagKey($status['labelon']);
                // We only add to mapping new statuses
                if (!$mapping->has($key)) {
                    $mapping->add($key, 'boolean');
                }
            }
        }

        return $mapping;
    }

    /**
     * Inspired by ESRecordSerializer
     *
     * @todo complete, with all the other transformations
     * @todo convert this function in a HydratorInterface and inject into fetcher
     * @param $record
     */
    private function transform($record)
    {
        $dateFields = $this->elasticSearchEngine->getAvailableDateFields();

        $databox = $this->appbox->get_databox($record['databox_id']);

        foreach ($databox->getStatusStructure() as $bit => $status) {
            $key = RecordHelper::normalizeFlagKey($status['labelon']);

            $record['flags'][$key] = \databox_status::bitIsSet($record['flags_bitfield'], $bit);
        }

        foreach ($dateFields as $field) {
            if (!isset($record['caption'][$field])) {
                continue;
            }

            try {
                $date = new \DateTime($record['caption'][$field]);
                $record['caption'][$field] = $date->format(Mapping::DATE_FORMAT_CAPTION_PHP);
            } catch (\Exception $e) {
                $record['caption'][$field] = null;
                continue;
            }
        }

        return $record;
    }
}
