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
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\CandidateTerms;
use databox;
use Iterator;
use media_subdef;
use Psr\Log\LoggerInterface;

class RecordIndexer
{
    const TYPE_NAME = 'record';

    private $structure;

    private $helper;

    private $thesaurus;

    /**
     * @var \appbox
     */
    private $appbox;

    /**
     * @var array
     */
    private $locales;

    private $logger;

    public function __construct(Structure $structure, RecordHelper $helper, Thesaurus $thesaurus, \appbox $appbox, array $locales, LoggerInterface $logger)
    {
        $this->structure = $structure;
        $this->helper = $helper;
        $this->thesaurus = $thesaurus;
        $this->appbox = $appbox;
        $this->locales = $locales;
        $this->logger = $logger;
    }

    public function populateIndex(BulkOperation $bulk, array $databoxes)
    {
        foreach ($databoxes as $databox) {
            $this->logger->info(sprintf('Indexing database %s...', $databox->get_viewname()));
            $fetcher = $this->createFetcherForDatabox($databox);
            $this->indexFromFetcher($bulk, $fetcher);
            $this->logger->info(sprintf('Finished indexing %s', $databox->get_viewname()));
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
            new CoreHydrator($databox->get_sbas_id(), $databox->get_viewname(), $this->helper),
            new TitleHydrator($connection),
            new MetadataHydrator($connection, $this->structure, $this->helper),
            new ThesaurusHydrator($this->structure, $this->thesaurus, $candidateTerms),
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
            unset($record['id']);
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
            ->add('databox_name', 'string')->notAnalyzed() // database name (still indexed for facets)
            ->add('base_id', 'integer') // Unique collection ID
            ->add('collection_id', 'integer')->notIndexed() // Useless collection ID (local to databox)
            ->add('collection_name', 'string')->notAnalyzed() // Collection name (still indexed for facets)
            ->add('uuid', 'string')->notIndexed()
            ->add('sha256', 'string')->notIndexed()
            // Mandatory metadata
            ->add('original_name', 'string')->notIndexed()
            ->add('mime', 'string')->notAnalyzed() // Indexed for Kibana only
            ->add('type', 'string')->notAnalyzed()
            ->add('record_type', 'string')->notAnalyzed() // record or story
            // Dates
            ->add('created_on', 'date')->format(Mapping::DATE_FORMAT_MYSQL)
            ->add('updated_on', 'date')->format(Mapping::DATE_FORMAT_MYSQL)
            // Thesaurus
            ->add('concept_path', $this->getThesaurusPathMapping())
            // EXIF
            ->add('exif', $this->getExifMapping())
            // Status
            ->add('flags', $this->getFlagsMapping())
            ->add('flags_bitfield', 'integer')->notIndexed()
            // Keep some fields arround for display purpose
            ->add('subdefs', Mapping::disabledMapping())
            ->add('title', Mapping::disabledMapping())
        ;

        // Caption mapping
        $this->buildCaptionMapping($this->structure->getUnrestrictedFields(), $mapping, 'caption');
        $this->buildCaptionMapping($this->structure->getPrivateFields(), $mapping, 'private_caption');

        return $mapping->export();
    }

    private function buildCaptionMapping(array $fields, Mapping $root, $section)
    {
        $mapping = new Mapping();
        foreach ($fields as $field) {
            $this->addFieldToMapping($field, $mapping);
        }
        $root->add($section, $mapping);
        $root
            ->add(sprintf('%s_all', $section), 'string')
            ->addLocalizedSubfields($this->locales)
            ->addRawVersion()
        ;
    }

    private function addFieldToMapping(Field $field, Mapping $mapping)
    {
        $type = $field->getType();
        $mapping->add($field->getName(), $type);

        if ($type === Mapping::TYPE_DATE) {
            $mapping->format(Mapping::DATE_FORMAT_CAPTION);
        }

        if ($type === Mapping::TYPE_STRING) {
            $searchable = $field->isSearchable();
            $facet = $field->isFacet();
            if (!$searchable && !$facet) {
                $mapping->notIndexed();
            } elseif (!$searchable && $facet) {
                $mapping->notAnalyzed();
                $mapping->addRawVersion();
                $mapping->enableTermVectors(true);
            } else {
                $mapping->addRawVersion();
                $mapping->addAnalyzedVersion($this->locales);
                $mapping->enableTermVectors(true);
            }
        }
    }

    private function getThesaurusPathMapping()
    {
        $mapping = new Mapping();
        foreach ($this->structure->getThesaurusEnabledFields() as $name => $_) {
            $mapping
                ->add($name, 'string')
                ->analyzer('thesaurus_path', 'indexing')
                ->analyzer('keyword', 'searching')
                ->addRawVersion()
            ;
        }

        return $mapping;
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
            ->add(media_subdef::TC_DATA_AUDIOSAMPLERATE, 'float')
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
        $databox = $this->appbox->get_databox($record['databox_id']);

        foreach ($databox->getStatusStructure() as $bit => $status) {
            $key = RecordHelper::normalizeFlagKey($status['labelon']);

            $record['flags'][$key] = \databox_status::bitIsSet($record['flags_bitfield'], $bit);
        }

        return $record;
    }
}
