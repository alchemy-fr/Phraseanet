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
use Alchemy\Phrasea\Application\Helper\DataboxLoggerAware;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\SubDefinitionSubstituerAware;
use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Cache\Exception;
use Alchemy\Phrasea\Core\Event\Record\CollectionChangedEvent;
use Alchemy\Phrasea\Core\Event\Record\CreatedEvent;
use Alchemy\Phrasea\Core\Event\Record\DeletedEvent;
use Alchemy\Phrasea\Core\Event\Record\MetadataChangedEvent;
use Alchemy\Phrasea\Core\Event\Record\OriginalNameChangedEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Core\Event\Record\StatusChangedEvent;
use Alchemy\Phrasea\Core\Event\Record\StoryCoverChangedEvent;
use Alchemy\Phrasea\Core\Event\Record\SubdefinitionCreateEvent;
use Alchemy\Phrasea\Core\Event\RecordEdit;
use Alchemy\Phrasea\Core\PhraseaEvents;
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
use Alchemy\Phrasea\Model\Repositories\FeedItemRepository;
use Alchemy\Phrasea\Model\Serializer\CaptionSerializer;
use Alchemy\Phrasea\Record\RecordReference;
use Alchemy\Phrasea\Twig\PhraseanetExtension;
use Alchemy\Phrasea\WorkerManager\Event\RecordsWriteMetaEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use MediaVorus\MediaVorus;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\File\File as SymfoFile;

class record_adapter implements RecordInterface, cache_cacheableInterface
{
    use DataboxLoggerAware;
    use DispatcherAware;
    use SubDefinitionSubstituerAware;

    const CACHE_ORIGINAL_NAME = 'originalname';
    const CACHE_TECHNICAL_DATA = 'technical_data';
    const CACHE_MIME = 'mime';
    const CACHE_TITLE = 'title';
    const CACHE_SHA256 = 'sha256';
    const CACHE_SUBDEFS = 'subdefs';
    const CACHE_GROUPING = 'grouping';

    const ENCODE_NONE = 'encode_none';
    const ENCODE_FOR_HTML = 'encode_for_html';
    const ENCODE_FOR_URI = 'encode_for_uri';

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
    private $cover_record_id;
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
        $this->cover_record_id = $record->getCoverRecordId();
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
     * @throws DBALException
     * @deprecated use {@link self::setType} instead.
     */
    public function set_type($type)
    {
        return $this->setType($type);
    }

    /**
     * @param string $type
     * @param bool $shouldSubdefsBeRebuilt
     *
     * @return $this
     * @throws Exception
     * @throws DBALException
     */
    public function setType($type, $shouldSubdefsBeRebuilt = true)
    {
        $type = strtolower($type);

        $old_type = $this->getType();

        if (!in_array($type, ['document', 'audio', 'video', 'image', 'flash', 'map'])) {
            throw new Exception('unrecognized document type');
        }

        $sql = 'UPDATE record SET moddate = NOW(), type = :type WHERE record_id = :record_id';
        $this->getDataboxConnection()->executeUpdate($sql, ['type' => $type, 'record_id' => $this->getRecordId()]);

        if (($old_type !== $type) && $shouldSubdefsBeRebuilt) {
            $this->dispatch(RecordEvents::SUBDEFINITION_CREATE, new SubdefinitionCreateEvent($this));
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

    /**
     * @param $mime
     * @param bool $shouldSubdefsBeRebuilt
     *
     * @return $this
     * @throws DBALException
     */
    public function setMimeType($mime, $shouldSubdefsBeRebuilt = true)
    {
        $oldMime = $this->getMimeType();

        // see http://lists.w3.org/Archives/Public/xml-dist-app/2003Jul/0064.html
        if (!preg_match("/^[a-zA-Z0-9!#$%^&\\*_\\-\\+{}\\|'.`~]+\\/[a-zA-Z0-9!#$%^&\\*_\\-\\+{}\\|'.`~]+$/", $mime)) {
            throw new \Exception(sprintf('Unrecognized mime type %s', $mime));
        }

        if ($this->getDataboxConnection()->executeUpdate(
            'UPDATE record SET moddate = NOW(), mime = :mime WHERE record_id = :record_id',
            array(':mime' => $mime, ':record_id' => $this->getRecordId())
        )) {

            if (($oldMime !== $mime) && $shouldSubdefsBeRebuilt) {
                $this->dispatch(RecordEvents::SUBDEFINITION_CREATE, new SubdefinitionCreateEvent($this));
            }

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

    public function getCoverRecordId()
    {
        return $this->cover_record_id;
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
     * @return collection
     * @deprecated use {@link self::getCollection} instead.
     */
    public function get_collection()
    {
        return $this->getCollection();
    }

    /**
     * Return collection to which the record belongs to.
     *
     * @return collection
     */
    public function getCollection()
    {
        return collection::getByCollectionId($this->app, $this->getDatabox(), $this->collection_id);
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

    public function getDataboxName()
    {
        return $this->getDatabox()->get_viewname();
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
     * @param  appbox         $appbox       WTF this parm is useless
     * @return record_adapter
     *
     */
    public function move_to_collection(collection $collection, appbox $appbox = null)
    {
        if ($this->getCollection()->get_base_id() === $collection->get_base_id()) {
            $this->dispatch(RecordEvents::COLLECTION_CHANGED, new CollectionChangedEvent($this, $collection, $collection));

            return $this;
        }

        $beforeCollection = $this->getCollection();
        $coll_id_from = $this->getCollectionId();
        $coll_id_to = $collection->get_coll_id();

        $sql = "UPDATE record SET moddate = NOW(), coll_id = :coll_id WHERE record_id =:record_id";

        $params = [
            ':coll_id'   => $coll_id_to,
            ':record_id' => $this->getRecordId(),
        ];

        $stmt = $this->getDataboxConnection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->base_id = $collection->get_base_id();
        $this->collection_id = $coll_id_to;

        $this->delete_data_from_cache();

        $this->app['phraseanet.logger']($this->getDatabox())
            ->log($this, Session_Logger::EVENT_MOVE, $collection->get_coll_id(), '', $coll_id_from);

        $this->dispatch(RecordEvents::COLLECTION_CHANGED, new CollectionChangedEvent($this, $beforeCollection, $collection));

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
     * Return an array containing GPSPosition
     *
     * @return array
     */
    public function getPositionFromTechnicalInfos()
    {
        $positionTechnicalField = [
            media_subdef::TC_DATA_LATITUDE,
            media_subdef::TC_DATA_LONGITUDE
        ];
        $position = [];

        foreach($positionTechnicalField as $field){
            $fieldData = $this->get_technical_infos($field);

            if($fieldData){
                $position[$field] = $fieldData->getValue();
            }
        }

        if(count($position) == 2){
            return [
                'isCoordComplete' => 1,
                'latitude' => $position[media_subdef::TC_DATA_LATITUDE],
                'longitude' => $position[media_subdef::TC_DATA_LONGITUDE]
            ];
        }

        return ['isCoordComplete' => 0, 'latitude' => 'false', 'longitude' => 'false'];
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
     * @return array
     */
    public function getRecordDescriptionAsArray()
    {
        $helpers = new PhraseanetExtension($this->app);
        $description = [];

        foreach ($this->getDatabox()->get_meta_structure()->get_elements() as $data_field) {
            $fieldName = $data_field->get_name();

            if ($this->get_caption()->has_field($fieldName)) {
                try {
                    $captionField =  $this->get_caption()->get_field($fieldName);
                } catch (\Exception $e) {
                    continue;
                }

                $fieldValues = $captionField->get_values();

                $fieldLabel = $helpers->getCaptionFieldLabel($this, $fieldName);

                $description[$fieldLabel] = $helpers->getCaptionField($this, $fieldName, $fieldValues);
            }
        }

        return $description;
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

        // order to write metas
        $this->app['dispatcher']->dispatch(WorkerEvents::RECORDS_WRITE_META,
            new RecordsWriteMetaEvent([$this->getRecordId()], $this->getDataboxId())
        );

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
        $encode = igorw\get_in($options, ['encode'], self::ENCODE_NONE);
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
                    $v = $v['value'];
                    switch ($encode) {
                        case self::ENCODE_FOR_HTML:
                            $v = htmlentities($v);
                            break;
                        case self::ENCODE_FOR_URI:
                            $v = urlencode($v);
                            break;
                    }
                    $titles[] = $v;
                }
            }
            $title = trim(implode(' - ', $titles));
        }

        if (trim($title) === '') {
            $title = trim($this->get_original_name($removeExtension));
            switch ($encode) {
                case self::ENCODE_FOR_HTML:
                    $title = htmlentities($title);
                    break;
                case self::ENCODE_FOR_URI:
                    $title = urlencode($title);
                    break;
            }
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

        foreach ($mandatoryParams as $k) {
            if (!array_key_exists($k, $params)) {
                throw new Exception_InvalidArgument(sprintf('Invalid metadata, missing key %s', $k));
            }
            $params[$k] = trim($params[$k]);
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

        $new_val = $params['value'];
        $meta_id = ($params['meta_id'] !== '') ? (int)($params['meta_id']) : null;

        //
        // preserve unicity of multi-values : no doubles please
        //
        $values = $caption_field->get_values(); // existing values
        $value_found = null;
        $meta_found = null;
        foreach ($values as $v) {
            if($v->getValue() === $new_val) {
                // the value already exists
                $value_found = $v;
            }
            if(!is_null($meta_id) && $v->getId() === $meta_id) {
                // the imposed meta is found
                $meta_found = $v;
            }
        }

        if (!is_null($meta_id)) {
            //
            // here we want to override a specific value (by meta-id)
            //
            if(!$meta_found) {
                // this meta_id does not exists, we cannot override it
                return $this;
            }

            if ($new_val === '') {
                // override with empty = delete
                $meta_found->delete();
            }
            else {
                // override with new value
                if($value_found && $value_found->getId() !== $meta_found->getId()) {
                    // the new value did already exists _elsewhere_, we must delete it to avoid doubles
                    $value_found->delete();
                }
                $meta_found->set_value($new_val);
                if ($vocab && $vocab_id) {
                    $meta_found->setVocab($vocab, $vocab_id);
                }
            }
        }
        else {
            //
            // here we want to set/add a value. if the field is mono, "create()" will override it if necessary
            //
            if($databox_field->is_multi()) {
                // add a _non empty_ value only if it does not already exists
                if($new_val !== '' && !$value_found) {
                    caption_Field_Value::create($this->app, $databox_field, $this, $new_val, $vocab, $vocab_id);
                }
            }
            else {
                // set a mono value
                foreach ($values as $v) {       // delete former one (should be unique)
                    $v->delete();
                }
                if($new_val !== '') {
                    caption_Field_Value::create($this->app, $databox_field, $this, $new_val, $vocab, $vocab_id);
                }
            }
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

        $this->write_metas();

        $this->dispatch(RecordEvents::METADATA_CHANGED, new MetadataChangedEvent($this));

        return $this;
    }

    public function setMetadatasByActions(stdClass $actions)
    {
        // WIP crashes when trying to access an undefined stdClass property ? should return null ?
        $this->apply_body($actions);
        return $this;
    }


    /*
     * =============================================================================
     * the following methods allows editing by the json api-v3:record:post format
     *
     */

    /**
     * @param stdClass $b
     * @throws Exception
     */
    private function apply_body(stdClass $b)
    {
        // do metadatas ops
        if (is_array(@$b->metadatas)) {
            $this->do_metadatas($b->metadatas);
        }
        // do sb ops
        if (is_array(@$b->status)) {
            $this->do_status($b->status);
        }
        if(!is_null(@$b->base_id)) {
            $this->do_collection($b->base_id);
        }
    }

    /**
     * @param $base_id
     */
    private function do_collection($base_id)
    {
        $this->move_to_collection(collection::getByCollectionId($this->app, $this->getDatabox(), $base_id));
    }


    //////////////////////////////////
    /// TODO : keep multi-values uniques !
    /// it should be done in record_adapter
    //////////////////////////////////

    /**
     * @param $metadatas
     * @throws Exception
     *
     *  nb : use of "silent" @ operator on stdClass member access (equals null in not defined) is more simple than "iseet()" or "empty()"
     */
    private function do_metadatas(array $metadatas)
    {
        /** @var databox_field[]  $struct */
        $struct = $this->getDatabox()->get_meta_structure();


        $structByKey = [];
        $allStructFields = [];
        foreach ($struct as $f) {
            $allStructFields[$f->get_id()] = $f;
            $structByKey[$f->get_id()]   =  &$allStructFields[$f->get_id()];
            $structByKey[$f->get_name()] = &$allStructFields[$f->get_id()];
        }

        $metadatas_ops = [];
        /** @var stdClass $_m */
        foreach ($metadatas as $_m) {
            // sanity
            if(@$_m->meta_struct_id && @$_m->field_name) {                // WIP crashes if meta_struct_id is undefined
                throw new Exception("define meta_struct_id OR field_name, not both.");
            }
            // select fields that match meta_struct_id or field_name (can be arrays)
            $fields_list = null;    // to filter caption_fields from record, default all
            $struct_fields = [];    // struct fields that match meta_struct_id or field_name
            $field_keys = @$_m->meta_struct_id ?: @$_m->field_name;  // can be null if none defined (=match all)
            if($field_keys !== null) {
                if (!is_array($field_keys)) {
                    $field_keys = [$field_keys];
                }
                $fields_list = [];
                foreach ($field_keys as $k) {
                    if(array_key_exists($k, $structByKey)) {
                        $fields_list[] = $structByKey[$k]->get_name();
                        $struct_fields[$structByKey[$k]->get_id()] = $structByKey[$k];
                    }
                    else {
                        throw new Exception(sprintf("unknown field (%s).", $k));
                    }
                }
            }
            else {
                // no meta_struct_id, no field_name --> match all struct fields !
                $struct_fields = $allStructFields;
            }
            $caption_fields = $this->get_caption()->get_fields($fields_list, true);

            $meta_id = isset($_m->meta_id) ? (int)($_m->meta_id) : null;

            if(!($match_method = (string)(@$_m->match_method))) {
                $match_method = 'ignore_case';
            }
            if(!in_array($match_method, ['strict', 'ignore_case', 'regexp'])) {
                throw new Exception(sprintf("bad match_method (%s).", $match_method));
            }

            $values = [];
            if(is_array(@$_m->value)) {
                foreach ($_m->value as $v) {
                    if(($v = trim((string)$v)) !== '') {
                        $values[] = $v;
                    }
                }
            }
            else {
                if(($v = trim((string)(@$_m->value))) !== '') {
                    $values[] = $v;
                }
            }

            if(!($action = (string)(@$_m->action))) {
                $action = 'set';
            }

            switch ($action) {
                case 'set':
                    $ops = $this->metadata_set($struct_fields, $caption_fields, $meta_id, $values);
                    break;
                case 'add':
                    $ops = $this->metadata_add($struct_fields, $values);
                    break;
                case 'delete':
                    $ops = $this->metadata_replace($caption_fields, $meta_id, $match_method, $values, null);
                    break;
                case 'replace':
                    if (!isset($_m->replace_with)) {
                        throw new Exception("missing mandatory \"replace_with\" for action \"replace\".");
                    }
                    if (!is_string($_m->replace_with) && !is_null($_m->replace_with)) {
                        throw new Exception("bad \"replace_with\" for action \"replace\".");
                    }
                    $ops = $this->metadata_replace($caption_fields, $meta_id, $match_method, $values, $_m->replace_with);
                    break;
                default:
                    throw new Exception(sprintf("bad action (%s).", $action));
            }

            $metadatas_ops = array_merge($metadatas_ops, $ops);
        }

        $this->set_metadatas($metadatas_ops, true);

        // order to write meta in file
        $this->app['dispatcher']->dispatch(WorkerEvents::RECORDS_WRITE_META,
            new RecordsWriteMetaEvent([$this->getRecordId()],  $this->getDataboxId()));

    }

    /**
     * @param $statuses
     * @return array
     * @throws Exception
     */
    private function do_status(array $statuses)
    {
        $datas = strrev($this->getStatus());

        foreach ($statuses as $status) {
            $n = (int)(@$status->bit);
            $value = (int)(@$status->state);
            if ($n > 31 || $n < 4) {
                throw new Exception(sprintf("Invalid status bit number (%s).", $n));
            }
            if ($value < 0 || $value > 1) {
                throw new Exception(sprintf("Invalid status bit state (%s) for bit (%s).", $value, $n));
            }

            $datas = substr($datas, 0, ($n)) . $value . substr($datas, ($n + 1));
        }

        $this->setStatus(strrev($datas));
    }

    private function match($pattern, $method, $value)
    {
        switch ($method) {
            case 'strict':
                return $value === $pattern;
            case 'ignore_case':
                return strtolower($value) === strtolower($pattern);
            case 'regexp':
                return preg_match($pattern, $value) == 1;
        }
        return false;
    }

    /**
     * @param databox_field[] $struct_fields  struct-fields (from struct) matching meta_struct_id or field_name
     * @param caption_field[] $caption_fields caption-fields (from record) matching meta_struct_id or field_name (or all if not set)
     * @param int|null $meta_id
     * @param string[] $values
     *
     * @return array                            ops to execute
     * @throws Exception
     */
    private function metadata_set(array $struct_fields, array $caption_fields, $meta_id, array $values): array
    {
        $ops = [];

        // if one field was multi-valued and no meta_id was set, we must delete all values
        foreach ($caption_fields as $cf) {
            foreach ($cf->get_values() as $field_value) {
                if (is_null($meta_id) || $field_value->getId() === (int)$meta_id) {
                    $ops[] = [
                        'explain' => sprintf('set:: removing value "%s" from "%s"', $field_value->getValue(), $cf->get_name()),
                        'meta_struct_id' => $cf->get_meta_struct_id(),
                        'meta_id'        => $field_value->getId(),
                        'value'          => ''
                    ];
                }
            }
        }
        // now set values to matching struct_fields
        foreach ($struct_fields as $sf) {
            if($sf->is_multi()) {
                //  add the non-null value(s)
                foreach ($values as $value) {
                    if ($value) {
                        $ops[] = [
                            'expain'         => sprintf('set:: adding value "%s" to "%s" (multi)', $value, $sf->get_name()),
                            'meta_struct_id' => $sf->get_id(),
                            'meta_id'        => $meta_id,  // can be null
                            'value'          => $value
                        ];
                    }
                }
            }
            else {
                // mono-valued
                if(count($values) > 1) {
                    throw new Exception(sprintf("set:: setting mono-valued (%s) requires only one value.", $sf->get_name()));
                }
                if( ($value = $values[0]) ) {
                    $ops[] = [
                        'expain' => sprintf('adding value "%s" to "%s" (mono)', $value, $sf->get_name()),
                        'meta_struct_id' => $sf->get_id(),
                        'meta_id'        => $meta_id,  // probably null,
                        'value'          => $value
                    ];
                }
            }
        }

        return $ops;
    }

    /**
     * @param databox_field[] $struct_fields struct-fields (from struct) matching meta_struct_id or field_name
     * @param string[] $values
     *
     * @return array                            ops to execute
     * @throws Exception
     */
    private function metadata_add($struct_fields, $values)
    {
        $ops = [];

        // now set values to matching struct_fields
        foreach ($struct_fields as $sf) {
            if(!$sf->is_multi()) {
                throw new Exception(sprintf("can't \"add\" to mono-valued (%s).", $sf->get_name()));
            }
            foreach ($values as $value) {
                $ops[] = [
                    'expain'         => sprintf('add:: adding value "%s" to "%s"', $value, $sf->get_name()),
                    'meta_struct_id' => $sf->get_id(),
                    'meta_id'        => null,
                    'value'          => $value
                ];
            }
        }

        return $ops;
    }

    /**
     * @param caption_field[] $caption_fields  caption-fields (from record) matching meta_struct_id or field_name (or all if not set)
     * @param int|null $meta_id
     * @param string $match_method              "strict" | "ignore_case" | "regexp"
     * @param string[] $values
     * @param string|null $replace_with
     *
     * @return array                            ops to execute
     */
    private function metadata_replace($caption_fields, $meta_id, $match_method, $values, $replace_with)
    {
        $ops = [];

        $replace_with = trim((string)$replace_with);

        foreach ($caption_fields as $cf) {

            // match all ?
            //
            if(is_null($meta_id) && count($values) == 0) {
                // first delete former values
                foreach ($cf->get_values() as $field_value) {
                    $ops[] = [
                        'explain' => sprintf('rpl::match_all: removing value "%s" from "%s"', $field_value->getValue(), $cf->get_name()),
                        'meta_struct_id' => $cf->get_meta_struct_id(),
                        'meta_id'        => $field_value->getId(),
                        'value'          => ''
                    ];
                }
                // then add the replacing value
                $ops[] = [
                    'expain' => sprintf('rpl::match_all: adding value "%s" to "%s"', $replace_with, $cf->get_name()),
                    'meta_struct_id' => $cf->get_meta_struct_id(),
                    'meta_id'        => null,
                    'value'          => $replace_with
                ];
            }

            // match by meta-id ?
            //
            if (!is_null($meta_id)) {
                foreach ($cf->get_values() as $field_value) {
                    if ($field_value->getId() === $meta_id) {
                        $ops[] = [
                            'expain' => sprintf('rpl::match_meta_id %s (field "%s") set value "%s"', $field_value->getId(), $cf->get_name(), $replace_with),
                            'meta_struct_id' => $cf->get_meta_struct_id(),
                            'meta_id'        => $field_value->getId(),
                            'value'          => $replace_with
                        ];
                    }
                }
            }

            // match by value(s) ?
            //
            foreach ($values as $value) {
                foreach ($cf->get_values() as $field_value) {
                    $rw = $replace_with;
                    if($match_method=='regexp' && $rw != '') {
                        $rw = preg_replace($value, $rw, $field_value->getValue());
                    }
                    if ($this->match($value, $match_method, $field_value->getValue())) {
                        $ops[] = [
                            'expain' => sprintf('rpl::match_value "%s" (field "%s") set value "%s"', $field_value->getValue(), $cf->get_name(), $rw),
                            'meta_struct_id' => $cf->get_meta_struct_id(),
                            'meta_id'        => $field_value->getId(),
                            'value'          => $rw
                        ];
                    }
                }
            }
        }

        return $ops;
    }

    /*
     *
     * END  editing by the json api-v3:record:post format
     * =============================================================================
     */



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

    private function dispatch($eventName, Event $event)
    {
        $this->app['dispatcher']->dispatch($eventName, $event);
    }

    /**
     *
     * @param Application $app
     * @param collection $collection
     *
     * @return record_adapter
     */
    public static function createStory(Application $app, collection $collection)
    {
        $connection = $collection->get_databox()->get_connection();

        $sql = 'INSERT INTO record (coll_id, record_id, parent_record_id, cover_record_id, moddate, credate, type, sha256, uuid, originalname, mime)'
            .' VALUES (:coll_id, NULL, :parent_record_id, NULL, NOW(), NOW(), :type, :sha256, :uuid , :originalname, :mime)';

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

            $sql = 'INSERT INTO log_docs (id, log_id, date, record_id, coll_id, action, final, comment)'
                . ' VALUES (null, :log_id, now(), :record_id, :coll_id, "add", :final, "")';
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                ':log_id'    => $log_id,
                ':record_id' => $story_id,
                ':coll_id'   => $collection->get_coll_id(),
                ':final'     => $collection->get_coll_id(),
            ]);
            $stmt->closeCursor();
        } catch (\Exception $e) {
            unset($e);
        }

        $story->dispatch(RecordEvents::CREATED, new CreatedEvent($story));

        return $story;
    }

    /**
     * set the cover of a story by copying thumbnail and prewiew from a selected child
     * @param $fromChildRecordId
     * @param array $coverSources   for apiV1 : one can map the story thmb/prev to another subdef, e.g. ['preview_cover_source' => "preview1200"]
     *                              default to same thumbnail/preview
     * @return string               id of the selected child (for apiv1)
     * @throws \Exception
     */
    public function setStoryCover($fromChildRecordId, $coverSources = [])
    {
        if(!$this->isStory) {
            throw new \Exception(sprintf('Record is not a story'));
        }

        $previousDescription = $this->getRecordDescriptionAsArray();
        $coverSources = array_merge(['thumbnail_cover_source' => 'thumbnail', 'preview_cover_source' => 'preview'], $coverSources);

        $fromChildRecord = new self($this->app, $this->getDataboxId(), $fromChildRecordId);

        if (!$this->hasChild($fromChildRecord)) {
            throw new \Exception(sprintf('Record identified by databox_id %s and record_id %s is not in the story', $this->getDataboxId(), $fromChildRecordId));
        }

        $this->cover_record_id = $fromChildRecordId;

        $databox = $this->getDatabox();
        $sql = "UPDATE record SET `cover_record_id` = :cover_record_id WHERE record_id = :story_id";
        $connection = $databox->get_connection();
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            ':cover_record_id' => $this->getCoverRecordId(),
            ':story_id'        => $this->getRecordId()
        ]);
        $stmt->closeCursor();

        // try to copy thumbnail & preview

        foreach ($fromChildRecord->get_subdefs() as $name => $value) {
            if (!($key = array_search($name, $coverSources))) {
                continue;
            }
            if ($value->get_type() !== \media_subdef::TYPE_IMAGE) {
                continue;
            }

            $name = ($key == 'thumbnail_cover_source') ? 'thumbnail': 'preview';

            $media = $this->app->getMediaFromUri($value->getRealPath());
            // same db => no need to adapt size, do a simple copy
            $this->getSubDefinitionSubstituer()->substituteSubdef($this, $name, $media, false);
            $this->getDataboxLogger($this->getDatabox())->log(
                $this,
                \Session_Logger::EVENT_SUBSTITUTE,
                $name,
                ''
            );
        }

        $this->delete_data_from_cache();

        $this->dispatch(RecordEvents::STORY_COVER_CHANGED, new StoryCoverChangedEvent($this, $fromChildRecord));
        $this->dispatch(PhraseaEvents::RECORD_EDIT, new RecordEdit($this, $previousDescription));

        return $fromChildRecord->getId();
    }

    /**
     * @param File $file
     * @param Application $app
     *
     * @return record_adapter|null
     * @throws DBALException
     */
    public static function createFromFile(File $file, Application $app)
    {
        $collection = $file->getCollection();

        $record = self::_create(
            $collection,
            $app,
            $file
        );
        if($record) {
            $databox = $record->getDatabox();

            $filesystem = self::getFilesystem($app);

            $pathhd = $filesystem->generateDataboxDocumentBasePath($databox);
            $newname = $filesystem->generateDocumentFilename($record, $file->getFile());
            $newname_tmp = $newname.".tmp";

            clearstatcache(true, $file->getFile()->getRealPath());

            $filesystem->copy($file->getFile()->getRealPath(), $pathhd . $newname_tmp);

            clearstatcache(true, $pathhd . $newname_tmp);

            $filesystem->rename($pathhd . $newname_tmp, $pathhd . $newname);

            clearstatcache(true, $pathhd . $newname);

            $media = $app->getMediaFromUri($pathhd . $newname);
            media_subdef::create($app, $record, 'document', $media);

            $record->delete_data_from_cache(record_adapter::CACHE_SUBDEFS);

            $record->insertTechnicalDatas($app['mediavorus']);

            $record->dispatch(RecordEvents::CREATED, new CreatedEvent($record));
        }

        return $record;
    }

    /**
     * create a record without document
     *
     * @param collection $collection
     * @param Application $app
     *
     * @return record_adapter|null
     * @throws DBALException
     */
    public static function create(collection $collection, Application $app)
    {
        $record = self::_create($collection, $app);
        if($record) {
            $record->dispatch(RecordEvents::CREATED, new CreatedEvent($record));
        }

        return $record;
    }

    /**
     * @param collection $collection
     * @param Application $app
     * @param File|null $file
     * @return record_adapter|null
     * @throws DBALException
     */
    private static function _create(collection $collection, Application $app, File $file=null)
    {
        $databox = $collection->get_databox();

        $sql = "INSERT INTO record"
            . " (coll_id, record_id, parent_record_id, moddate, credate, type, sha256, uuid, originalname, mime)"
            . " VALUES (:coll_id, null, :parent_record_id, NOW(), NOW(), :type, :sha256, :uuid, :originalname, :mime)";

        $connection = $databox->get_connection();
        $stmt = $connection->prepare($sql);

        $stmt->execute([
            ':coll_id'          => $collection->get_coll_id(),
            ':parent_record_id' => 0,
            ':type'             => $file ? ($file->getType() ? $file->getType()->getType() : 'unknown') : 'unknown',
            ':sha256'           => $file ? $file->getMedia()->getHash('sha256') : null,
            ':uuid'             => $file ? $file->getUUID(true) : null,
            ':originalname'     => $file ? $file->getOriginalName() : null,
            ':mime'             => $file ? $file->getFile()->getMimeType() : null,
        ]);
        $stmt->closeCursor();

        $record_id = $connection->lastInsertId();

        $record = new self($app, $databox->get_sbas_id(), $record_id);

        try {
            $log_id = $app['phraseanet.logger']($databox)->get_id();

            $sql = "INSERT INTO log_docs (id, log_id, date, record_id, coll_id, action, final, comment)"
                . " VALUES (null, :log_id, now(), :record_id, :coll_id, 'add', :final, '')";

            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute([
                ':log_id'    => $log_id,
                ':record_id' => $record_id,
                ':coll_id'   => $collection->get_coll_id(),
                ':final'     => $collection->get_coll_id(),
            ]);
            $stmt->closeCursor();
        }
        catch (\Exception $e) {
            $record = null;
        }

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
     * Insert or update technical data
     * $technicalDatas an array of name => value
     *
     * @param array $technicalDatas
     */
    public function insertOrUpdateTechnicalDatas($technicalDatas)
    {
        $technicalFields = media_subdef::getTechnicalFieldsList();
        $sqlValues = null;

        foreach($technicalDatas as $name => $value){
            if(array_key_exists($name, $technicalFields)){
                if(is_null($value)){
                    $value = 0;
                }
                $sqlValues[] = [$this->getRecordId(), $name, $value, $value];
            }
        }

        if($sqlValues){
            $connection = $this->getDataboxConnection();
            $connection->transactional(function (Connection $connection) use ($sqlValues) {
                $statement = $connection->prepare('INSERT INTO technical_datas (record_id, name, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = ?');
                array_walk($sqlValues, [$statement, 'execute']);
            });

            $this->delete_data_from_cache(self::CACHE_TECHNICAL_DATA);
        }
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

        $sql = "DELETE permalinks FROM subdef INNER JOIN permalinks USING(subdef_id) WHERE record_id=:record_id";
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

        /** @var FeedItemRepository $feedItemRepository */
        $feedItemRepository = $this->app['repo.feed-items'];

        // remove the record from publications
        foreach($feedItemRepository->findBy(['recordId' =>  $this->getRecordId()]) as $feedItem) {
            $this->app['orm.em']->remove($feedItem);
        }

        $this->app['orm.em']->flush();

        $this->app['filesystem']->remove($ftodel);

        // delete cache of subdefs
        $this->delete_data_from_cache(self::CACHE_SUBDEFS);

        // delete the corresponding key record_id from the cache
        $this->delete_data_from_cache();

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
            $sd = $this->get_subdef($subdefname);

            $permalink = $sd->get_permalink();
            if ($permalink instanceof media_Permalink_Adapter) {
                $permalink->delete_data_from_cache();
            }

            $sd->delete_data_from_cache();

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
        $sql = "INSERT INTO log_view (id, log_id, date, record_id, referrer, site_id, coll_id)"
            . " VALUES (null, :log_id, now(), :rec, :referrer, :site, :collid)";

        $params = [
            ':log_id'   => $log_id
            , ':rec'      => $this->getRecordId()
            , ':referrer' => $referrer
            , ':site'     => $gv_sit
            , ':collid'   => $this->getCollectionId()
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

    public static function getRecordsByOriginalnameWithExcludedCollIds(databox $databox, $original_name, $caseSensitive = false, $offset_start = 0, $how_many = 10, $excludedCollIds = [])
    {
        $offset_start = max(0, (int)$offset_start);
        $how_many = max(1, (int)$how_many);
        $collate = $caseSensitive ? 'utf8_bin' : 'utf8_unicode_ci';

        $qb = $databox->get_connection()->createQueryBuilder()
            ->select('record_id')
            ->from('record')
            ->where('originalname = :original_name COLLATE :collate')
            ;

        $params = ['original_name' => $original_name, 'collate' => $collate];
        $types  = [];

        if (!empty($excludedCollIds)) {
            $qb->andWhere($qb->expr()->notIn('coll_id', ':coll_id'));

            $params['coll_id'] = $excludedCollIds;
            $types[':coll_id'] = Connection::PARAM_INT_ARRAY;
        }

        $sql = $qb->setFirstResult($offset_start)
            ->setMaxResults($how_many)
            ->getSQL()
            ;

        $rs = $databox->get_connection()->fetchAll($sql, $params, $types);

        $records = [];
        foreach ($rs as $row) {
            $records[] = $databox->get_record($row['record_id']);
        }

        return $records;
    }

    /**
     * @return set_selection|record_adapter[]
     * @throws Exception
     * @throws DBALException
     * @deprecated use {@link self::getChildren} instead.
     */
    public function get_children()
    {
        return $this->getChildren();
    }

    /**
     * @param int $offset
     * @param null|int $max_items
     *
     * @return set_selection|record_adapter[]
     * @throws Exception
     * @throws DBALException
     */
    public function getChildren($offset = 0, $max_items = null)
    {
        if (!$this->isStory()) {
            throw new Exception('This record is not a grouping');
        }

        $user = $this->getAuthenticatedUser();

        $selections = $this->getDatabox()->getRecordRepository()->findChildren([$this->getRecordId()], $user, $offset, $max_items);

        return reset($selections);
    }

    public function getChildrenCount()
    {
        if (!$this->isStory()) {
            throw new Exception('This record is not a grouping');
        }

        $user = $this->getAuthenticatedUser();

        return $this->getDatabox()->getRecordRepository()->getChildrenCount($this->getRecordId(), $user);
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

    public function hasChild(record_adapter $record)
    {
        return $this->getChildren()->offsetExists($record->getId());
    }

    public function appendChild(record_adapter $record)
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

    public function removeChild(record_adapter $record)
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
        $statusBefore['status'] = [];
        foreach ($this->getStatusStructure() as $bit => $st) {
            $statusBefore['status'][] = [
                'bit'   => $bit,
                'state' => \databox_status::bitIsSet($this->getStatusBitField(), $bit),
            ];
        }

        $this->getDataboxConnection()->executeUpdate(
            'UPDATE record SET moddate = NOW(), status = :status WHERE record_id=:record_id',
            ['status' => bindec($status), 'record_id' => $this->getRecordId()]
        );

        $this->status = str_pad($status, 32, '0', STR_PAD_LEFT);
        // modification date is now unknown, delete from cache to reload on another record
        $this->delete_data_from_cache();

        $newStatus['status'] = [];
        foreach ($this->getStatusStructure() as $bit => $st) {
            $newStatus['status'][] = [
                'bit'   => $bit,
                'state' => \databox_status::bitIsSet($this->getStatusBitField(), $bit),
            ];
        }

        $this->dispatch(RecordEvents::STATUS_CHANGED, new StatusChangedEvent($this, $statusBefore, $newStatus));
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
            'mime'            => $this->mime,
            'sha256'          => $this->sha256,
            'originalName'    => $this->original_name,
            'type'            => $this->type,
            'isStory'         => $this->isStory,
            'cover_record_id' => $this->cover_record_id,
            'uuid'            => $this->uuid,
            'updated'         => $this->updated->format(DATE_ISO8601),
            'created'         => $this->created->format(DATE_ISO8601),
            'base_id'         => $this->base_id,
            'collection_id'   => $this->collection_id,
            'status'          => $this->status,
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
        $this->cover_record_id = is_null($row['cover_record_id']) ? null : (int) $row['cover_record_id'];
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
