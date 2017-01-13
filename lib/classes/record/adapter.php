<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Cache\Exception;
use Alchemy\Phrasea\Core\Event\Record\CollectionChangedEvent;
use Alchemy\Phrasea\Core\Event\Record\CreatedEvent;
use Alchemy\Phrasea\Core\Event\Record\DeletedEvent;
use Alchemy\Phrasea\Core\Event\Record\MetadataChangedEvent;
use Alchemy\Phrasea\Core\Event\Record\OriginalNameChangedEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Core\Event\Record\StatusChangedEvent;
use Alchemy\Phrasea\Core\PhraseaTokens;
use Alchemy\Phrasea\Databox\Subdef\MediaSubdefRepository;
use Alchemy\Phrasea\Filesystem\FilesystemService;
use Alchemy\Phrasea\Media\TechnicalData;
use Alchemy\Phrasea\Media\TechnicalDataSet;
use Alchemy\Phrasea\Metadata\Tag\TfBasename;
use Alchemy\Phrasea\Metadata\Tag\TfFilename;
use Alchemy\Phrasea\Model\Entities\OrderElement;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\RecordInterface;
use Alchemy\Phrasea\Model\Serializer\CaptionSerializer;
use Alchemy\Phrasea\Record\RecordReference;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use MediaVorus\MediaVorus;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\File as SymfoFile;

class record_adapter implements RecordInterface, cache_cacheableInterface
{
    const CACHE_ORIGINAL_NAME = 'originalname';
    const CACHE_TECHNICAL_DATA = 'technical_data';
    const CACHE_MIME = 'mime';
    const CACHE_TITLE = 'title';
    const CACHE_SHA256 = 'sha256';
    const CACHE_SUBDEFS = 'subdefs';
    const CACHE_GROUPING = 'grouping';

    /**
     * @param Application $app
     * @return FilesystemService
     */
    private static function getFilesystem(Application $app)
    {
        return $app['phraseanet.filesystem'];
    }

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var RecordReference;
     */
    private $reference;

    /**
     * @var Connection|null
     */
    private $connection;

    private $base_id;
    private $collection_id;
    private $mime;
    private $number;

    /**
     * @var string
     */
    private $status;
    private $subdefs;
    private $type;
    private $sha256;
    private $isStory;
    private $duration;
    /** @var DateTime */
    private $created;
    /** @var string */
    private $original_name;
    /** @var TechnicalDataSet|null */
    private $technical_data;
    /** @var string */
    private $uuid;
    /** @var DateTime */
    private $updated;

    /** @var bool|null|integer  */
    private $width;
    /** @var bool|null|integer  */
    private $height;
    /** @var bool|null|integer  */
    private $size;

    /**
     * @param Application $app
     * @param integer     $sbas_id
     * @param integer     $record_id
     * @param integer     $number
     * @param bool        $load
     */
    public function __construct(Application $app, $sbas_id, $record_id, $number = null, $load = true)
    {
        $this->app = $app;
        $this->reference = RecordReference::createFromDataboxIdAndRecordId($sbas_id, $record_id);
        $this->number = (int)$number;

        $this->width = $this->height = $this->size = false; // means unknown for now

        if ($load) {
            $this->load();
        }
    }

    protected function load()
    {
        if (null === $record = $this->getDatabox()->getRecordRepository()->find($this->getRecordId())) {
            throw new Exception_Record_AdapterNotFound('Record ' . $this->getRecordId() . ' on database ' . $this->getDataboxId() . ' not found ');
        }

        $this->mirror($record);
    }

    /**
     * @param record_adapter $record
     */
    private function mirror(record_adapter $record)
    {
        $this->mime = $record->getMimeType();
        $this->sha256 = $record->getSha256();
        $this->original_name = $record->getOriginalName();
        $this->type = $record->getType();
        $this->isStory = $record->isStory();
        $this->uuid = $record->getUuid();
        $this->updated = $record->getUpdated();
        $this->created = $record->getCreated();
        $this->base_id = $record->getBaseId();
        $this->collection_id = $record->getCollectionId();
        $this->status = $record->getStatus();
    }

    /**
     * @return DateTime
     * @deprecated {@link self::getCreated}
     */
    public function get_creation_date()
    {
        return $this->getCreated();
    }

    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return string
     * @deprecated use {@link getUuid} instead.
     */
    public function get_uuid()
    {
        return $this->getUuid();
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function getWidth()
    {
        $this->getDocInfos();

        return $this->width;
    }

    public function getHeight()
    {
        $this->getDocInfos();

        return $this->height;
    }

    public function getSize()
    {
        $this->getDocInfos();

        return $this->size;
    }

    private function getDocInfos()
    {
        if($this->width === false) {       // strict false means unknown
            try {
                $doc = $this->get_subdef('document');
                $this->width = $doc->get_width();
                $this->height = $doc->get_height();
                $this->size = $doc->get_size();
            } catch (\Exception $e) {
                // failing once is failing ever
                $this->width = $this->height = $this->size = null;
            }
        }
    }

    /**
     * @return DateTime
     * @deprecated use {@link self::getUpdated} instead
     */
    public function get_modification_date()
    {
        return $this->getUpdated();
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set a relative number (order) for the current in its set
     *
     * @param  int $number
     * @return $this
     */
    public function setNumber($number)
    {
        $this->number = (int)$number;

        return $this;
    }

    /**
     * @param  string $type
     * @return $this
     * @throws Exception
     * @throws \Doctrine\DBAL\DBALException
     * @deprecated use {@link self::setType} instead.
     */
    public function set_type($type)
    {
        return $this->setType($type);
    }

    /**
     * @param string $type
     * @return $this
     * @throws Exception
     * @throws \Doctrine\DBAL\DBALException
     */
    public function setType($type)
    {
        $type = strtolower($type);

        $old_type = $this->getType();

        if (!in_array($type, ['document', 'audio', 'video', 'image', 'flash', 'map'])) {
            throw new Exception('unrecognized document type');
        }

        $sql = 'UPDATE record SET moddate = NOW(), type = :type WHERE record_id = :record_id';
        $this->getDataboxConnection()->executeUpdate($sql, ['type' => $type, 'record_id' => $this->getRecordId()]);

        if ($old_type !== $type) {
            $this->rebuild_subdefs();
        }

        $this->type = $type;
        $this->delete_data_from_cache();

        return $this;
    }

    public function touch()
    {
        $this->getDataboxConnection()->executeUpdate(
            'UPDATE record SET moddate = NOW() WHERE record_id = :record_id',
            ['record_id' => $this->getRecordId()]
        );

        $this->delete_data_from_cache();
    }

    /**
     * Returns the type of the document
     *
     * @return string
     * @deprecated use {@link self::getType} instead.
     */
    public function get_type()
    {
        return $this->getType();
    }

    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $mime
     * @return $this
     * @deprecated use {@link self::setMimeType} instead.
     */
    public function set_mime($mime)
    {
        return $this->setMimeType($mime);
    }

    public function setMimeType($mime)
    {
        // see http://lists.w3.org/Archives/Public/xml-dist-app/2003Jul/0064.html
        if (!preg_match("/^[a-zA-Z0-9!#$%^&\\*_\\-\\+{}\\|'.`~]+\\/[a-zA-Z0-9!#$%^&\\*_\\-\\+{}\\|'.`~]+$/", $mime)) {
            throw new \Exception(sprintf('Unrecognized mime type %s', $mime));
        }

        if ($this->getDataboxConnection()->executeUpdate(
            'UPDATE record SET moddate = NOW(), mime = :mime WHERE record_id = :record_id',
            array(':mime' => $mime, ':record_id' => $this->getRecordId())
        )) {

            $this->rebuild_subdefs();
            $this->delete_data_from_cache();
        }

        $this->mime = $mime;

        return $this;
    }

    /**
     * @return string
     * @deprecated use {@link self::getMimeType} instead.
     */
    public function get_mime()
    {
        return $this->getMimeType();
    }

    public function getMimeType()
    {
        return $this->mime;
    }

    /**
     * Return true if the record is a grouping
     *
     * @return bool
     * @deprecated use {@link self::isStory} instead
     */
    public function is_grouping()
    {
        return $this->isStory();
    }

    public function isStory()
    {
        return $this->isStory;
    }

    /**
     * Return base_id of the record
     *
     * @return int
     * @deprecated use {@link self::getBaseId} instead.
     */
    public function get_base_id()
    {
        return $this->getBaseId();
    }

    public function getBaseId()
    {
        return $this->base_id;
    }

    /**
     * Return collection_id of the record
     *
     * @return int
     * @deprecated use {@link self::getCollectionId} instead.
     */
    public function get_collection_id()
    {
        return $this->getCollectionId();
    }

    public function getCollectionId()
    {
        return $this->collection_id;
    }

    /**
     * Return record collection
     *
     * @return \collection
     * @deprecated use {@link self::getCollection} instead.
     */
    public function get_collection()
    {
        return $this->getCollection();
    }

    /**
     * Return collection to which the record belongs to.
     *
     * @return \collection
     */
    public function getCollection()
    {
        return \collection::getByCollectionId($this->app, $this->getDatabox(), $this->collection_id);
    }

    /**
     * @return string  the name of the collection to which the record belongs to.
     */
    public function getCollectionName()
    {
        return $this->getCollection()->get_name();
    }

    /**
     * Returns record_id of the record
     *
     * @return int
     * @deprecated use {@link self::getRecordId} instead.
     */
    public function get_record_id()
    {
        return $this->getRecordId();
    }

    public function getRecordId()
    {
        return $this->reference->getRecordId();
    }

    /**
     * @return databox
     * @deprecated use {@link self::getDatabox} instead.
     */
    public function get_databox()
    {
        return $this->getDatabox();
    }

    /**
     * @return databox
     */
    public function getDatabox()
    {
        return $this->app->findDataboxById($this->reference->getDataboxId());
    }

    /**
     * @return media_subdef
     */
    public function get_thumbnail()
    {
        return $this->get_subdef('thumbnail');
    }

    /**
     * @param string|string[] $devices
     * @param string|string[] $mimes
     * @return media_subdef[]
     */
    public function get_embedable_medias($devices = null, $mimes = null)
    {
        return $this->getSubdfefByDeviceAndMime($devices, $mimes);
    }

    /**
     * get duration formatted as xx:xx:xx
     *
     * @return string
     */
    public function get_formated_duration()
    {
        return p4string::format_seconds($this->get_duration());
    }

    /**
     * Returns duration in seconds
     *
     * @return int
     */
    public function get_duration()
    {
        if (!$this->duration) {
            $duration = $this->get_technical_infos(media_subdef::TC_DATA_DURATION);
            $this->duration = $duration ? round($duration->getValue()) : false;
        }

        return $this->duration;
    }

    /**
     *
     * @param  collection     $collection
     * @param  appbox         $appbox
     * @return record_adapter
     */
    public function move_to_collection(collection $collection, appbox $appbox)
    {
        if ($this->getCollection()->get_base_id() === $collection->get_base_id()) {
            return $this;
        }

        $sql = "UPDATE record SET moddate = NOW(), coll_id = :coll_id WHERE record_id =:record_id";

        $params = [
            ':coll_id'   => $collection->get_coll_id(),
            ':record_id' => $this->getRecordId(),
        ];

        $stmt = $this->getDataboxConnection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->base_id = $collection->get_base_id();

        $this->app['phraseanet.logger']($this->getDatabox())
            ->log($this, Session_Logger::EVENT_MOVE, $collection->get_coll_id(), '');

        $this->delete_data_from_cache();

        $this->dispatch(RecordEvents::COLLECTION_CHANGED, new CollectionChangedEvent($this));

        return $this;
    }

    /**
     * @return null|media_subdef
     */
    public function get_rollover_thumbnail()
    {
        if ($this->getType() != 'video') {
            return null;
        }

        try {
            return $this->get_subdef('thumbnailGIF');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return string
     * @deprecated use {self::getSha256} instead.
     */
    public function get_sha256()
    {
        return $this->getSha256();
    }

    public function getSha256()
    {
        return $this->sha256;
    }

    public function has_subdef($name)
    {
        return in_array($name, $this->get_available_subdefs(), false);
    }

    /**
     * @param string $name
     * @return media_subdef
     */
    public function get_subdef($name)
    {
        $name = strtolower($name);

        if (isset($this->subdefs[$name])) {
            return $this->subdefs[$name];
        }

        if (!in_array($name, $this->get_available_subdefs(), false)) {
            throw new Exception_Media_SubdefNotFound(sprintf("subdef `%s` not found", $name));
        }

        $subdefs = $this->getMediaSubdefRepository()->findOneByRecordIdAndName($this->getRecordId(), $name);

        return $subdefs ?: new media_subdef($this->app, $this, $name, ($name !== 'document'));
    }

    /**
     * Returns an array of subdef matching
     *
     * @param  string|string[] $devices the matching device (see databox_subdef::DEVICE_*)
     * @param  string|string[] $mimes   the matching mime types
     * @return media_subdef[]
     */
    public function getSubdfefByDeviceAndMime($devices = null, $mimes = null)
    {
        $subdefNames = $subdefs = [];

        $availableSubdefs = $this->get_subdefs();

        if (isset($availableSubdefs['document'])) {

            $mime_ok = !$mimes || in_array($availableSubdefs['document']->get_mime(), (array)$mimes, false);
            $devices_ok = !$devices || array_intersect($availableSubdefs['document']->getDevices(), (array)$devices);

            if ($mime_ok && $devices_ok) {
                $subdefs['document'] = $availableSubdefs['document'];
            }
        }

        $searchDevices = array_merge((array)$devices, (array)databox_subdef::DEVICE_ALL);

        $type = $this->isStory() ? 'image' : $this->getType();

        foreach ($this->getDatabox()->get_subdef_structure() as $group => $databoxSubdefs) {

            if ($type != $group) {
                continue;
            }

            foreach ($databoxSubdefs as $databoxSubdef) {

                if ($devices && !array_intersect($databoxSubdef->getDevices(), $searchDevices)) {
                    continue;
                }

                array_push($subdefNames, $databoxSubdef->get_name());
            }
        }

        foreach ($availableSubdefs as $subdef) {

            if (!in_array($subdef->get_name(), $subdefNames)) {
                continue;
            }

            if ($mimes && !in_array($subdef->get_mime(), (array)$mimes)) {
                continue;
            }

            $subdefs[$subdef->get_name()] = $subdef;
        }

        return $subdefs;
    }

    /**
     * @return media_subdef[]
     */
    public function get_subdefs()
    {
        if (null !== $this->subdefs) {
            return $this->subdefs;
        }

        $this->subdefs = [];

        foreach ($this->getMediaSubdefRepository()->findByRecordIdsAndNames([$this->getRecordId()]) as $subdef) {
            $this->subdefs[$subdef->get_name()] = $subdef;
        }

        foreach (['preview', 'thumbnail'] as $name) {
            if (!isset($this->subdefs[$name])) {
                $this->subdefs[$name] = new media_subdef($this->app, $this, $name, true, []);
            }
        }

        return $this->subdefs;
    }

    /**
     * @return string[]
     */
    protected function get_available_subdefs()
    {
        if (null !== $this->subdefs) {
            return array_keys($this->subdefs);
        }

        try {
            $data = $this->get_data_from_cache(self::CACHE_SUBDEFS);
        } catch (\Exception $e) {
            $data = false;
        }

        if (is_array($data)) {
            return $data;
        }

        $subdefs = array_keys($this->get_subdefs());

        $this->set_data_to_cache($subdefs, self::CACHE_SUBDEFS);

        return $subdefs;
    }

    /**
     * @return string
     */
    public function get_collection_logo()
    {
        return collection::getLogo($this->base_id, $this->app, true);
    }

    /**
     * Return a Technical data set or a specific technical data or false when not found
     *
     * @param  string $data
     * @return TechnicalDataSet|TechnicalData|false
     */
    public function get_technical_infos($data = '')
    {
        if (null === $this->technical_data) {
            $sets = $this->app['service.technical_data']->fetchRecordsTechnicalData([$this]);

            $this->setTechnicalDataSet(reset($sets));
        }

        if ($data) {
            if (isset($this->technical_data[$data])) {
                return $this->technical_data[$data];
            } else {
                return false;
            }
        }

        return $this->technical_data;
    }

    /**
     * @param TechnicalDataSet $dataSet
     * @internal
     */
    public function setTechnicalDataSet(TechnicalDataSet $dataSet)
    {
        $this->technical_data = $dataSet;
    }

    /**
     * @return caption_record
     */
    public function get_caption()
    {
        return new caption_record($this->app, $this);
    }

    public function getCaption(array $fields = null)
    {
        return $this->getCaptionFieldsMap($this->get_caption()->get_fields($fields, true));
    }

    /**
     * @param caption_field[] $fields
     * @return array
     */
    private function getCaptionFieldsMap(array $fields)
    {
        $collection = [];

        foreach ($fields as $field) {
            $values = array_map(function (caption_Field_Value $fieldValue) {
                return $fieldValue->getValue();
            }, $field->get_values());

            $collection[$field->get_name()] = $values;
        }

        return $collection;
    }

    /**
     * @param bool $removeExtension
     * @return string
     */
    public function get_original_name($removeExtension = null)
    {
        if ($removeExtension) {
            return pathinfo($this->original_name, PATHINFO_FILENAME);
        }

        return $this->original_name;
    }

    public function set_original_name($original_name)
    {
        $this->original_name = $original_name;

        foreach ($this->getDatabox()->get_meta_structure()->get_elements() as $data_field) {

            if ($data_field->get_tag() instanceof TfFilename) {
                $original_name = pathinfo($original_name, PATHINFO_FILENAME);
            } elseif (!$data_field->get_tag() instanceof TfBasename) {
                continue;
            }

            // Replacing original name in multi values is non sense
            if ($data_field->is_multi()) {
                continue;
            }

            try {
                $field = $this->get_caption()->get_field($data_field->get_name());
                $values = $field->get_values();
                $value = end($values);

                $this->set_metadatas([[
                    'meta_struct_id' => $field->get_meta_struct_id(),
                    'meta_id' => $value->getId(),
                    'value' => $original_name,
                ]], true);
            } catch (\Exception $e) {
                // Caption is not setup, ignore error
            }
        }

        $this->getDataboxConnection()->executeUpdate(
            'UPDATE record SET moddate = NOW(), originalname = :originalname WHERE record_id = :record_id',
            ['originalname' => $original_name, 'record_id' => $this->getRecordId()]
        );

        $this->delete_data_from_cache();

        $this->dispatch(RecordEvents::ORIGINAL_NAME_CHANGED, new OriginalNameChangedEvent($this));

        return $this;
    }

    /**
     * get the title (concat "thumbtitle" fields which match locale, with "-")
     * fallback to the filename, possibly with extension removed
     *
     * @param string $locale
     * @param $options[]
     *      'removeExtension' : boolean
     *
     * @return string
     */
    public function getTitle($locale = null, Array $options = [])
    {
        $removeExtension = !!igorw\get_in($options, ['removeExtension'], false);

        $cache = !$removeExtension;

        if ($cache) {
            try {
                return $this->get_data_from_cache(self::CACHE_TITLE);
            } catch (\Exception $e) {

            }
        }

        $title = '';

        $fields = $this->getDatabox()->get_meta_structure();

        $fields_to_retrieve = [];

        foreach ($fields as $field) {
            if (in_array($field->get_thumbtitle(), ['1', $locale])) {
                $fields_to_retrieve [] = $field->get_name();
            }
        }

        if (count($fields_to_retrieve) > 0) {
            $retrieved_fields = $this->get_caption()->get_highlight_fields($fields_to_retrieve);
            $titles = [];
            foreach ($retrieved_fields as $value) {
                foreach ($value['values'] as $v) {
                    $titles[] = $v['value'];
                }
            }
            $title = trim(implode(' - ', $titles));
        }

        if (trim($title) === '') {
            $title = trim($this->get_original_name($removeExtension));
        }

        $title = $title != "" ? $title : $this->app->trans('reponses::document sans titre');

        if ($cache) {
            $this->set_data_to_cache(self::CACHE_TITLE, $title);
        }

        return $title;
    }

    /**
     * @param Array $options
     *
     * @return string
     */
    public function get_title(Array $options = [])
    {
        return $this->getTitle($this->app['locale'], $options);
    }

    /**
     * @return media_subdef
     */
    public function get_preview()
    {
        return $this->get_subdef('preview');
    }

    /**
     * @return bool
     */
    public function has_preview()
    {
        try {
            return $this->get_subdef('preview')->is_physically_present();
        } catch (\Exception $e) {
            unset($e);
        }

        return false;
    }

    /**
     * @return string
     * @deprecated use {@link self::getId} instead.
     */
    public function get_serialize_key()
    {
        return $this->getDataboxId() . '_' . $this->getRecordId();
    }

    /**
     * @return int
     * @deprecated use {@link self::getDataboxId} instead
     */
    public function get_sbas_id()
    {
        return $this->getDatabox()->get_sbas_id();
    }

    /**
     * @param  DOMDocument    $dom_doc
     * @return record_adapter
     */
    protected function set_xml(DOMDocument $dom_doc)
    {
        $sql = 'UPDATE record SET moddate = NOW(), xml = :xml WHERE record_id= :record_id';
        $stmt = $this->getDataboxConnection()->prepare($sql);
        $stmt->execute(
            [
                ':xml'       => $dom_doc->saveXML(),
                ':record_id' => $this->getRecordId(),
            ]
        );
        $stmt->closeCursor();

        return $this;
    }

    /**
     * @todo move this function to caption_record
     * @param  Array  $params An array containing three keys : meta_struct_id (int) , meta_id (int or null) and value (Array)
     * @param databox $databox
     * @return record_adapter
     * @throws Exception
     * @throws Exception_InvalidArgument
     */
    protected function set_metadata(Array $params, databox $databox)
    {
        $mandatoryParams = ['meta_struct_id', 'meta_id', 'value'];

        foreach ($mandatoryParams as $param) {
            if (!array_key_exists($param, $params)) {
                throw new Exception_InvalidArgument(sprintf('Invalid metadata, missing key %s', $param));
            }
        }

        if (!is_scalar($params['value'])) {
            throw new Exception('Metadata value should be scalar');
        }

        $databox_field = $databox->get_meta_structure()->get_element($params['meta_struct_id']);

        $caption_field = new caption_field($this->app, $databox_field, $this);
        $caption_field->delete_data_from_cache();

        $vocab = $vocab_id = null;

        if (isset($params['vocabularyId']) && $databox_field->getVocabularyControl()) {
            try {
                $vocab = $databox_field->getVocabularyControl();
                $vocab_id = $params['vocabularyId'];
                $vocab->validate($vocab_id);
            } catch (\Exception $e) {
                $vocab = $vocab_id = null;
            }
        }

        if (trim($params['meta_id']) !== '') {
            $tmp_val = trim($params['value']);

            $caption_field_value = $caption_field->get_value($params['meta_id']);

            if ($tmp_val === '') {
                $caption_field_value->delete();
                unset($caption_field_value);
            } else {
                $caption_field_value->set_value($params['value']);
                if ($vocab && $vocab_id) {
                    $caption_field_value->setVocab($vocab, $vocab_id);
                }
            }
        } else {
            $caption_field_value = caption_Field_Value::create($this->app, $databox_field, $this, $params['value'], $vocab, $vocab_id);
        }

        return $this;
    }

    /**
     * @todo move this function to caption_record
     *
     * @param array   $metadatas
     * @param boolean $force_readonly
     *
     * @return record_adapter
     */
    public function set_metadatas(array $metadatas, $force_readonly = false)
    {
        $databox_descriptionStructure = $this->getDatabox()->get_meta_structure();

        foreach ($metadatas as $param) {
            if (!is_array($param)) {
                throw new Exception_InvalidArgument('Invalid metadatas argument');
            }

            $db_field = $databox_descriptionStructure->get_element($param['meta_struct_id']);

            if ($db_field->is_readonly() === true && !$force_readonly) {
                continue;
            }

            $this->set_metadata($param, $this->getDatabox());
        }

        $xml = new DOMDocument();
        $xml->loadXML($this->app['serializer.caption']->serialize($this->get_caption(), CaptionSerializer::SERIALIZE_XML, true));

        $this->set_xml($xml);
        unset($xml);

        $this->dispatch(RecordEvents::METADATA_CHANGED, new MetadataChangedEvent($this));

        return $this;
    }

    /**
     * @return record_adapter
     */
    public function rebuild_subdefs()
    {
        $sql = 'UPDATE record SET jeton=(jeton | :make_subdef_mask) WHERE record_id = :record_id';
        $stmt = $this->getDataboxConnection()->prepare($sql);
        $stmt->execute([
            ':record_id' => $this->getRecordId(),
            'make_subdef_mask' => PhraseaTokens::MAKE_SUBDEF,
        ]);
        $stmt->closeCursor();

        return $this;
    }

    public function get_missing_subdefs()
    {
        $databox = $this->getDatabox();

        try {
            $this->get_hd_file();
        } catch (\Exception $e) {
            return array();
        }

        $subDefDefinitions = $databox->get_subdef_structure()->getSubdefGroup($this->getType());
        if (!$subDefDefinitions) {
            return array();
        }

        $record = $this;
        $wanted_subdefs = array_map(function (databox_subdef $subDef) {
           return  $subDef->get_name();
        }, array_filter(iterator_to_array($subDefDefinitions), function (databox_subdef $subDef) use ($record) {
            return !$record->has_subdef($subDef->get_name());
        }));


        $missing_subdefs = array_map(function (media_subdef $subDef) {
            return $subDef->get_name();
        }, array_filter($this->get_subdefs(), function (media_subdef $subdef) {
            return !$subdef->is_physically_present();
        }));

        return array_values(array_merge($wanted_subdefs, $missing_subdefs));
    }

    /**
     * @return record_adapter
     */
    public function write_metas()
    {
        $tokenMask = PhraseaTokens::WRITE_META_DOC | PhraseaTokens::WRITE_META_SUBDEF;
        $this->getDataboxConnection()->executeUpdate(
            'UPDATE record SET jeton = jeton | :tokenMask WHERE record_id= :record_id',
            ['tokenMask' => $tokenMask, 'record_id' => $this->getRecordId()]
        );

        return $this;
    }

    private function dispatch($eventName, RecordEvent $event)
    {
        $this->app['dispatcher']->dispatch($eventName, $event);
    }

    /**
     *
     * @param Application $app
     * @param \collection $collection
     *
     * @return \record_adapter
     */
    public static function createStory(Application $app, \collection $collection)
    {
        $connection = $collection->get_databox()->get_connection();

        $sql = 'INSERT INTO record (coll_id, record_id, parent_record_id, moddate, credate, type, sha256, uuid, originalname, mime)'
            .' VALUES (:coll_id, NULL, :parent_record_id, NOW(), NOW(), :type, :sha256, :uuid , :originalname, :mime)';

        $stmt = $connection->prepare($sql);

        $stmt->execute([
            ':coll_id'          => $collection->get_coll_id(),
            ':parent_record_id' => 1,
            ':type'             => 'unknown',
            ':sha256'           => null,
            ':uuid'             => Uuid::uuid4(),
            ':originalname'     => null,
            ':mime'             => null,
        ]);
        $stmt->closeCursor();

        $story_id = $connection->lastInsertId();

        $story = new self($app, $collection->get_databox()->get_sbas_id(), $story_id);

        try {
            $log_id = $app['phraseanet.logger']($collection->get_databox())->get_id();

            $sql = 'INSERT INTO log_docs (id, log_id, date, record_id, action, final, comment)'
                . ' VALUES (null, :log_id, now(), :record_id, "add", :coll_id,"")';
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                ':log_id'    => $log_id,
                ':record_id' => $story_id,
                ':coll_id'   => $collection->get_coll_id(),
            ]);
            $stmt->closeCursor();
        } catch (\Exception $e) {
            unset($e);
        }

        $story->dispatch(RecordEvents::CREATED, new CreatedEvent($story));

        return $story;
    }

    /**
     * @param File        $file
     * @param Application $app
     *
     * @return \record_adapter
     */
    public static function createFromFile(File $file, Application $app)
    {
        $databox = $file->getCollection()->get_databox();

        $sql = "INSERT INTO record"
            . " (coll_id, record_id, parent_record_id, moddate, credate, type, sha256, uuid, originalname, mime)"
            . " VALUES (:coll_id, null, :parent_record_id, NOW(), NOW(), :type, :sha256, :uuid, :originalname, :mime)";

        $stmt = $databox->get_connection()->prepare($sql);

        $stmt->execute([
            ':coll_id'          => $file->getCollection()->get_coll_id(),
            ':parent_record_id' => 0,
            ':type'             => $file->getType() ? $file->getType()->getType() : 'unknown',
            ':sha256'           => $file->getMedia()->getHash('sha256'),
            ':uuid'             => $file->getUUID(true),
            ':originalname'     => $file->getOriginalName(),
            ':mime'             => $file->getFile()->getMimeType(),
        ]);
        $stmt->closeCursor();

        $record_id = $databox->get_connection()->lastInsertId();

        $record = new self($app, $databox->get_sbas_id(), $record_id);

        try {
            $log_id = $app['phraseanet.logger']($databox)->get_id();

            $sql = "INSERT INTO log_docs (id, log_id, date, record_id, action, final, comment)"
                . " VALUES (null, :log_id, now(), :record_id, 'add', :coll_id, '')";

            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute([
                ':log_id'    => $log_id,
                ':record_id' => $record_id,
                ':coll_id'   => $file->getCollection()->get_coll_id(),
            ]);
            $stmt->closeCursor();
        } catch (\Exception $e) {
            unset($e);
        }

        $filesystem = self::getFilesystem($app);

        $pathhd = $filesystem->generateDataboxDocumentBasePath($databox);
        $newname = $filesystem->generateDocumentFilename($record, $file->getFile());

        $filesystem->copy($file->getFile()->getRealPath(), $pathhd . $newname);

        $media = $app->getMediaFromUri($pathhd . $newname);
        media_subdef::create($app, $record, 'document', $media);

        $record->delete_data_from_cache(\record_adapter::CACHE_SUBDEFS);

        $record->insertTechnicalDatas($app['mediavorus']);

        $record->dispatch(RecordEvents::CREATED, new CreatedEvent($record));

        return $record;
    }

    /**
     * Read technical datas an insert them
     * This method can be long to perform
     *
     * @return record_adapter
     */
    public function insertTechnicalDatas(MediaVorus $mediavorus)
    {
        try {
            $document = $this->get_subdef('document');
        } catch (\Exception_Media_SubdefNotFound $e) {
            return $this;
        }

        $connection = $this->getDataboxConnection();
        $connection->executeUpdate('DELETE FROM technical_datas WHERE record_id = :record_id', [
            ':record_id' => $this->getRecordId(),
        ]);

        $sqlValues = [];

        foreach ($document->readTechnicalDatas($mediavorus) as $name => $value) {
            if (is_null($value)) {
                continue;
            } elseif (is_bool($value)) {
                if ($value) {
                    $value = 1;
                } else {
                    $value = 0;
                }
            }
            $sqlValues[] = [$this->getRecordId(), $name, $value];
        }

        if ($sqlValues) {
            $connection->transactional(function (Connection $connection) use ($sqlValues) {
                $statement = $connection->prepare('INSERT INTO technical_datas (record_id, name, value) VALUES (?, ?, ?)');
                array_walk($sqlValues, [$statement, 'execute']);
            });
        }

        $this->delete_data_from_cache(self::CACHE_TECHNICAL_DATA);

        return $this;
    }

    /**
     * @param databox $databox
     * @param string     $sha256
     * @param integer    $record_id
     * @return record_adapter[]
     * @deprecated use {@link databox::getRecordRepository} instead.
     */
    public static function get_record_by_sha(\databox $databox, $sha256, $record_id = null)
    {
        $records = $databox->getRecordRepository()->findBySha256($sha256);

        if (!is_null($record_id)) {
            $records = array_filter($records, function (record_adapter $record) use ($record_id) {
                return $record->getRecordId() == $record_id;
            });
        }

        return $records;
    }

    /**
     * Search for a record on a databox by UUID
     *
     * @param \databox $databox
     * @param string   $uuid
     * @param int      $record_id Restrict check on a record_id
     * @return record_adapter[]
     * @deprecated use {@link databox::getRecordRepository} instead.
     */
    public static function get_record_by_uuid(\databox $databox, $uuid, $record_id = null)
    {
        $records = $databox->getRecordRepository()->findByUuid($uuid);

        if (!is_null($record_id)) {
            $records = array_filter($records, function (record_adapter $record) use ($record_id) {
                return $record->getRecordId() == $record_id;
            });
        }

        return $records;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\File\File|null
     */
    public function get_hd_file()
    {
        $hd = $this->get_subdef('document');

        if ($hd->is_physically_present()) {
            return new SymfoFile($hd->getRealPath());
        }

        return null;
    }


    public function clearStampCache()
    {
        $connection = $this->getDataboxConnection();

        $sql = "SELECT path, file FROM record r INNER JOIN subdef s USING(record_id)\n"
            . "WHERE r.type='image' AND s.name IN ('preview', 'document') AND record_id = :record_id";

        $params = [
            ':record_id' => $this->getRecordId()
        ];

        $stmt = $connection->prepare($sql);
        $stmt->execute($params);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            @unlink(\p4string::addEndSlash($row['path']) . 'stamp_' . $row['file']);
        }
       $stmt->closeCursor();

       return $this;
    }

    /**
     * @return array[] list of deleted files real paths
     */
    public function delete()
    {
        $connection = $this->getDataboxConnection();

        $ftodel = [];
        foreach ($this->get_subdefs() as $subdef) {
            if (!$subdef->is_physically_present()) {
                continue;
            }

            if ($subdef->get_name() === 'thumbnail') {
                $this->app['phraseanet.thumb-symlinker']->unlink($subdef->getRealPath());
            }

            $ftodel[] = $subdef->getRealPath();
            $watermark = $subdef->getWatermarkRealPath();
            if (file_exists($watermark)) {
                $ftodel[] = $watermark;
            }
            $stamp = $subdef->getStampRealPath();
            if (file_exists($stamp)) {
                $ftodel[] = $stamp;
            }
        }

        $origcoll = $this->collection_id;

        $xml = $this->app['serializer.caption']->serialize($this->get_caption(), CaptionSerializer::SERIALIZE_XML);

        $this->app['phraseanet.logger']($this->getDatabox())
            ->log($this, Session_Logger::EVENT_DELETE, $origcoll, $xml);

        $sql = "DELETE FROM record WHERE record_id = :record_id";
        $stmt = $connection->prepare($sql);
        $stmt->execute([':record_id' => $this->getRecordId()]);
        $stmt->closeCursor();

        $sql = "DELETE FROM metadatas WHERE record_id = :record_id";
        $stmt = $connection->prepare($sql);
        $stmt->execute([':record_id' => $this->getRecordId()]);
        $stmt->closeCursor();

        $sql = "DELETE FROM permalinks WHERE subdef_id IN (SELECT subdef_id FROM subdef WHERE record_id=:record_id)";
        $stmt = $connection->prepare($sql);
        $stmt->execute([':record_id' => $this->getRecordId()]);
        $stmt->closeCursor();

        $sql = "DELETE FROM subdef WHERE record_id = :record_id";
        $stmt = $connection->prepare($sql);
        $stmt->execute([':record_id' => $this->getRecordId()]);
        $stmt->closeCursor();

        $sql = "DELETE FROM technical_datas WHERE record_id = :record_id";
        $stmt = $connection->prepare($sql);
        $stmt->execute([':record_id' => $this->getRecordId()]);
        $stmt->closeCursor();

        $sql = "DELETE FROM regroup WHERE rid_parent = :record_id1 OR rid_child = :record_id2";
        $stmt = $connection->prepare($sql);
        $stmt->execute([':record_id1' => $this->getRecordId(), ':record_id2' => $this->getRecordId()]);
        $stmt->closeCursor();

        $orderElementRepository = $this->app['repo.order-elements'];

        /** @var OrderElement $order_element */
        foreach ($orderElementRepository->findBy(['recordId' => $this->getRecordId()]) as $order_element) {
            if ($order_element->getSbasId($this->app) == $this->getDataboxId()) {
                $this->app['orm.em']->remove($order_element);
            }
        }

        $basketElementRepository = $this->app['repo.basket-elements'];

        foreach ($basketElementRepository->findElementsByRecord($this) as $basket_element) {
            $this->app['orm.em']->remove($basket_element);
        }

        $this->app['orm.em']->flush();

        $this->app['filesystem']->remove($ftodel);

        $this->delete_data_from_cache(self::CACHE_SUBDEFS);

        $this->dispatch(RecordEvents::DELETED, new DeletedEvent($this));

        return array_keys($ftodel);
    }

    /**
     * @param  string $option optional cache name
     * @return string
     */
    public function get_cache_key($option = null)
    {
        return 'record_' . $this->getId() . ($option ? '_' . $option : '');
    }

    public function clearSubdefCache($subdefname)
    {
        if ($this->has_subdef($subdefname)) {
            $this->get_subdef($subdefname)->delete_data_from_cache();

            $permalink = $this->get_subdef($subdefname)->get_permalink();

            if ($permalink instanceof media_Permalink_Adapter) {
                $permalink->delete_data_from_cache();
            }
        }

        $this->delete_data_from_cache(self::CACHE_SUBDEFS);
    }

    /**
     * @param  string $option optional cache name
     * @return mixed
     */
    public function get_data_from_cache($option = null)
    {
        $databox = $this->getDatabox();

        return $databox->get_data_from_cache($this->get_cache_key($option));
    }

    public function set_data_to_cache($value, $option = null, $duration = 0)
    {
        $databox = $this->getDatabox();

        return $databox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
    }

    public function delete_data_from_cache($option = null)
    {
        switch ($option)
        {
            case self::CACHE_SUBDEFS:
                $this->subdefs = null;
                break;
        }

        $databox = $this->getDatabox();

        $databox->delete_data_from_cache($this->get_cache_key($option));
    }

    public function log_view($log_id, $referrer, $gv_sit)
    {
        $sql = "INSERT INTO log_view (id, log_id, date, record_id, referrer, site_id)"
            . " VALUES (null, :log_id, now(), :rec, :referrer, :site)";

        $params = [
            ':log_id'   => $log_id
            , ':rec'      => $this->getRecordId()
            , ':referrer' => $referrer
            , ':site'     => $gv_sit,
        ];
        $stmt = $this->getDataboxConnection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    /**
     * @var array
     */
    protected $container_basket;

    /**
     * @todo de meme avec stories
     * @return \Alchemy\Phrasea\Model\Entities\Basket[]
     */
    public function get_container_baskets(EntityManager $em, User $user)
    {
        return $em
            ->getRepository('Phraseanet:Basket')
            ->findContainingRecordForUser($this, $user);
    }

    /**
     * @param databox $databox
     * @param int     $original_name
     * @param bool    $caseSensitive
     * @param int     $offset_start
     * @param int     $how_many
     *
     * @return record_adapter[]
     */
    public static function get_records_by_originalname(databox $databox, $original_name, $caseSensitive = false, $offset_start = 0, $how_many = 10)
    {
        $offset_start = max(0, (int)$offset_start);
        $how_many = max(1, (int)$how_many);

        $sql = sprintf(
            'SELECT record_id FROM record WHERE originalname = :original_name COLLATE %s LIMIT %d, %d',
            $caseSensitive ? 'utf8_bin' : 'utf8_unicode_ci',
            $offset_start,
            $how_many
        );

        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute([':original_name' => $original_name]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $records = [];
        foreach ($rs as $row) {
            $records[] = $databox->get_record($row['record_id']);
        }

        return $records;
    }

    /**
     * @return set_selection|record_adapter[]
     * @throws Exception
     * @throws \Doctrine\DBAL\DBALException
     * @deprecated use {@link self::getChildren} instead.
     */
    public function get_children()
    {
        return $this->getChildren();
    }

    /**
     * @return record_adapter[]|set_selection
     * @throws Exception
     */
    public function getChildren()
    {
        if (!$this->isStory()) {
            throw new Exception('This record is not a grouping');
        }

        $user = $this->getAuthenticatedUser();
        $selections = $this->getDatabox()->getRecordRepository()->findChildren([$this->getRecordId()], $user);

        return reset($selections);
    }

    /**
     * @return set_selection
     */
    public function get_grouping_parents()
    {
        $user = $this->getAuthenticatedUser();
        $selections = $this->getDatabox()->getRecordRepository()->findParents([$this->getRecordId()], $user);

        return reset($selections);
    }

    public function hasChild(\record_adapter $record)
    {
        return $this->getChildren()->offsetExists($record->getId());
    }

    public function appendChild(\record_adapter $record)
    {
        if (!$this->isStory()) {
            throw new \Exception('Only stories can append children');
        }

        $ord = 0;

        $sql = "SELECT (max(ord)+1) as ord FROM regroup WHERE rid_parent = :parent_record_id";

        $connection = $this->getDataboxConnection();

        $stmt = $connection->prepare($sql);

        $stmt->execute([':parent_record_id' => $this->getRecordId()]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt->closeCursor();

        if ($row) {
            $ord = is_null($row["ord"]) ? 0 : $row["ord"];
        }

        $sql = "INSERT INTO regroup (id, rid_parent, rid_child, dateadd, ord)"
            . " VALUES (null, :parent_record_id, :record_id, NOW(), :ord)";

        $params = [
            ':parent_record_id' => $this->getRecordId()
            , ':record_id'        => $record->getRecordId()
            , ':ord'              => $ord,
        ];

        $stmt = $connection->prepare($sql);
        $stmt->execute($params);

        $stmt->closeCursor();

        $this->touch();

        return $this;
    }

    public function removeChild(\record_adapter $record)
    {
        if (!$this->isStory()) {
            throw new \Exception('Only stories can append children');
        }

        $sql = "DELETE FROM regroup WHERE rid_parent = :parent_record_id AND rid_child = :record_id";

        $params = [
            ':parent_record_id' => $this->getRecordId()
            , ':record_id'        => $record->getRecordId(),
        ];

        $stmt = $this->getDataboxConnection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->touch();

        return $this;
    }

    /** {@inheritdoc} */
    public function getDataboxId()
    {
        return $this->reference->getDataboxId();
    }

    /** {@inheritdoc} */
    public function getOriginalName()
    {
       return $this->get_original_name();
    }

    /** {@inheritdoc} */
    public function setOriginalName($originalName)
    {
        $this->set_original_name($originalName);
    }

    /** {@inheritdoc} */
    public function getId()
    {
        return $this->reference->getId();
    }

    /**
     * @param string $status
     * @return void
     */
    public function setStatus($status)
    {
        $this->getDataboxConnection()->executeUpdate(
            'UPDATE record SET moddate = NOW(), status = :status WHERE record_id=:record_id',
            ['status' => bindec($status), 'record_id' => $this->getRecordId()]
        );

        $this->status = str_pad($status, 32, '0', STR_PAD_LEFT);
        // modification date is now unknown, delete from cache to reload on another record
        $this->delete_data_from_cache();

        $this->dispatch(RecordEvents::STATUS_CHANGED, new StatusChangedEvent($this));
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /** {@inheritdoc} */
    public function getStatusBitField()
    {
        return bindec($this->getStatus());
    }

    /** {@inheritdoc} */
    public function getExif()
    {
        return $this->get_technical_infos()->getValues();
    }

    public function getStatusStructure()
    {
        return $this->getDatabox()->getStatusStructure();
    }

    public function putInCache()
    {
        $data = [
            'mime'          => $this->mime,
            'sha256'        => $this->sha256,
            'originalName'  => $this->original_name,
            'type'          => $this->type,
            'isStory'       => $this->isStory,
            'uuid'          => $this->uuid,
            'updated'       => $this->updated->format(DATE_ISO8601),
            'created'       => $this->created->format(DATE_ISO8601),
            'base_id'       => $this->base_id,
            'collection_id' => $this->collection_id,
            'status' => $this->status,
        ];

        $this->set_data_to_cache($data);
    }

    /**
     * @param array $row
     */
    public function mapFromData(array $row)
    {
        if (!isset($row['base_id'])) {
            $row['base_id'] = phrasea::baseFromColl($this->getDataboxId(), $row['collection_id'], $this->app);
        }

        $this->collection_id = (int)$row['collection_id'];
        $this->base_id = (int)$row['base_id'];
        $this->created = new DateTime($row['created']);
        $this->updated = new DateTime($row['updated']);
        $this->uuid = $row['uuid'];

        $this->isStory = ($row['isStory'] == '1');
        $this->type = $row['type'];
        $this->original_name = $row['originalName'];
        $this->sha256 = $row['sha256'];
        $this->mime = $row['mime'];
        $this->status = str_pad($row['status'], 32, '0', STR_PAD_LEFT);
    }

    /**
     * @return Connection
     */
    protected function getDataboxConnection()
    {
        if (null === $this->connection) {
            $this->connection = $this->getDatabox()->get_connection();
        }

        return $this->connection;
    }

    /**
     * @return MediaSubdefRepository
     */
    private function getMediaSubdefRepository()
    {
        return $this->app['provider.repo.media_subdef']->getRepositoryForDatabox($this->getDataboxId());
    }

    /**
     * @return User|null
     */
    protected function getAuthenticatedUser()
    {
        /** @var \Alchemy\Phrasea\Authentication\Authenticator $authenticator */
        $authenticator = $this->app['authentication'];

        return $authenticator->getUser();
    }
}
