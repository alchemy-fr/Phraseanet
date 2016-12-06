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
use Alchemy\Phrasea\Databox\Subdef\MediaSubdefRepository;
use Alchemy\Phrasea\Http\StaticFile\Symlink\SymLinker;
use Alchemy\Phrasea\Model\RecordReferenceInterface;
use Alchemy\Phrasea\Utilities\NullableDateTime;
use Assert\Assertion;
use Guzzle\Http\Url;
use MediaAlchemyst\Alchemyst;
use MediaVorus\Media\MediaInterface;
use MediaVorus\MediaVorus;

class media_subdef extends media_abstract implements cache_cacheableInterface
{
    /**
     * @param Application $app
     * @param int $databoxId
     * @return MediaSubdefRepository
     */
    private static function getMediaSubdefRepository(Application $app, $databoxId)
    {
        return $app['provider.repo.media_subdef']->getRepositoryForDatabox($databoxId);
    }

    /** @var Application */
    protected $app;

    /** @var string */
    protected $mime;

    /** @var string */
    protected $file;

    /** @var string */
    protected $path;

    /** @var record_adapter */
    protected $record;

    /** @var media_Permalink_Adapter */
    protected $permalink;

    /** @var boolean */
    protected $is_substituted = false;

    /** @var string */
    protected $pathfile;

    /** @var int */
    protected $subdef_id;

    /** @var string */
    protected $name;

    /** @var string */
    protected $etag;

    /** @var DateTime */
    protected $creation_date;

    /** @var DateTime */
    protected $modification_date;

    /** @var bool */
    protected $is_physically_present = false;

    /** @var integer */
    private $size = 0;

    /*
     * Players types constants
     */
    const TYPE_VIDEO_MP4 = 'VIDEO_MP4';
    const TYPE_VIDEO_FLV = 'VIDEO_FLV';
    const TYPE_FLEXPAPER = 'FLEXPAPER';
    const TYPE_AUDIO_MP3 = 'AUDIO_MP3';
    const TYPE_IMAGE = 'IMAGE';
    const TYPE_NO_PLAYER = 'UNKNOWN';

    /*
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
    const TC_DATA_LONGITUDE_REF = 'LongitudeRef';
    const TC_DATA_LATITUDE = 'Latitude';
    const TC_DATA_LATITUDE_REF = 'LatitudeRef';
    const TC_DATA_FOCALLENGTH = 'FocalLength';
    const TC_DATA_CAMERAMODEL = 'CameraModel';
    const TC_DATA_FLASHFIRED = 'FlashFired';
    const TC_DATA_APERTURE = 'Aperture';
    const TC_DATA_SHUTTERSPEED = 'ShutterSpeed';
    const TC_DATA_HYPERFOCALDISTANCE = 'HyperfocalDistance';
    const TC_DATA_ISO = 'ISO';
    const TC_DATA_LIGHTVALUE = 'LightValue';

    /**
     * @param Application $app
     * @param RecordReferenceInterface $record
     * @param string $name
     * @param bool $substitute
     * @param array|null $data
     */
    public function __construct(Application $app, RecordReferenceInterface $record, $name, $substitute = false, array $data = null)
    {
        $this->app = $app;
        $this->name = $name;
        $this->record = $record instanceof record_adapter
            ? $record
            : $app->findDataboxById($record->getDataboxId())->get_record($record->getRecordId());

        if (null !== $data) {
            $this->loadFromArray($data);
        } else {
            $this->load($substitute);
        }

        parent::__construct($this->width, $this->height, $this->generateUrl());
    }

    /**
     * @param  bool $substitute
     * @return void
     */
    protected function load($substitute)
    {
        try {
            $data = $this->get_data_from_cache();
        } catch (Exception $e) {
            $data = false;
        }

        if (is_array($data)) {
            $this->loadFromArray($data);

            return;
        }

        $data = self::getMediaSubdefRepository($this->app, $this->record->getDataboxId())
            ->findOneByRecordIdAndName($this->record->getRecordId(), $this->name);

        if ($data) {
            $this->loadFromArray($data->toArray());
        } elseif ($substitute === false) {
            throw new Exception_Media_SubdefNotFound($this->name . ' not found');
        }

        $this->loadFromArray([]);

        $this->set_data_to_cache($this->toArray());
    }

    private function loadFromArray(array $data)
    {
        if (!$data) {
            $data = [
                'mime' => 'unknown',
                'width' => 0,
                'height' => 0,
                'size' => 0,
                'path' => '',
                'file' => '',
                'physically_present' => false,
                'is_substituted' => false,
                'subdef_id' => null,
                'updated_on' => null,
                'created_on' => null,
                'url' => null,
            ];
        }

        $normalizer = function ($field, callable $then, callable $else = null) use ($data) {
            if (isset($data[$field]) || array_key_exists($field, $data)) {
                return $then($data[$field]);
            }

            return $else ? $else() : null;
        };

        $this->mime = $data['mime'];
        $this->width = (int)$data['width'];
        $this->height = (int)$data['height'];
        $this->size = (int)$data['size'];
        $this->etag = $normalizer('etag', 'strval');
        $this->path = p4string::addEndSlash($data['path']);
        $this->file = $data['file'];
        $this->is_physically_present = (bool)$data['physically_present'];
        $this->is_substituted = (bool)$data['is_substituted'];
        $this->subdef_id = $normalizer('subdef_id', 'intval');
        $this->modification_date = $normalizer('updated_on', 'date_create');
        $this->creation_date = $normalizer('created_on', 'date_create');
        $this->url = $normalizer('url', [Url::class, 'factory'], [$this, 'generateUrl']);

        if (!$this->isStillAccessible()) {
            $this->markPhysicallyUnavailable();
        }
    }

    private function toArray()
    {
        return [
            'record_id' => $this->get_record_id(),
            'name' => $this->get_name(),
            'width' => $this->width,
            'size' => $this->size,
            'height' => $this->height,
            'mime' => $this->mime,
            'file' => $this->file,
            'path' => $this->path,
            'physically_present' => $this->is_physically_present,
            'is_substituted' => $this->is_substituted,
            'subdef_id' => $this->subdef_id,
            'updated_on' => NullableDateTime::format($this->modification_date),
            'created_on' => NullableDateTime::format($this->creation_date),
            'etag' => $this->etag,
            'url' => (string)$this->url,
        ];
    }

    /**
     * Removes the file associated to a subdef
     *
     * @return \media_subdef
     */
    public function remove_file()
    {
        if ($this->is_physically_present() && is_writable($this->getRealPath())) {
            unlink($this->getRealPath());

            $this->delete_data_from_cache();

            $permalink = $this->get_permalink();

            if ($permalink instanceof media_Permalink_Adapter) {
                $permalink->delete_data_from_cache();
            }

            $this->markPhysicallyUnavailable();
        }

        return $this;
    }

    /**
     * delete this subdef
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function delete()
    {
        $this->remove_file();

        $connection = $this->getDataboxConnection();

        $connection->executeUpdate(
            'DELETE FROM permalinks WHERE subdef_id = :subdef_id',
            ['subdef_id' => $this->subdef_id]
        );

        self::getMediaSubdefRepository($this->app, $this->record->getDataboxId())->delete($this);
        $this->delete_data_from_cache();
        $this->record->delete_data_from_cache(record_adapter::CACHE_SUBDEFS);
    }

    private function getSubstituteFilename()
    {
        if ($this->record->isStory()) {
            return 'regroup_thumb.png';
        }

        $mime = $this->record->getMimeType();
        $mime = trim($mime) != '' ? str_replace('/', '_', $mime) : 'application_octet-stream';

        return str_replace('+', '%20', $mime) . '.png';
    }

    /**
     * Find a substitution file for a subdef
     * @return void
     */
    protected function markPhysicallyUnavailable()
    {
        $this->is_physically_present = false;

        $this->mime = 'image/png';
        $this->width = 256;
        $this->height = 256;
        $this->file = $this->getSubstituteFilename();
        $this->etag = null;

        $this->path = $this->app['root.path'] . '/www/assets/common/images/icons/substitution/';
        $this->url = Url::factory('/assets/common/images/icons/substitution/' . $this->file);

        if (!file_exists($this->getRealPath())) {
            $this->path = $this->app['root.path'] . '/www/assets/common/images/icons/';
            $this->file = 'substitution.png';
            $this->url = Url::factory('/assets/common/images/icons/' . $this->file);
        }
    }

    /**
     * @return bool
     */
    public function is_physically_present()
    {
        return $this->is_physically_present;
    }

    /**
     * @return record_adapter
     */
    public function get_record()
    {
        return $this->record;
    }

    /**
     * @return media_Permalink_Adapter
     */
    public function get_permalink()
    {
        if (null === $this->permalink && $this->is_physically_present()) {
            $this->permalink = media_Permalink_Adapter::getPermalink($this->app, $this->record->getDatabox(), $this);
        }

        return $this->permalink;
    }

    /**
     * @return int
     */
    public function get_record_id()
    {
        return $this->record->getRecordId();
    }

    public function getEtag()
    {
        if (!$this->etag && $this->is_physically_present()) {
            $file = new SplFileInfo($this->getRealPath());

            if ($file->isFile()) {
                $this->setEtag(md5($file->getRealPath() . $file->getMTime()));
            }
        }

        return $this->etag;
    }

    /**
     * @param string|null $etag
     */
    public function setEtag($etag)
    {
        $this->etag = $etag;

        return $this->save();
    }

    /**
     * @param boolean $substit
     */
    public function set_substituted($substit)
    {
        $this->is_substituted = !!$substit;

        return $this->save();
    }

    /**
     * @return int
     */
    public function get_sbas_id()
    {
        return $this->record->getDataboxId();
    }

    /**
     * @return string
     */
    public function get_type()
    {
        static $types = [
            'application/x-shockwave-flash' => self::TYPE_FLEXPAPER,
            'audio/mp3' => self::TYPE_AUDIO_MP3,
            'audio/mpeg' => self::TYPE_AUDIO_MP3,
            'image/gif' => self::TYPE_IMAGE,
            'image/jpeg' => self::TYPE_IMAGE,
            'image/png' => self::TYPE_IMAGE,
            'video/mp4' => self::TYPE_VIDEO_MP4,
            'video/x-flv' => self::TYPE_VIDEO_FLV,
        ];

        if (isset($types[$this->mime])) {
            return $types[$this->mime];
        }

        return self::TYPE_NO_PLAYER;
    }

    /**
     * @return string
     */
    public function get_mime()
    {
        return $this->mime;
    }

    /**
     * @return string
     */
    public function get_path()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function get_file()
    {
        return $this->file;
    }

    /**
     * @return int
     */
    public function get_size()
    {
        return $this->size;
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function get_subdef_id()
    {
        return $this->subdef_id;
    }

    /**
     * @return bool
     */
    public function is_substituted()
    {
        return $this->is_substituted;
    }

    /**
     * @return string
     * @deprecated use {@link self::getRealPath} instead
     */
    public function get_pathfile()
    {
        return $this->getRealPath();
    }

    /**
     * @return DateTime
     */
    public function get_modification_date()
    {
        return $this->modification_date;
    }

    /**
     * @return DateTime
     */
    public function get_creation_date()
    {
        return $this->creation_date;
    }

    /**
     * @return Url
     */
    public function renew_url()
    {
        $this->url = $this->generateUrl();

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
                ->getDatabox()
                ->get_subdef_structure()
                ->get_subdef($this->record->getType(), $this->get_name());
    }

    public function getDevices()
    {
        if ($this->get_name() === 'document') {
            return [\databox_subdef::DEVICE_ALL];
        }

        try {
            return $this->record
                    ->getDatabox()
                    ->get_subdef_structure()
                    ->get_subdef($this->record->getType(), $this->get_name())
                    ->getDevices();
        } catch (\Exception_Databox_SubdefNotFound $e) {
            return [];
        }
    }

    /**
     * @param int        $angle
     * @param Alchemyst  $alchemyst
     * @param MediaVorus $mediavorus
     *
     * @return media_subdef
     */
    public function rotate($angle, Alchemyst $alchemyst, MediaVorus $mediavorus)
    {
        if (!$this->is_physically_present()) {
            throw new \Alchemy\Phrasea\Exception\RuntimeException('You can not rotate a substitution');
        }

        $specs = new \MediaAlchemyst\Specification\Image();
        $specs->setRotationAngle($angle);

        try {
            $alchemyst->turnInto($this->getRealPath(), $this->getRealPath(), $specs);
        } catch (\MediaAlchemyst\Exception\ExceptionInterface $e) {
            return $this;
        }

        $media = $mediavorus->guess($this->getRealPath());

        $this->width = $media->getWidth();
        $this->height = $media->getHeight();

        return $this->save();
    }

    /**
     * Read the technical datas of the file.
     * Returns an empty array for non physical present files
     *
     * @return array An array of technical datas Key/values
     */
    public function readTechnicalDatas(MediaVorus $mediavorus)
    {
        if (!$this->is_physically_present()) {
            return [];
        }

        $media = $mediavorus->guess($this->getRealPath());

        $datas = [];

        $methods = [
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
            self::TC_DATA_ORIENTATION        => 'getOrientation',
            self::TC_DATA_LONGITUDE          => 'getLongitude',
            self::TC_DATA_LONGITUDE_REF      => 'getLongitudeRef',
            self::TC_DATA_LATITUDE           => 'getLatitude',
            self::TC_DATA_LATITUDE_REF       => 'getLatitudeRef',
        ];

        foreach ($methods as $tc_name => $method) {
            if (method_exists($media, $method)) {
                $result = call_user_func([$media, $method]);

                if (null !== $result) {
                    $datas[$tc_name] = $result;
                }
            }
        }

        $datas[self::TC_DATA_MIMETYPE] = $media->getFile()->getMimeType();
        $datas[self::TC_DATA_FILESIZE] = $media->getFile()->getSize();

        unset($media);

        return $datas;
    }

    public static function create(Application $app, RecordReferenceInterface $record, $name, MediaInterface $media)
    {
        $path = $media->getFile()->getPath();
        $newname = $media->getFile()->getFilename();

        $params = [
            'record_id' => $record->getRecordId(),
            'name' => $name,
            'path' => $path,
            'file' => $newname,
            'width' => 0,
            'height' => 0,
            'mime' => $media->getFile()->getMimeType(),
            'size' => $media->getFile()->getSize(),
            'physically_present' => true,
            'is_substituted' => false,
        ];

        if (method_exists($media, 'getWidth') && null !== $media->getWidth()) {
            $params['width'] = $media->getWidth();
        }
        if (method_exists($media, 'getHeight') && null !== $media->getHeight()) {
            $params['height'] = $media->getHeight();
        }

        /** @var callable $factoryProvider */
        $factoryProvider = $app['provider.factory.media_subdef'];
        $factory = $factoryProvider($record->getDataboxId());

        $subdef = $factory($params);

        Assertion::isInstanceOf($subdef, \media_subdef::class);

        $repository = self::getMediaSubdefRepository($app, $record->getDataboxId());
        $repository->save($subdef);

        // Refresh from Database.
        $subdef = $repository->findOneByRecordIdAndName($record->getRecordId(), $name);

        $permalink = $subdef->get_permalink();

        if ($permalink instanceof media_Permalink_Adapter) {
            $permalink->delete_data_from_cache();
        }

        if ($name === 'thumbnail') {
            /** @var SymLinker $symlinker */
            $symlinker = $app['phraseanet.thumb-symlinker'];
            $symlinker->symlink($subdef->getRealPath());
        }

        return $subdef;
    }

    /**
     * @return Url
     */
    protected function generateUrl()
    {
        if (!$this->is_physically_present()) {
            $this->markPhysicallyUnavailable();

            return $this->url;
        }

        $generators = [
            [$this, 'tryGetThumbnailUrl'],
            [$this, 'tryGetVideoUrl'],
        ];

        foreach ($generators as $generator) {
            $url = $generator();

            if ($url instanceof Url) {
                return $url;
            }
        }

        return Url::factory($this->app->path('datafile', [
            'sbas_id' => $this->record->getDataboxId(),
            'record_id' => $this->record->getRecordId(),
            'subdef' => $this->get_name(),
            'etag' => $this->getEtag(),
        ]));
    }

    public function get_cache_key($option = null)
    {
        return 'subdef_' . $this->get_record()->getId()
            . '_' . $this->name . ($option ? '_' . $option : '');
    }

    public function get_data_from_cache($option = null)
    {
        $databox = $this->get_record()->getDatabox();

        return $databox->get_data_from_cache($this->get_cache_key($option));
    }

    public function set_data_to_cache($value, $option = null, $duration = 0)
    {
        $databox = $this->get_record()->getDatabox();

        return $databox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
    }

    public function delete_data_from_cache($option = null)
    {
        $databox = $this->get_record()->getDatabox();

        $databox->delete_data_from_cache($this->get_cache_key($option));
    }

    /**
     * @return string
     */
    public function getRealPath()
    {
        return $this->path . $this->file;
    }

    /**
     * @return string
     */
    public function getWatermarkRealPath()
    {
        return $this->path . 'watermark_' . $this->file;
    }

    /**
     * @return string
     */
    public function getStampRealPath()
    {
        return $this->path . 'stamp_' . $this->file;
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    private function getDataboxConnection()
    {
        return $this->record->getDatabox()->get_connection();
    }

    /**
     * @return bool
     */
    private function isStillAccessible()
    {
        return $this->is_physically_present && file_exists($this->getRealPath());
    }

    /**
     * @return Url|null
     */
    protected function tryGetThumbnailUrl()
    {
        if ('thumbnail' !== $this->get_name()) {
            return null;
        }

        $url = $this->app['phraseanet.static-file']->getUrl($this->getRealPath());

        if (null === $url) {
            return null;
        }

        $url->getQuery()->offsetSet('etag', $this->getEtag());

        return $url;
    }

    /**
     * @return Url|null
     */
    protected function tryGetVideoUrl()
    {
        if ($this->mime !== 'video/mp4' || !$this->app['phraseanet.h264-factory']->isH264Enabled()) {
            return null;
        }

        return $this->app['phraseanet.h264']->getUrl($this->getRealPath());
    }

    /**
     * @return $this
     */
    private function save()
    {
        self::getMediaSubdefRepository($this->app, $this->record->getDataboxId())->save($this);

        return $this;
    }
}
