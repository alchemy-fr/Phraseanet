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

use Alchemy\Phrasea\SearchEngine\Elastic\BulkOperation;
use Alchemy\Phrasea\SearchEngine\Elastic\Exception\Exception;
use Alchemy\Phrasea\SearchEngine\Elastic\Exception\MergeException;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordFetcher;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Elasticsearch\Client;
use media_subdef;

class RecordIndexer
{
    const TYPE_NAME = 'record';

    /**
     * @var \Elasticsearch\Client
     */
    private $client;

    /**
     * @var array
     */
    private $options;

    /**
     * @var \appbox
     */
    private $appbox;

    public function __construct(Client $client, array $options, \appbox $appbox)
    {
        $this->client = $client;
        $this->options = $options;
        $this->appbox = $appbox;
    }

    public function populateIndex()
    {
        // Prepare the bulk operation
        $bulk = new BulkOperation($this->client);
        $bulk->setDefaultIndex($this->options['index']);
        $bulk->setDefaultType(self::TYPE_NAME);
        $bulk->setAutoFlushLimit(1000);

        // Helper to fetch record related data
        $recordHelper = new RecordHelper($this->appbox);

        foreach ($this->appbox->get_databoxes() as $databox) {

            // TODO Pass a BulkOperation object to TermIndexer to muliplex
            // indexing queries between types
            // TODO Create object to query thesaurus for term paths/synonyms
            // TODO Extract record indexing logic in a RecordIndexer class
            $fetcher = new RecordFetcher($databox, $recordHelper);
            $fetcher->setBatchSize(200);
            while ($record = $fetcher->fetch()) {
                $params = array();
                $params['id'] = $record['id'];
                $params['body'] = $record;
                $bulk->index($params);
            }
        }

        $bulk->flush();
    }

    public function getMapping()
    {
        $mapping = new Mapping();
        $mapping
            // Identifiers
            ->add('record_id', 'integer')  // Compound primary key
            ->add('databox_id', 'integer') // Compound primary key
            ->add('base_id', 'integer') // Unique collection ID
            ->add('collection_id', 'integer') // Useless collection ID (local to databox)
            ->add('uuid', 'string')->notAnalyzed()
            ->add('sha256', 'string')->notAnalyzed()
            // Mandatory metadata
            ->add('original_name', 'string')->notAnalyzed()
            ->add('mime', 'string')->notAnalyzed()
            ->add('type', 'string')->notAnalyzed()
            // Dates
            ->add('created_on', 'date')->format(Mapping::DATE_FORMAT_MYSQL)
            ->add('updated_on', 'date')->format(Mapping::DATE_FORMAT_MYSQL)
        ;

        // Caption mapping
        $captionMapping = new Mapping();
        $mapping->add('caption', $captionMapping);
        $privateCaptionMapping = new Mapping();
        $mapping->add('private_caption', $privateCaptionMapping);
        foreach ($this->getRecordFieldsStructure() as $name => $params) {
            $m = $params['private'] ? $privateCaptionMapping : $captionMapping;
            $m->add($name, $params['type']);

            if ($params['type'] === Mapping::TYPE_DATE) {
                $m->format(Mapping::DATE_FORMAT_CAPTION);
            }

            if (!$params['indexable'] && !$params['to_aggregate']) {
                $m->notIndexed();
            } elseif (!$params['indexable'] && $params['to_aggregate']) {
                $m->notAnalyzed();
                $m->addRawVersion();
            } else {
                $m->addRawVersion();
                $m->addAnalyzedVersion(['fr', 'de']); // @todo Dynamic list from the box
            }
        }

        // EXIF
        $mapping->add('exif', $this->getRecordExifMapping());

        // Status
        $mapping->add('flags', $this->getRecordFlagsMapping());

        return $mapping->export();
    }


    private function getRecordFieldsStructure()
    {
        $fields = array();

        foreach ($this->appbox->get_databoxes() as $databox) {
            printf("Databox %d\n", $databox->get_sbas_id());
            foreach ($databox->get_meta_structure() as $fieldStructure) {
                $field = array();
                // Field type
                switch ($fieldStructure->get_type()) {
                    case \databox_field::TYPE_DATE:
                        $field['type'] = 'date';
                        break;
                    case \databox_field::TYPE_NUMBER:
                        $field['type'] = 'string'; // TODO integer, float, double ?
                        break;
                    case \databox_field::TYPE_STRING:
                    case \databox_field::TYPE_TEXT:
                        $field['type'] = 'string';
                        break;
                    default:
                        throw new Exception(sprintf('Invalid field type "%s", expected "date", "number" or "string".', $fieldStructure->get_type()));
                        break;
                }

                // Business rules
                $field['private'] = $fieldStructure->isBusiness();
                $field['indexable'] = $fieldStructure->is_indexable();
                $field['to_aggregate'] = false; // @todo, dev in progress

                $name = $fieldStructure->get_name();

                printf("Field \"%s\" <%s> (private: %b)\n", $name, $field['type'], $field['private']);

                // Since mapping is merged between databoxes, two fields may
                // have conflicting names. Indexing is the same for a given
                // type so we reject only those with different types.
                if (isset($fields[$name])) {
                    if ($fields[$name]['type'] !== $field['type']) {
                        throw new MergeException(sprintf("Field %s can't be merged, incompatible types (%s vs %s)", $name, $fields[$name]['type'], $field['type']));
                    }

                    if ($fields[$name]['indexable'] !== $field['indexable']) {
                        throw new MergeException(sprintf("Field %s can't be merged, incompatible indexable state", $name));
                    }

                    if ($fields[$name]['to_aggregate'] !== $field['to_aggregate']) {
                        throw new MergeException(sprintf("Field %s can't be merged, incompatible to_aggregate state", $name));
                    }
                    // TODO other structure incompatibilities

                    printf("Merged with previous \"%s\" field\n", $name);
                }

                $fields[$name] = $field;
            }
        }

        return $fields;
    }

    // @todo Add call to addAnalyzedVersion ?
    private function getRecordExifMapping()
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

    private function getRecordFlagsMapping()
    {
        $mapping = new Mapping();
        $seen = array();

        foreach ($this->appbox->get_databoxes() as $databox) {
            foreach ($databox->get_statusbits() as $bit => $status) {
                $key = self::normalizeFlagKey($status['labelon']);
                // We only add to mapping new statuses
                if (!in_array($key, $seen)) {
                    $mapping->add($key, 'boolean');
                    $seen[] = $key;
                }
            }
        }

        return $mapping;
    }

    private static function normalizeFlagKey($key)
    {
        // Replace non letter or digits by _
        $key = preg_replace('/[^\\pL\d]+/u', '_', $key);
        $key = trim($key, '_');

        // Transliterate
        if (function_exists('iconv')) {
            $key = iconv('UTF-8', 'ASCII//TRANSLIT', $key);
        }

        // Remove non wording characters
        $key = preg_replace('/[^-\w]+/', '', $key);
        $key = strtolower($key);

        return $key;
    }
}
