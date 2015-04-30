<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Core\Event\Record\RecordEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Core\Event\Record\RecordCollectionChangedEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordCreatedEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordDeletedEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordMetadataChangedEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordOriginalNameChangedEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordStatusChangedEvent;
use Alchemy\Phrasea\Metadata\Tag\TfFilename;
use Alchemy\Phrasea\Metadata\Tag\TfBasename;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Serializer\CaptionSerializer;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;
use MediaVorus\MediaVorus;
use Rhumsaa\Uuid\Uuid;
use Alchemy\Phrasea\Model\RecordInterface;
use Symfony\Component\HttpFoundation\File\File as SymfoFile;
use Alchemy\Phrasea\Core\PhraseaTokens;

class record_adapter implements RecordInterface, cache_cacheableInterface
{
    /**
     *
     * @var <type>
     */
    protected $xml;

    /**
     *
     * @var <type>
     */
    protected $base_id;

    /**
     *
     * @var <type>
     */
    protected $record_id;

    /**
     *
     * @var <type>
     */
    protected $mime;

    /**
     *
     * @var <type>
     */
    protected $number;

    /**
     *
     * @var <type>
     */
    protected $status;

    /**
     *
     * @var <type>
     */
    protected $subdefs;

    /**
     *
     * @var <type>
     */
    protected $type;

    /**
     *
     * @var <type>
     */
    protected $sha256;

    /**
     *
     * @var <type>
     */
    protected $grouping;

    /**
     *
     * @var <type>
     */
    protected $duration;

    /**
     *
     * @var databox
     */
    protected $databox;

    /**
     *
     * @var DateTime
     */
    protected $creation_date;

    /**
     *
     * @var string
     */
    protected $original_name;

    /**
     *
     * @var Array
     */
    protected $technical_datas;

    /**
     *
     * @var caption_record
     */
    protected $caption_record;

    /**
     *
     * @var string
     */
    protected $uuid;

    /**
     *
     * @var DateTime
     */
    protected $modification_date;
    protected $app;

    const CACHE_ORIGINAL_NAME = 'originalname';
    const CACHE_TECHNICAL_DATAS = 'technical_datas';
    const CACHE_MIME = 'mime';
    const CACHE_TITLE = 'title';
    const CACHE_SHA256 = 'sha256';
    const CACHE_SUBDEFS = 'subdefs';
    const CACHE_GROUPING = 'grouping';
    const CACHE_STATUS = 'status';

    /**
     *
     * @param Application $app
     * @param integer     $sbas_id
     * @param integer     $record_id
     * @param integer     $number
     *
     * @return record_adapter
     */
    public function __construct(Application $app, $sbas_id, $record_id, $number = null)
    {
        $this->app = $app;
        $this->databox = $this->app['phraseanet.appbox']->get_databox((int) $sbas_id);
        $this->number = (int) $number;
        $this->record_id = (int) $record_id;

        return $this->load();
    }

    protected function load()
    {
        try {
            $datas = $this->get_data_from_cache();

            $this->mime = $datas['mime'];
            $this->sha256 = $datas['sha256'];
            $this->original_name = $datas['original_name'];
            $this->type = $datas['type'];
            $this->grouping = $datas['grouping'];
            $this->uuid = $datas['uuid'];
            $this->modification_date = $datas['modification_date'];
            $this->creation_date = $datas['creation_date'];
            $this->base_id = $datas['base_id'];

            return $this;
        } catch (\Exception $e) {

        }

        $connbas = $this->databox->get_connection();
        $sql = 'SELECT coll_id, record_id,credate , uuid, moddate, parent_record_id
            , type, originalname, bitly, sha256, mime
            FROM record WHERE record_id = :record_id';
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':record_id' => $this->record_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!$row) {
            throw new Exception_Record_AdapterNotFound('Record ' . $this->record_id . ' on database ' . $this->databox->get_sbas_id() . ' not found ');
        }

        $this->base_id = (int) phrasea::baseFromColl($this->databox->get_sbas_id(), $row['coll_id'], $this->app);
        $this->creation_date = new DateTime($row['credate']);
        $this->modification_date = new DateTime($row['moddate']);
        $this->uuid = $row['uuid'];

        $this->grouping = ($row['parent_record_id'] == '1');
        $this->type = $row['type'];
        $this->original_name = $row['originalname'];
        $this->sha256 = $row['sha256'];
        $this->mime = $row['mime'];

        $datas = [
            'mime'              => $this->mime
            , 'sha256'            => $this->sha256
            , 'original_name'     => $this->original_name
            , 'type'              => $this->type
            , 'grouping'          => $this->grouping
            , 'uuid'              => $this->uuid
            , 'modification_date' => $this->modification_date
            , 'creation_date'     => $this->creation_date
            , 'base_id'           => $this->base_id
        ];

        $this->set_data_to_cache($datas);

        return $this;
    }

    /**
     *
     * @return DateTime
     */
    public function get_creation_date()
    {
        return $this->creation_date;
    }

    /**
     *
     * @return string
     */
    public function get_uuid()
    {
        return $this->uuid;
    }

    /**
     *
     * @return DateTime
     */
    public function get_modification_date()
    {
        return $this->modification_date;
    }

    /**
     *
     * @return int
     */
    public function get_number()
    {
        return $this->number;
    }

    /**
     * Set a relative number (order) for the vurrent in its set
     *
     * @param  int            $number
     * @return record_adapter
     */
    public function set_number($number)
    {
        $this->number = (int) $number;

        return $this;
    }

    /**
     *
     * @param  string         $type
     * @return record_adapter
     */
    public function set_type($type)
    {
        $type = strtolower($type);

        $old_type = $this->get_type();

        if (!in_array($type, ['document', 'audio', 'video', 'image', 'flash', 'map'])) {
            throw new Exception('unrecognized document type');
        }

        $databox = $this->app['phraseanet.appbox']->get_databox($this->get_sbas_id());
        $connbas = $databox->get_connection();

        $sql = 'UPDATE record SET type = :type WHERE record_id = :record_id';
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':type'      => $type, ':record_id' => $this->get_record_id()]);
        $stmt->closeCursor();

        if ($old_type !== $type)
            $this->rebuild_subdefs();

        $this->type = $type;
        $this->delete_data_from_cache();

        return $this;
    }

    public function set_mime($mime)
    {
        $old_mime = $this->get_mime();

        // see http://lists.w3.org/Archives/Public/xml-dist-app/2003Jul/0064.html
        if (!preg_match("/^[a-zA-Z0-9!#$%^&\*_\-\+{}\|'.`~]+\/[a-zA-Z0-9!#$%^&\*_\-\+{}\|'.`~]+$/", $mime)) {
            throw new \Exception(sprintf('Unrecognized mime type %s', $mime));
        }

        $connection = connection::getPDOConnection($this->app, $this->get_sbas_id());

        $sql = 'UPDATE record SET mime = :mime WHERE record_id = :record_id';
        $stmt = $connection->prepare($sql);
        $stmt->execute(array(':mime' => $mime, ':record_id' => $this->get_record_id()));
        $stmt->closeCursor();

        if ($mime !== $old_mime) {
            $this->rebuild_subdefs();
        }

        $this->mime = $mime;
        $this->delete_data_from_cache();

        return $this;
    }

    /**
     * Return true if the record is a grouping
     *
     * @return boolean
     */
    public function is_grouping()
    {
        return $this->grouping;
    }

    /**
     * Return base_id of the record
     *
     * @return <int>
     */
    public function get_base_id()
    {
        return $this->base_id;
    }

    /**
     * Return record collection
     *
     * @return \collection
     */
    public function get_collection()
    {
        return \collection::get_from_base_id($this->app, $this->base_id);
    }

    /**
     * Returns record_id of the record
     *
     * @return <int>
     */
    public function get_record_id()
    {
        return $this->record_id;
    }

    /**
     *
     * @return databox
     */
    public function get_databox()
    {
        return $this->databox;
    }

    /**
     *
     * @return media_subdef
     */
    public function get_thumbnail()
    {
        return $this->get_subdef('thumbnail');
    }

    /**
     *
     * @return Array
     */
    public function get_embedable_medias($devices = null, $mimes = null)
    {
        return $this->getSubdfefByDeviceAndMime($devices, $mimes);
    }

    /**
     * Returns the type of the document
     *
     * @return string
     */
    public function get_type()
    {
        return $this->type;
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
            $this->duration = round($this->get_technical_infos(media_subdef::TC_DATA_DURATION));
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
        if ($this->get_collection()->get_base_id() === $collection->get_base_id()) {
            return $this;
        }

        $sql = "UPDATE record SET coll_id = :coll_id WHERE record_id =:record_id";

        $params = [
            ':coll_id'   => $collection->get_coll_id(),
            ':record_id' => $this->get_record_id()
        ];

        $stmt = $this->get_databox()->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->base_id = $collection->get_base_id();

        $this->app['phraseanet.logger']($this->get_databox())
            ->log($this, Session_Logger::EVENT_MOVE, $collection->get_coll_id(), '');

        $this->delete_data_from_cache();

        $this->dispatch(RecordEvents::COLLECTION_CHANGED, new RecordCollectionChangedEvent($this));

        return $this;
    }

    /**
     *
     * @return media
     */
    public function get_rollover_thumbnail()
    {
        if ($this->get_type() != 'video') {
            return null;
        }

        try {
            return $this->get_subdef('thumbnailGIF');
        } catch (\Exception $e) {

        }

        return null;
    }

    /**
     *
     * @return string
     */
    public function get_sha256()
    {
        return $this->sha256;
    }

    /**
     *
     * @return string
     */
    public function get_mime()
    {
        return $this->mime;
    }

    /**
     *
     * @return string
     */
    public function get_status()
    {
        if (!$this->status) {
            $this->status = $this->retrieve_status();
        }

        return $this->status;
    }

    /**
     *
     * @return string
     */
    protected function retrieve_status()
    {
        try {
            return $this->get_data_from_cache(self::CACHE_STATUS);
        } catch (\Exception $e) {

        }
        $sql = 'SELECT BIN(status) as status FROM record
              WHERE record_id = :record_id';
        $stmt = $this->get_databox()->get_connection()->prepare($sql);
        $stmt->execute([':record_id' => $this->get_record_id()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!$row) {
            throw new Exception('status not found');
        }

        $status = $row['status'];
        $status = str_pad($status, 32, '0', STR_PAD_LEFT);

        $this->set_data_to_cache($status, self::CACHE_STATUS);

        return $status;
    }

    public function has_subdef($name)
    {
        return in_array($name, $this->get_available_subdefs());
    }

    /**
     *
     * @param  <type>       $name
     * @return media_subdef
     */
    public function get_subdef($name)
    {
        $name = strtolower($name);

        if (!in_array($name, $this->get_available_subdefs())) {
            throw new Exception_Media_SubdefNotFound(sprintf("subdef `%s` not found", $name));
        }

        if (isset($this->subdefs[$name])) {
            return $this->subdefs[$name];
        }

        if (!$this->subdefs) {
            $this->subdefs = [];
        }

        $substitute = ($name !== 'document');

        return $this->subdefs[$name] = new media_subdef($this->app, $this, $name, $substitute);
    }

    /**
     * Returns an array of subdef matching
     *
     * @param  string|array $devices the matching device (see databox_subdef::DEVICE_*)
     * @param  type         $mimes   the matching mime types
     * @return array
     */
    public function getSubdfefByDeviceAndMime($devices = null, $mimes = null)
    {
        $subdefNames = $subdefs = [];

        $availableSubdefs = $this->get_subdefs();

        if (isset($availableSubdefs['document'])) {

            $mime_ok = !$mimes || in_array($availableSubdefs['document']->get_mime(), (array) $mimes);
            $devices_ok = !$devices || array_intersect($availableSubdefs['document']->getDevices(), (array) $devices);

            if ($mime_ok && $devices_ok) {
                $subdefs['document'] = $availableSubdefs['document'];
            }
        }

        $searchDevices = array_merge((array) $devices, (array) databox_subdef::DEVICE_ALL);

        $type = $this->is_grouping() ? 'image' : $this->get_type();

        foreach ($this->databox->get_subdef_structure() as $group => $databoxSubdefs) {

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

            if ($mimes && !in_array($subdef->get_mime(), (array) $mimes)) {
                continue;
            }

            $subdefs[$subdef->get_name()] = $subdef;
        }

        return $subdefs;
    }

    /**
     *
     * @return Array
     */
    public function get_subdefs()
    {
        if (!$this->subdefs) {
            $this->subdefs = [];
        }

        $subdefs = $this->get_available_subdefs();
        foreach ($subdefs as $name) {
            $this->get_subdef($name);
        }

        return $this->subdefs;
    }

    /**
     *
     * @return Array
     */
    protected function get_available_subdefs()
    {
        try {
            return $this->get_data_from_cache(self::CACHE_SUBDEFS);
        } catch (\Exception $e) {

        }

        $connbas = $this->get_databox()->get_connection();

        $sql = 'SELECT name FROM record r, subdef s
            WHERE s.record_id = r.record_id AND r.record_id = :record_id';

        $stmt = $connbas->prepare($sql);
        $stmt->execute([':record_id' => $this->get_record_id()]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $subdefs = ['preview', 'thumbnail'];

        foreach ($rs as $row) {
            $subdefs[] = $row['name'];
        }
        $subdefs = array_unique($subdefs);
        $this->set_data_to_cache($subdefs, self::CACHE_SUBDEFS);

        return $subdefs;
    }

    /**
     *
     * @return string
     */
    public function get_collection_logo()
    {
        return collection::getLogo($this->base_id, $this->app, true);
    }

    /**
     *
     * @param  string $data
     * @return Array
     */
    public function get_technical_infos($data = false)
    {
        if (!$this->technical_datas) {
            try {
                $this->technical_datas = $this->get_data_from_cache(self::CACHE_TECHNICAL_DATAS);
            } catch (\Exception $e) {
                $this->technical_datas = [];
                $connbas = $this->get_databox()->get_connection();
                $sql = 'SELECT name, value FROM technical_datas WHERE record_id = :record_id';
                $stmt = $connbas->prepare($sql);
                $stmt->execute([':record_id' => $this->get_record_id()]);
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                foreach ($rs as $row) {
                    switch (true) {
                        case preg_match('/[0-9]?\.[0-9]+/', $row['value']):
                            $this->technical_datas[$row['name']] = (float) $row['value'];
                            break;
                        case ctype_digit($row['value']):
                            $this->technical_datas[$row['name']] = (int) $row['value'];
                            break;
                        default:
                            $this->technical_datas[$row['name']] = $row['value'];
                            break;
                    }
                }
                $this->set_data_to_cache($this->technical_datas, self::CACHE_TECHNICAL_DATAS);
                unset($e);
            }
        }

        if ($data) {
            if (isset($this->technical_datas[$data])) {
                return $this->technical_datas[$data];
            } else {
                return false;
            }
        }

        return $this->technical_datas;
    }

    /**
     *
     * @return caption_record
     */
    public function get_caption()
    {
        return new caption_record($this->app, $this, $this->get_databox());
    }

    public function getCaption()
    {
        $collection = [];

        /** @var caption_field $field */
        foreach ($this->get_caption()->get_fields() as $field) {
            $values = array_map(function(caption_Field_Value $fieldValue) {
                return $fieldValue->getValue();
            }, $field->get_values());

            $collection[$field->get_name()] = $values;
        }

        return $collection;
    }

    /**
     *
     * @return string
     */
    public function get_original_name($removeExtension = null)
    {
        if ($removeExtension) {
            return pathinfo($this->original_name, PATHINFO_FILENAME);
        } else {
            return $this->original_name;
        }
    }

    public function set_original_name($original_name)
    {
        $this->original_name = $original_name;

        foreach ($this->get_databox()->get_meta_structure()->get_elements() as $data_field) {

            if ($data_field->get_tag() instanceof TfFilename) {
                $original_name = pathinfo($original_name, PATHINFO_FILENAME);
            } elseif (!$data_field->get_tag() instanceof TfBasename) {
                continue;
            }

            /**
             * Replacing original name in multi values is non sense
             */
            if (!$data_field->is_multi()) {
                continue;
            }

            try {
                $field = $this->get_caption()->get_field($data_field->get_name())->get_meta_id();
                $values = $field->get_values();
                $value = array_pop($values);
                $meta_id = $value->getId();
            } catch (\Exception $e) {
                $meta_id = null;
            }

            $metas = [
                'meta_struct_id' => $field->get_meta_struct_id()
                , 'meta_id'        => $meta_id
                , 'value'          => $original_name
            ];

            $this->set_metadatas($metas, true);
        }

        $sql = 'UPDATE record
            SET originalname = :originalname WHERE record_id = :record_id';

        $params = [
            ':originalname' => $original_name
            , ':record_id'    => $this->get_record_id()
        ];

        $stmt = $this->get_databox()->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->delete_data_from_cache();

        $this->dispatch(RecordEvents::ORIGINAL_NAME_CHANGED, new RecordOriginalNameChangedEvent($this));

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_title($highlight = false, SearchEngineInterface $searchEngine = null, $removeExtension = null, SearchEngineOptions $options = null)
    {
        $cache = !$highlight && !$searchEngine && !$removeExtension;

        if ($cache) {
            try {
                return $this->get_data_from_cache(self::CACHE_TITLE);
            } catch (\Exception $e) {

            }
        }

        $title = '';

        $fields = $this->get_databox()->get_meta_structure();

        $fields_to_retrieve = [];

        foreach ($fields as $field) {
            if (in_array($field->get_thumbtitle(), ['1', $this->app['locale']])) {
                $fields_to_retrieve [] = $field->get_name();
            }
        }

        if (count($fields_to_retrieve) > 0) {
            $retrieved_fields = $this->get_caption()->get_highlight_fields($highlight, $fields_to_retrieve, $searchEngine);
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
     *
     * @return media_subdef
     */
    public function get_preview()
    {
        return $this->get_subdef('preview');
    }

    /**
     *
     * @return boolean
     */
    public function has_preview()
    {
        try {
            $this->get_subdef('preview');

            return $this->get_subdef('preview')->is_physically_present();
        } catch (\Exception $e) {
            unset($e);
        }

        return false;
    }

    /**
     *
     * @return string
     */
    public function get_serialize_key()
    {
        return $this->get_sbas_id() . '_' . $this->get_record_id();
    }

    /**
     *
     * @return int
     */
    public function get_sbas_id()
    {
        return $this->get_databox()->get_sbas_id();
    }

    public function substitute_subdef($name, MediaInterface $media, Application $app, $adapt=true)
    {
        $newfilename = $this->record_id . '_0_' . $name . '.' . $media->getFile()->getExtension();

        if ($name == 'document') {
            $baseprefs = $this->get_databox()->get_sxml_structure();

            $pathhd = p4string::addEndSlash((string) ($baseprefs->path));

            $filehd = $this->get_record_id() . "_document." . strtolower($media->getFile()->getExtension());
            $pathhd = databox::dispatch($app['filesystem'], $pathhd);

            $app['filesystem']->copy($media->getFile()->getRealPath(), $pathhd . $filehd, true);

            $subdefFile = $pathhd . $filehd;

            $meta_writable = true;
        } else {
            $type = $this->is_grouping() ? 'image' : $this->get_type();

            $subdef_def = $this->get_databox()->get_subdef_structure()->get_subdef($type, $name);

            if ($this->has_subdef($name) && $this->get_subdef($name)->is_physically_present()) {

                $path_file_dest = $this->get_subdef($name)->get_pathfile();
                $this->get_subdef($name)->remove_file();
                $this->clearSubdefCache($name);
            } else {
                $path = databox::dispatch($app['filesystem'], $subdef_def->get_path());
                $app['filesystem']->mkdir($path, 0750);
                $path_file_dest = $path . $newfilename;
            }

            if($adapt) {
                try {
                    $app['media-alchemyst']->turnInto(
                        $media->getFile()->getRealPath(),
                        $path_file_dest,
                        $subdef_def->getSpecs()
                    );
                } catch (\MediaAlchemyst\Exception\ExceptionInterface $e) {
                    return $this;
                }

                $subdefFile = $path_file_dest;
            }
            else{
                $app['filesystem']->copy($media->getFile()->getRealPath(), $path_file_dest);

                $subdefFile = $path_file_dest;
            }

            $meta_writable = $subdef_def->meta_writeable();
        }

        $app['filesystem']->chmod($subdefFile, 0760);
        $media = $app['mediavorus']->guess($subdefFile);
        $subdef = media_subdef::create($app, $this, $name, $media);
        $subdef->set_substituted(true);

        $this->delete_data_from_cache(self::CACHE_SUBDEFS);

        if ($meta_writable) {
            $this->write_metas();
        }

        if ($name == 'document' && $adapt) {
            $this->rebuild_subdefs();
        }

        return $this;
    }

    /**
     *
     * @param  DOMDocument    $dom_doc
     * @return record_adapter
     */
    protected function set_xml(DOMDocument $dom_doc)
    {
        $connbas = $this->get_databox()->get_connection();
        $sql = 'UPDATE record SET xml = :xml WHERE record_id= :record_id';
        $stmt = $connbas->prepare($sql);
        $stmt->execute(
            [
                ':xml'       => $dom_doc->saveXML(),
                ':record_id' => $this->record_id
            ]
        );
        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @todo move this function to caption_record
     * @param  Array          $params An array containing three keys : meta_struct_id (int) , meta_id (int or null) and value (Array)
     * @return record_adapter
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

        $databox_field = databox_field::get_instance($this->app, $databox, $params['meta_struct_id']);

        $caption_field = new caption_field($this->app, $databox_field, $this);

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

        $this->caption_record = null;

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
    public function set_metadatas(Array $metadatas, $force_readonly = false)
    {
        foreach ($metadatas as $param) {
            if (!is_array($param)) {
                throw new Exception_InvalidArgument('Invalid metadatas argument');
            }

            $db_field = \databox_field::get_instance($this->app, $this->get_databox(), $param['meta_struct_id']);

            if ($db_field->is_readonly() === true && !$force_readonly) {
                continue;
            }

            $this->set_metadata($param, $this->databox);
        }

        $this->xml = null;
        $this->caption_record = null;

        $xml = new DOMDocument();
        $xml->loadXML($this->app['serializer.caption']->serialize($this->get_caption(), CaptionSerializer::SERIALIZE_XML, true));

        $this->set_xml($xml);
        unset($xml);

        $this->dispatch(RecordEvents::METADATA_CHANGED, new RecordMetadataChangedEvent($this));

        return $this;
    }

    /**
     *
     * @return record_adapter
     */
    public function rebuild_subdefs()
    {
        $databox = $this->app['phraseanet.appbox']->get_databox($this->get_sbas_id());
        $connbas = $databox->get_connection();
        $sql = 'UPDATE record SET jeton=(jeton | ' . PhraseaTokens::MAKE_SUBDEF . ') WHERE record_id = :record_id';
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':record_id' => $this->get_record_id()]);
        $stmt->closeCursor();

        return $this;
    }

    public function get_missing_subdefs()
    {
        $databox = $this->get_databox();

        try {
            $this->get_hd_file();
        } catch (\Exception $e) {
            return array();
        }

        $subDefDefinitions = $databox->get_subdef_structure()->getSubdefGroup($this->get_type());
        if (!$subDefDefinitions) {
            return array();
        }

        $record = $this;
        $wanted_subdefs = array_map(function($subDef) {
           return  $subDef->get_name();
        }, array_filter($subDefDefinitions, function($subDef) use ($record) {
            return !$record->has_subdef($subDef->get_name());
        }));


        $missing_subdefs = array_map(function($subDef) {
            return $subDef->get_name();
        }, array_filter($this->get_subdefs(), function($subdef) {
            return !$subdef->is_physically_present();
        }));

        return array_values(array_merge($wanted_subdefs, $missing_subdefs));
    }

    /**
     *
     * @return record_adapter
     */
    public function write_metas()
    {
        $databox = $this->app['phraseanet.appbox']->get_databox($this->get_sbas_id());
        $connbas = $databox->get_connection();
        $sql = 'UPDATE record
            SET jeton = jeton | (' . (PhraseaTokens::WRITE_META_DOC | PhraseaTokens::WRITE_META_SUBDEF) . ')
            WHERE record_id= :record_id';
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':record_id' => $this->record_id]);
        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @param  string         $status
     * @return record_adapter
     */
    public function set_binary_status($status)
    {
        $databox = $this->app['phraseanet.appbox']->get_databox($this->get_sbas_id());
        $connbas = $databox->get_connection();

        $sql = 'UPDATE record SET status = 0b' . $status . '
            WHERE record_id= :record_id';
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':record_id' => $this->record_id]);
        $stmt->closeCursor();

        $sql = 'REPLACE INTO status (id, record_id, name, value) VALUES (null, :record_id, :name, :value)';
        $stmt = $connbas->prepare($sql);

        $status = strrev($status);
        $length = strlen($status);
        for ($i = 4; $i < $length; $i++) {
            $stmt->execute([
                ':record_id' => $this->get_record_id(),
                ':name'      => $i,
                ':value'     => $status[$i]
            ]);
        }
        $stmt->closeCursor();

        $this->delete_data_from_cache(self::CACHE_STATUS);

        $this->dispatch(RecordEvents::STATUS_CHANGED, new RecordStatusChangedEvent($this));

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
        $databox = $collection->get_databox();

        $sql = 'INSERT INTO record
              (coll_id, record_id, parent_record_id, moddate, credate
                , type, sha256, uuid, originalname, mime)
            VALUES
              (:coll_id, null, :parent_record_id, NOW(), NOW()
              , :type, :sha256, :uuid
              , :originalname, :mime)';

        $stmt = $databox->get_connection()->prepare($sql);

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

        $story_id = $databox->get_connection()->lastInsertId();

        $story = new self($app, $databox->get_sbas_id(), $story_id);

        try {
            $log_id = $app['phraseanet.logger']($databox)->get_id();

            $sql = 'INSERT INTO log_docs (id, log_id, date, record_id, action, final, comment)
            VALUES (null, :log_id, now(),
              :record_id, "add", :coll_id,"")';
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute([
                ':log_id'    => $log_id,
                ':record_id' => $story_id,
                ':coll_id'   => $collection->get_coll_id()
            ]);
            $stmt->closeCursor();
        } catch (\Exception $e) {
            unset($e);
        }

        self::dispatchCreatedEvent($app, $story);

        return $story;
    }

    /**
     *
     * @param File        $file
     * @param Application $app
     *
     * @return \record_adapter
     */
    public static function createFromFile(File $file, Application $app)
    {
        $databox = $file->getCollection()->get_databox();

        $sql = 'INSERT INTO record
              (coll_id, record_id, parent_record_id, moddate, credate
                , type, sha256, uuid, originalname, mime)
            VALUES
              (:coll_id, null, :parent_record_id, NOW(), NOW()
              , :type, :sha256, :uuid
              , :originalname, :mime)';

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

            $sql = 'INSERT INTO log_docs (id, log_id, date, record_id, action, final, comment)
            VALUES (null, :log_id, now(),
              :record_id, "add", :coll_id,"")';
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute([
                ':log_id'    => $log_id,
                ':record_id' => $record_id,
                ':coll_id'   => $file->getCollection()->get_coll_id()
            ]);
            $stmt->closeCursor();
        } catch (\Exception $e) {
            unset($e);
        }

        $pathhd = databox::dispatch($app['filesystem'], trim($databox->get_sxml_structure()->path));
        $newname = $record->get_record_id() . "_document." . pathinfo($file->getOriginalName(), PATHINFO_EXTENSION);

        $app['filesystem']->copy($file->getFile()->getRealPath(), $pathhd . $newname, true);

        $media = $app['mediavorus']->guess($pathhd . $newname);
        media_subdef::create($app, $record, 'document', $media);

        $record->delete_data_from_cache(\record_adapter::CACHE_SUBDEFS);

        $record->insertTechnicalDatas($app['mediavorus']);

        self::dispatchCreatedEvent($app, $record);

        return $record;
    }

    private static function dispatchCreatedEvent(Application $app, RecordInterface $record)
    {
        $app['dispatcher']->dispatch(RecordEvents::CREATED, new RecordCreatedEvent($record));
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

        $sql = 'DELETE FROM technical_datas WHERE record_id = :record_id';
        $stmt = $this->get_databox()->get_connection()->prepare($sql);
        $stmt->execute([':record_id' => $this->get_record_id()]);
        $stmt->closeCursor();

        $sql = 'INSERT INTO technical_datas (id, record_id, name, value)
        VALUES (null, :record_id, :name, :value)';
        $stmt = $this->get_databox()->get_connection()->prepare($sql);

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

            $stmt->execute([
                ':record_id' => $this->get_record_id()
                , ':name'      => $name
                , ':value'     => $value
            ]);
        }

        $stmt->closeCursor();

        $this->delete_data_from_cache(self::CACHE_TECHNICAL_DATAS);

        return $this;
    }

    /**
     *
     * @param  Application    $app
     * @param  integer        $sbas_id
     * @param  string         $sha256
     * @param  integer        $record_id
     * @return record_adapter
     */
    public static function get_record_by_sha(Application $app, $sbas_id, $sha256, $record_id = null)
    {
        $databox = $app['phraseanet.appbox']->get_databox($sbas_id);
        $conn = $databox->get_connection();

        $sql = "SELECT record_id
            FROM record r
            WHERE sha256 IS NOT NULL
              AND sha256 = :sha256";

        $params = [':sha256' => $sha256];

        if (!is_null($record_id)) {
            $sql .= ' AND record_id = :record_id';
            $params[':record_id'] = $record_id;
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $records = [];

        foreach ($rs as $row) {
            $records[] = new record_adapter($app, $sbas_id, $row['record_id']);
        }

        return $records;
    }

    /**
     * Search for a record on a databox by UUID
     *
     * @param Application $app
     * @param \databox    $databox
     * @param string      $uuid
     * @param int         $record_id Restrict check on a record_id
     *
     * @return \record_adapter
     */
    public static function get_record_by_uuid(Application $app, \databox $databox, $uuid, $record_id = null)
    {
        $sql = "SELECT record_id FROM record r
                WHERE uuid IS NOT NULL AND uuid = :uuid";

        $params = [':uuid' => $uuid];

        if (!is_null($record_id)) {
            $sql .= ' AND record_id = :record_id';
            $params[':record_id'] = $record_id;
        }

        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $records = [];

        foreach ($rs as $row) {
            $records[] = new record_adapter($app, $databox->get_sbas_id(), $row['record_id']);
        }

        return $records;
    }

    /**
     *
     * @return \Symfony\Component\HttpFoundation\File\File|null
     */
    public function get_hd_file()
    {
        $hd = $this->get_subdef('document');

        if ($hd->is_physically_present()) {
            return new SymfoFile($hd->get_pathfile());
        }

        return null;
    }

    /**
     *
     * @return Array : list of deleted files;
     */
    public function delete()
    {
        $connbas = $this->get_databox()->get_connection();

        $ftodel = [];
        foreach ($this->get_subdefs() as $subdef) {
            if (!$subdef->is_physically_present())
                continue;

            if ($subdef->get_name() === 'thumbnail') {
                $this->app['filesystem']->remove($this->app['phraseanet.thumb-symlinker']->getSymlinkPath($subdef->get_pathfile()));
            }

            $ftodel[] = $subdef->get_pathfile();
            $watermark = $subdef->get_path() . 'watermark_' . $subdef->get_file();
            if (file_exists($watermark))
                $ftodel[] = $watermark;
            $stamp = $subdef->get_path() . 'stamp_' . $subdef->get_file();
            if (file_exists($stamp))
                $ftodel[] = $stamp;
        }

        $origcoll = phrasea::collFromBas($this->app, $this->get_base_id());

        $xml = $this->app['serializer.caption']->serialize($this->get_caption(), CaptionSerializer::SERIALIZE_XML);

        $this->app['phraseanet.logger']($this->get_databox())
            ->log($this, Session_Logger::EVENT_DELETE, $origcoll, $xml);

        $sql = "DELETE FROM record WHERE record_id = :record_id";
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':record_id' => $this->get_record_id()]);
        $stmt->closeCursor();

        $sql = "DELETE FROM metadatas WHERE record_id = :record_id";
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':record_id' => $this->get_record_id()]);
        $stmt->closeCursor();

        $sql = "DELETE FROM prop WHERE record_id = :record_id";
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':record_id' => $this->get_record_id()]);
        $stmt->closeCursor();

        $sql = "DELETE FROM idx WHERE record_id = :record_id";
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':record_id' => $this->get_record_id()]);
        $stmt->closeCursor();

        $sql = "DELETE FROM permalinks
            WHERE subdef_id
              IN (SELECT subdef_id FROM subdef WHERE record_id=:record_id)";
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':record_id' => $this->get_record_id()]);
        $stmt->closeCursor();

        $sql = "DELETE FROM subdef WHERE record_id = :record_id";
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':record_id' => $this->get_record_id()]);
        $stmt->closeCursor();

        $sql = "DELETE FROM technical_datas WHERE record_id = :record_id";
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':record_id' => $this->get_record_id()]);
        $stmt->closeCursor();

        $sql = "DELETE FROM thit WHERE record_id = :record_id";
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':record_id' => $this->get_record_id()]);
        $stmt->closeCursor();

        $sql = "DELETE FROM regroup WHERE rid_parent = :record_id";
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':record_id' => $this->get_record_id()]);
        $stmt->closeCursor();

        $sql = "DELETE FROM regroup WHERE rid_child = :record_id";
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':record_id' => $this->get_record_id()]);
        $stmt->closeCursor();

        $orderElementRepository = $this->app['repo.order-elements'];

        /* @var $repository Alchemy\Phrasea\Model\Repositories\OrderElementRepository */
        foreach ($orderElementRepository->findBy(['recordId' => $this->get_record_id()]) as $order_element) {
            if ($order_element->getSbasId($this->app) == $this->get_sbas_id()) {
                $this->app['orm.em']->remove($order_element);
            }
        }

        $basketElementRepository = $this->app['repo.basket-elements'];

        /* @var $repository Alchemy\Phrasea\Model\Repositories\BasketElementRepository */
        foreach ($basketElementRepository->findElementsByRecord($this) as $basket_element) {
            $this->app['orm.em']->remove($basket_element);
        }

        $this->app['orm.em']->flush();

        $this->app['filesystem']->remove($ftodel);

        $this->delete_data_from_cache(self::CACHE_SUBDEFS);

        $this->dispatch(RecordEvents::DELETED, new RecordDeletedEvent($this));

        return array_keys($ftodel);
    }

    /**
     *
     * @param  string $option optionnal cache name
     * @return string
     */
    public function get_cache_key($option = null)
    {
        return 'record_' . $this->get_serialize_key() . ($option ? '_' . $option : '');
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
     *
     * @param  string $option optionnal cache name
     * @return mixed
     */
    public function get_data_from_cache($option = null)
    {
        $databox = $this->get_databox();

        return $databox->get_data_from_cache($this->get_cache_key($option));
    }

    public function set_data_to_cache($value, $option = null, $duration = 0)
    {
        $databox = $this->get_databox();

        return $databox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
    }

    public function delete_data_from_cache($option = null)
    {
        switch ($option) {
            case self::CACHE_STATUS:
                $this->status = null;
                break;
            case self::CACHE_SUBDEFS:
                $this->subdefs = null;
                break;
            default:
                break;
        }
        $databox = $this->get_databox();

        return $databox->delete_data_from_cache($this->get_cache_key($option));
    }

    public function log_view($log_id, $referrer, $gv_sit)
    {
        $databox = $this->app['phraseanet.appbox']->get_databox($this->get_sbas_id());
        $connbas = $databox->get_connection();

        $sql = 'INSERT INTO log_view (id, log_id, date, record_id, referrer, site_id)
            VALUES
            (null, :log_id, now(), :rec, :referrer, :site)';

        $params = [
            ':log_id'   => $log_id
            , ':rec'      => $this->get_record_id()
            , ':referrer' => $referrer
            , ':site'     => $gv_sit
        ];
        $stmt = $connbas->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @var Array
     */
    protected $container_basket;

    /**
     * @todo de meme avec stories
     * @return Array
     */
    public function get_container_baskets(EntityManager $em, User $user)
    {
        return $em
                ->getRepository('Phraseanet:Basket')
                ->findContainingRecordForUser($this, $user);
    }

    /**
     *
     * @param databox $databox
     * @param integer $original_name
     * @param integer $caseSensitive
     * @param integer $offset_start
     * @param integer $how_many
     *
     * @return array
     */
    public static function get_records_by_originalname(databox $databox, $original_name, $caseSensitive = false, $offset_start = 0, $how_many = 10)
    {
        $offset_start = (int) ($offset_start < 0 ? 0 : $offset_start);
        $how_many = (int) (($how_many > 20 || $how_many < 1) ? 10 : $how_many);

        $sql = sprintf('SELECT record_id FROM record
            WHERE originalname = :original_name '
            . ($caseSensitive ? 'COLLATE utf8_bin' : 'COLLATE utf8_unicode_ci')
            . ' LIMIT %d, %d'
            , $offset_start, $how_many);

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
     *
     * @return set_selection
     */
    public function get_children()
    {
        if (!$this->is_grouping()) {
            throw new Exception('This record is not a grouping');
        }

        if ($this->app['authentication']->getUser()) {
            $sql = 'SELECT record_id
              FROM regroup g
                INNER JOIN (record r
                  INNER JOIN collusr c
                    ON site = :site
                      AND usr_id = :usr_id
                      AND c.coll_id = r.coll_id
                      AND ((status ^ mask_xor) & mask_and) = 0
                      AND r.parent_record_id=0
                )
                ON (g.rid_child = r.record_id AND g.rid_parent = :record_id)
              ORDER BY g.ord ASC, dateadd ASC, record_id ASC';

            $params = [
                ':site'   => $this->app['conf']->get(['main', 'key']),
                ':usr_id'    => $this->app['authentication']->getUser()->getId(),
                ':record_id' => $this->get_record_id(),
            ];
        } else {
            $sql = 'SELECT record_id
              FROM regroup g
                INNER JOIN record r
                ON (g.rid_child = r.record_id AND g.rid_parent = :record_id)
              ORDER BY g.ord ASC, dateadd ASC, record_id ASC';

            $params = [
                ':record_id' => $this->get_record_id()
            ];
        }

        $stmt = $this->get_databox()->get_connection()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $set = new set_selection($this->app);
        $i = 1;
        foreach ($rs as $row) {
            $set->add_element(new record_adapter($this->app, $this->get_sbas_id(), $row['record_id'], $i));
            $i++;
        }

        return $set;
    }

    /**
     *
     * @return set_selection
     */
    public function get_grouping_parents()
    {
        $sql = 'SELECT r.record_id
            FROM regroup g
              INNER JOIN (record r
                INNER JOIN collusr c
                  ON site = :site
                    AND usr_id = :usr_id
                    AND c.coll_id = r.coll_id
                    AND ((status ^ mask_xor) & mask_and)=0
                    AND r.parent_record_id = 1
              )
              ON (g.rid_parent = r.record_id)
            WHERE rid_child = :record_id';

        $stmt = $this->get_databox()->get_connection()->prepare($sql);
        $stmt->execute([
            ':site'      => $this->app['conf']->get(['main', 'key']),
            ':usr_id'    => $this->app['authentication']->getUser()->getId(),
            ':record_id' => $this->get_record_id(),
        ]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $set = new set_selection($this->app);
        foreach ($rs as $row) {
            $set->add_element(new record_adapter($this->app, $this->get_sbas_id(), $row['record_id']));
        }

        return $set;
    }

    public function hasChild(\record_adapter $record)
    {
        return $this->get_children()->offsetExists($record->get_serialize_key());
    }

    public function appendChild(\record_adapter $record)
    {
        if (!$this->is_grouping()) {
            throw new \Exception('Only stories can append children');
        }

        $connbas = $this->get_databox()->get_connection();

        $ord = 0;

        $sql = "SELECT (max(ord)+1) as ord
            FROM regroup WHERE rid_parent = :parent_record_id";

        $stmt = $connbas->prepare($sql);

        $stmt->execute([':parent_record_id' => $this->get_record_id()]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt->closeCursor();

        if ($row) {
            $ord = is_null($row["ord"]) ? 0 : $row["ord"];
        }

        $sql = 'INSERT INTO regroup (id, rid_parent, rid_child, dateadd, ord)
              VALUES (null, :parent_record_id, :record_id, NOW(), :ord)';

        $params = [
            ':parent_record_id' => $this->get_record_id()
            , ':record_id'        => $record->get_record_id()
            , ':ord'              => $ord
        ];

        $stmt = $connbas->prepare($sql);
        $stmt->execute($params);

        $stmt->closeCursor();

        $sql = 'UPDATE record SET moddate = NOW() WHERE record_id = :record_id';
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':record_id' => $this->get_record_id()]);
        $stmt->closeCursor();

        $this->delete_data_from_cache();

        return $this;
    }

    public function removeChild(\record_adapter $record)
    {
        if (!$this->is_grouping()) {
            throw new \Exception('Only stories can append children');
        }

        $connbas = $this->get_databox()->get_connection();

        $sql = "DELETE FROM regroup WHERE rid_parent = :parent_record_id
                  AND rid_child = :record_id";

        $params = [
            ':parent_record_id' => $this->get_record_id()
            , ':record_id'        => $record->get_record_id()
        ];

        $stmt = $connbas->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $sql = 'UPDATE record SET moddate = NOW() WHERE record_id = :record_id';
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':record_id' => $this->get_record_id()]);
        $stmt->closeCursor();

        $this->delete_data_from_cache();

        return $this;
    }

    /** {@inheritdoc} */
    public function getBaseId()
    {
        return $this->get_base_id();
    }

    /** {@inheritdoc} */
    public function getCollectionId()
    {
        return $this->get_collection()->get_coll_id();
    }

    /** {@inheritdoc} */
    public function getCreated()
    {
        return $this->get_creation_date();
    }

    /** {@inheritdoc} */
    public function getDataboxId()
    {
        return $this->get_databox()->get_sbas_id();
    }

    /** {@inheritdoc} */
    public function isStory()
    {
        return $this->is_grouping();
    }

    /** {@inheritdoc} */
    public function getMimeType()
    {
        return $this->get_mime();
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
    public function getRecordId()
    {
        return $this->get_record_id();
    }

    /** {@inheritdoc} */
    public function getSha256()
    {
        return $this->get_sha256();
    }

    /** {@inheritdoc} */
    public function getType()
    {
        return $this->get_type();
    }

    /** {@inheritdoc} */
    public function getUpdated()
    {
        return $this->get_modification_date();
    }

    /** {@inheritdoc} */
    public function getUuid()
    {
        return $this->get_uuid();
    }

    /** {@inheritdoc} */
    public function getId()
    {
        return $this->get_serialize_key();
    }

    public function setStatus($status)
    {
        $this->set_binary_status($status);

        $this->delete_data_from_cache(self::CACHE_STATUS);
    }

    /** {@inheritdoc} */
    public function getStatusBitField()
    {
        return bindec($this->get_status());
    }

    /** {@inheritdoc} */
    public function getExif()
    {
        return $this->get_technical_infos();
    }

    public function getStatusStructure()
    {
        return $this->databox->getStatusStructure();
    }
}
