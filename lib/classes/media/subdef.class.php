<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use MediaVorus\MediaVorus;
use MediaVorus\Media\Media;

/**
 *
 * @package     subdefs
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class media_subdef extends media_abstract implements cache_cacheableInterface
{
    /**
     *
     * @var string
     */
    protected $mime;

    /**
     *
     * @var string
     */
    protected $file;

    /**
     *
     * @var string
     */
    protected $path;

    /**
     *
     * @var record_adapter
     */
    protected $record;

    /**
     *
     * @var media_Permalink_Adapter
     */
    protected $permalink;

    /**
     *
     * @var boolean
     */
    protected $is_substituted = false;

    /**
     *
     * @var string
     */
    protected $pathfile;

    /**
     *
     * @var int
     */
    protected $subdef_id;

    /**
     *
     * @var string
     */
    protected $name;

    /**
     *
     * @var string
     */
    protected $etag;

    /**
     *
     * @var DateTime
     */
    protected $creation_date;

    /**
     *
     * @var DateTime
     */
    protected $modification_date;

    /**
     *
     * @var boolean
     */
    protected $is_physically_present = false;

    /**
     * Players types constants
     */
    const TYPE_VIDEO_MP4 = 'VIDEO_MP4';
    const TYPE_VIDEO_FLV = 'VIDEO_FLV';
    const TYPE_FLEXPAPER = 'FLEXPAPER';
    const TYPE_AUDIO_MP3 = 'AUDIO_MP3';
    const TYPE_IMAGE = 'IMAGE';
    const TYPE_NO_PLAYER = 'UNKNOWN';
    /**
     * Technical datas types constants
     */
    const TC_DATA_WIDTH = 'Width';
    const TC_DATA_HEIGHT = 'Height';
    const TC_DATA_COLORSPACE = 'ColorSpace';
    const TC_DATA_CHANNELS = 'Channels';
    const TC_DATA_ORIENTATION = 'Orientation';
    const TC_DATA_COLORDEPTH = 'ColorDepth';
    const TC_DATA_DURATION = 'Duration';
    const TC_DATA_AUDIOCODEC = 'AudioCodec';
    const TC_DATA_AUDIOSAMPLERATE = 'AudioSamplerate';
    const TC_DATA_VIDEOCODEC = 'VideoCodec';
    const TC_DATA_FRAMERATE = 'FrameRate';
    const TC_DATA_MIMETYPE = 'MimeType';
    const TC_DATA_FILESIZE = 'FileSize';
    const TC_DATA_LONGITUDE = 'Longitude';
    const TC_DATA_LATITUDE = 'Latitude';
    const TC_DATA_FOCALLENGTH = 'FocalLength';
    const TC_DATA_CAMERAMODEL = 'CameraModel';
    const TC_DATA_FLASHFIRED = 'FlashFired';
    const TC_DATA_APERTURE = 'Aperture';
    const TC_DATA_SHUTTERSPEED = 'ShutterSpeed';
    const TC_DATA_HYPERFOCALDISTANCE = 'HyperfocalDistance';
    const TC_DATA_ISO = 'ISO';
    const TC_DATA_LIGHTVALUE = 'LightValue';

    /**
     * @todo    the presence of file is checked on constructor, it would be better
     *          to check it when needed (stop disk access)
     *
     * @param  record_adapter $record
     * @param  type           $name
     * @param  type           $substitute
     * @return media_subdef
     */
    public function __construct(record_adapter &$record, $name, $substitute = false)
    {
        $this->name = $name;
        $this->record = $record;
        $this->load($substitute);

        $this->generate_url();

        return $this;
    }

    /**
     *
     * @param  boolean      $substitute
     * @return media_subdef
     */
    protected function load($substitute)
    {
        try {
            $datas = $this->get_data_from_cache();
            $this->mime = $datas['mime'];
            $this->width = $datas['width'];
            $this->height = $datas['height'];
            $this->etag = $datas['etag'];
            $this->path = $datas['path'];
            $this->url = $datas['url'];
            $this->file = $datas['file'];
            $this->is_physically_present = $datas['physically_present'];
            $this->is_substituted = $datas['is_substituted'];
            $this->subdef_id = $datas['subdef_id'];
            $this->modification_date = $datas['modification_date'];
            $this->creation_date = $datas['creation_date'];

            return $this;
        } catch (Exception $e) {

        }

        $connbas = $this->record->get_databox()->get_connection();

        $sql = 'SELECT subdef_id, name, file, width, height, mime,
                path, size, substit, created_on, updated_on, etag
                FROM subdef
                WHERE name = :name AND record_id = :record_id';

        $params = array(
            ':record_id' => $this->record->get_record_id(),
            ':name'      => $this->name
        );

        $stmt = $connbas->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row) {

            $this->width = (int) $row['width'];
            $this->height = (int) $row['height'];
            $this->mime = $row['mime'];
            $this->file = $row['file'];
            $this->etag = $row['etag'];
            $this->path = p4string::addEndSlash($row['path']);
            $this->is_substituted = ! ! $row['substit'];
            $this->subdef_id = (int) $row['subdef_id'];

            if ($row['updated_on'])
                $this->modification_date = new DateTime($row['updated_on']);
            if ($row['created_on'])
                $this->creation_date = new DateTime($row['created_on']);

            $this->is_physically_present = true;
        } elseif ($substitute === false) {
            throw new Exception_Media_SubdefNotFound($this->name . ' not found');
        }

        if ( ! $row) {
            $this->find_substitute_file();
        }

        $datas = array(
            'mime'               => $this->mime
            , 'width'              => $this->width
            , 'height'             => $this->height
            , 'etag'               => $this->etag
            , 'path'               => $this->path
            , 'url'                => $this->url
            , 'file'               => $this->file
            , 'physically_present' => $this->is_physically_present
            , 'is_substituted'     => $this->is_substituted
            , 'subdef_id'          => $this->subdef_id
            , 'modification_date'  => $this->modification_date
            , 'creation_date'      => $this->creation_date
        );

        $this->set_data_to_cache($datas);

        return $this;
    }

    /**
     * Removes the file associated to a subdef
     *
     * @return \media_subdef
     */
    public function remove_file()
    {
        if ($this->is_physically_present() && is_writable($this->get_pathfile())) {
            unlink($this->get_pathfile());

            $this->delete_data_from_cache();

            if ($this->get_permalink() instanceof media_Permalink_Adapter) {
                $this->get_permalink()->delete_data_from_cache();
            }

            $this->find_substitute_file();
        }

        return $this;
    }

    /**
     * Find a substitution file for a sibdef
     *
     * @return \media_subdef
     */
    protected function find_substitute_file()
    {
        $registry = $this->record->get_databox()->get_registry();

        if ($this->record->is_grouping()) {
            $this->mime = 'image/png';
            $this->width = 256;
            $this->height = 256;
            $this->path = $registry->get('GV_RootPath') . 'www/skins/icons/substitution/';
            $this->file = 'regroup_thumb.png';
            $this->url = '/skins/icons/substitution/regroup_thumb.png';
        } else {
            $mime = $this->record->get_mime();
            $mime = trim($mime) != '' ? str_replace('/', '_', $mime) : 'application_octet-stream';

            $this->mime = 'image/png';
            $this->width = 256;
            $this->height = 256;
            $this->path = $registry->get('GV_RootPath') . 'www/skins/icons/substitution/';
            $this->file = str_replace('+', '%20', $mime) . '.png';
            $this->url = '/skins/icons/substitution/' . $this->file;
        }

        $this->is_physically_present = false;

        if ( ! file_exists($this->path . $this->file)) {
            $this->path = $registry->get('GV_RootPath') . 'www/skins/icons/';
            $this->file = 'substitution.png';
            $this->url = '/skins/icons/' . $this->file;
        }

        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function is_physically_present()
    {
        return $this->is_physically_present;
    }

    /**
     *
     * @return record_adapter
     */
    public function get_record()
    {
        return $this->record;
    }

    /**
     *
     * @return media_Permalink_Adapter
     */
    public function get_permalink()
    {
        if ( ! $this->permalink && $this->is_physically_present())
            $this->permalink = media_Permalink_Adapter::getPermalink($this->record->get_databox(), $this);

        return $this->permalink;
    }

    /**
     *
     * @return int
     */
    public function get_record_id()
    {
        return $this->record->get_record_id();
    }

    public function getEtag()
    {
        if ( ! $this->etag && $this->is_physically_present()) {
            $this->setEtag(md5(time() . $this->get_pathfile()));
        }

        return $this->etag;
    }

    public function setEtag($etag)
    {
        $this->etag = $etag;

        $sql = "UPDATE subdef SET etag = :etag WHERE subdef_id = :subdef_id";
        $stmt = $this->record->get_databox()->get_connection()->prepare($sql);
        $stmt->execute(array(':subdef_id' => $this->subdef_id, ':etag'      => $etag));
        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @return int
     */
    public function get_sbas_id()
    {
        return $this->record->get_sbas_id();
    }

    /**
     *
     * @return string
     */
    public function get_type()
    {
        switch ($this->mime) {
            case 'video/mp4':
                $type = self::TYPE_VIDEO_MP4;
                break;
            case 'video/x-flv':
                $type = self::TYPE_VIDEO_FLV;
                break;
            case 'application/x-shockwave-flash':
                $type = self::TYPE_FLEXPAPER;
                break;
            case 'audio/mpeg':
                $type = self::TYPE_AUDIO_MP3;
                break;
            case 'image/jpeg':
            case 'image/png':
            case 'image/gif':
                $type = self::TYPE_IMAGE;
                break;
            default:
                $type = self::TYPE_NO_PLAYER;
                break;
        }

        return $type;
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
    public function get_path()
    {
        return $this->path;
    }

    /**
     *
     * @return string
     */
    public function get_file()
    {
        return $this->file;
    }

    /**
     *
     * @return int
     */
    public function get_size()
    {
        return (int) @filesize($this->get_pathfile());
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     *
     * @return id
     */
    public function get_subdef_id()
    {
        return $this->subdef_id;
    }

    /**
     *
     * @return boolean
     */
    public function is_substituted()
    {
        return $this->is_substituted;
    }

    /**
     *
     * @return string
     */
    public function get_pathfile()
    {
        return $this->path . $this->file;
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
    public function renew_url()
    {
        $this->generate_url();

        return $this->get_url();
    }

    /**
     * Return the databox subdef corresponding to the subdef
     *
     * @return \databox_subdef
     */
    public function getDataboxSubdef()
    {
        return $this->record
                ->get_databox()
                ->get_subdef_structure()
                ->get_subdef($this->record->get_type(), $this->get_name());
    }

    public function getDevices()
    {
        if ($this->get_name() == 'document') {
            return array(\databox_subdef::DEVICE_ALL);
        }

        try {

            return $this->record
                    ->get_databox()
                    ->get_subdef_structure()
                    ->get_subdef($this->record->get_type(), $this->get_name())
                    ->getDevices();
        } catch (\Exception_Databox_SubdefNotFound $e) {

            return array();
        }
    }

    /**
     *
     * @param  registryInterface $registry
     * @param  int               $angle
     * @return media_subdef
     */
    public function rotate($angle)
    {
        if ( ! $this->is_physically_present()) {
            throw new \Alchemy\Phrasea\Exception\RuntimeException('You can not rotate a substitution');
        }

        $Core = \bootstrap::getCore();

        $specs = new \MediaAlchemyst\Specification\Image();
        $specs->setRotationAngle($angle);

        try {
            $Core['media-alchemyst']->open($this->get_pathfile())
                ->turnInto($this->get_pathfile(), $specs)
                ->close();
        } catch (\MediaAlchemyst\Exception\Exception $e) {
            return $this;
        }

        $media = $Core['mediavorus']->guess(new SplFileInfo($this->get_pathfile()));

        $sql = "UPDATE subdef
              SET height = :height , width = :width, updated_on = NOW()
              WHERE record_id = :record_id AND name = :name";

        $params = array(
            ':width'     => $media->getWidth(),
            ':height'    => $media->getHeight(),
            ':record_id' => $this->get_record_id(),
            ':name'      => $this->get_name(),
        );

        $stmt = $this->record->get_databox()->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->width = $media->getWidth();
        $this->height = $media->getHeight();

        $this->delete_data_from_cache();

        unset($media);

        return $this;
    }

    /**
     * Read the technical datas of the file.
     * Returns an empty array for non physical present files
     *
     * @return array An array of technical datas Key/values
     */
    public function readTechnicalDatas()
    {
        if ( ! $this->is_physically_present()) {
            return array();
        }

        $Core = \bootstrap::getCore();
        $media = $Core['mediavorus']->guess(new \SplFileInfo($this->get_pathfile()));

        $datas = array();

        $methods = array(
            self::TC_DATA_WIDTH              => 'getWidth',
            self::TC_DATA_HEIGHT             => 'getHeight',
            self::TC_DATA_FOCALLENGTH        => 'getFocalLength',
            self::TC_DATA_CHANNELS           => 'getChannels',
            self::TC_DATA_COLORDEPTH         => 'getColorDepth',
            self::TC_DATA_CAMERAMODEL        => 'getCameraModel',
            self::TC_DATA_FLASHFIRED         => 'getFlashFired',
            self::TC_DATA_APERTURE           => 'getAperture',
            self::TC_DATA_SHUTTERSPEED       => 'getShutterSpeed',
            self::TC_DATA_HYPERFOCALDISTANCE => 'getHyperfocalDistance',
            self::TC_DATA_ISO                => 'getISO',
            self::TC_DATA_LIGHTVALUE         => 'getLightValue',
            self::TC_DATA_COLORSPACE         => 'getColorSpace',
            self::TC_DATA_DURATION           => 'getDuration',
            self::TC_DATA_FRAMERATE          => 'getFrameRate',
            self::TC_DATA_AUDIOSAMPLERATE    => 'getAudioSampleRate',
            self::TC_DATA_VIDEOCODEC         => 'getVideoCodec',
            self::TC_DATA_AUDIOCODEC         => 'getAudioCodec',
        );

        foreach ($methods as $tc_name => $method) {
            if (method_exists($media, $method)) {
                $result = call_user_method($method, $media);

                if (null !== $result) {
                    $datas[$tc_name] = $result;
                }
            }
        }

        $datas[self::TC_DATA_LONGITUDE] = $media->getLongitude();
        $datas[self::TC_DATA_LATITUDE] = $media->getLatitude();
        $datas[self::TC_DATA_MIMETYPE] = $media->getFile()->getMimeType();
        $datas[self::TC_DATA_FILESIZE] = $media->getFile()->getSize();

        unset($media);

        return $datas;
    }

    public static function create(record_Interface $record, $name, Media $media)
    {
        $databox = $record->get_databox();
        $connbas = $databox->get_connection();

        $path = $media->getFile()->getPath();
        $newname = $media->getFile()->getFilename();

        $params = array(
            ':path'       => $path,
            ':file'       => $newname,
            ':width'      => 0,
            ':height'     => 0,
            ':mime'       => $media->getFile()->getMimeType(),
            ':size'       => $media->getFile()->getSize(),
            ':dispatched' => 1,
        );

        if (method_exists($media, 'getWidth') && null !== $media->getWidth()) {
            $params[':width'] = $media->getWidth();
        }
        if (method_exists($media, 'getHeight') && null !== $media->getHeight()) {
            $params[':height'] = $media->getHeight();
        }

        try {

            $sql = 'SELECT subdef_id FROM subdef
                    WHERE record_id = :record_id AND name = :name';
            $stmt = $connbas->prepare($sql);
            $stmt->execute(array(
                ':record_id' => $record->get_record_id(),
                ':name'      => $name,
            ));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ( ! $row) {
                throw new \Exception_Media_SubdefNotFound('Require the real one');
            }

            $sql = "UPDATE subdef
              SET path = :path, file = :file
                  , width = :width , height = :height, mime = :mime
                  , size = :size, dispatched = :dispatched, updated_on = NOW()
              WHERE subdef_id = :subdef_id";

            $params[':subdef_id'] = $row['subdef_id'];
        } catch (\Exception_Media_SubdefNotFound $e) {
            $sql = "INSERT INTO subdef
              (record_id, name, path, file, width
                , height, mime, size, dispatched, created_on, updated_on)
              VALUES (:record_id, :name, :path, :file, :width, :height
                , :mime, :size, :dispatched, NOW(), NOW())";

            $params[':record_id'] = $record->get_record_id();
            $params[':name'] = $name;
        }

        $stmt = $connbas->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $subdef = new self($record, $name);
        $subdef->delete_data_from_cache();

        if ($subdef->get_permalink() instanceof media_Permalink_Adapter) {
            $subdef->get_permalink()->delete_data_from_cache();
        }

        unset($media);

        return $subdef;
    }

    /**
     *
     * @param  boolean $random
     * @return string
     */
    protected function generate_url()
    {
        if ( ! $this->is_physically_present()) {
            return;
        }

        if (in_array($this->mime, array('video/mp4'))) {
            $token = p4file::apache_tokenize($this->get_pathfile());
            if ($token) {
                $this->url = $token;

                return;
            }
        }

        $this->url = "/datafiles/" . $this->record->get_sbas_id()
            . "/" . $this->record->get_record_id() . "/"
            . $this->get_name() . "/";

        return;
    }

    public function get_cache_key($option = null)
    {
        return 'subdef_' . $this->get_record()->get_serialize_key()
            . '_' . $this->name . ($option ? '_' . $option : '');
    }

    public function get_data_from_cache($option = null)
    {
        $databox = $this->get_record()->get_databox();

        return $databox->get_data_from_cache($this->get_cache_key($option));
    }

    public function set_data_to_cache($value, $option = null, $duration = 0)
    {
        $databox = $this->get_record()->get_databox();

        return $databox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
    }

    public function delete_data_from_cache($option = null)
    {
        $this->setEtag(null);
        $databox = $this->get_record()->get_databox();

        return $databox->delete_data_from_cache($this->get_cache_key($option));
    }
}

