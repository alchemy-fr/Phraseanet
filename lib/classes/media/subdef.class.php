<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    protected $baseurl;

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
    protected $is_physically_present = true;

    const TYPE_VIDEO_MP4 = 'VIDEO_MP4';
    const TYPE_VIDEO_FLV = 'VIDEO_FLV';
    const TYPE_FLEXPAPER = 'FLEXPAPER';
    const TYPE_AUDIO_MP3 = 'AUDIO_MP3';
    const TYPE_IMAGE = 'IMAGE';
    const TYPE_NO_PLAYER = 'UNKNOWN';

    /**
     * @todo    the presence of file is checked on constructor, it would be better
     *          to check it when needed (stop disk access)
     *
     * @param   record_adapter $record
     * @param   type $name
     * @param   type $substitute
     * @return  media_subdef
     */
    function __construct(record_adapter &$record, $name, $substitute = false)
    {
        $this->name = $name;
        $this->record = $record;
        $this->load($substitute);
        $this->pathfile = $this->path . $this->file;

        $nowtime = new DateTime('-3 days');
        $random = $record->get_modification_date() > $nowtime;

        $this->generate_url($random);

        return $this;
    }

    /**
     *
     * @param boolean $substitute
     * @return media_subdef
     */
    protected function load($substitute)
    {
        try {
            $datas = $this->get_data_from_cache();
            $this->mime = $datas['mime'];
            $this->width = $datas['width'];
            $this->height = $datas['height'];
            $this->baseurl = $datas['baseurl'];
            $this->path = $datas['path'];
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

        $sql = 'SELECT subdef_id, name, baseurl, file, width, height, mime,
                path, size, substit, created_on, updated_on
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

        $registry = $this->record->get_databox()->get_registry();

        if ($row) {
            $this->width = (int) $row['width'];
            $this->height = (int) $row['height'];
            $this->mime = $row['mime'];
            $this->baseurl = trim($row['baseurl']);
            $this->file = $row['file'];
            $this->path = p4string::addEndSlash($row['path']);
            $this->is_substituted = ! ! $row['substit'];
            $this->subdef_id = (int) $row['subdef_id'];

            if ($row['updated_on'])
                $this->modification_date = new DateTime($row['updated_on']);
            if ($row['created_on'])
                $this->creation_date = new DateTime($row['created_on']);
        }
        elseif ($substitute === false) {
            throw new Exception_Media_SubdefNotFound($this->name . ' not found');
        } else {
            $this->mime = 'image/png';
            $this->width = 256;
            $this->height = 256;
            $this->baseurl = 'skins/icons/';
            $this->path = $registry->get('GV_RootPath') . 'www/skins/icons/';
            $this->file = 'deleted.png';
            $this->is_physically_present = false;
            $this->is_substituted = true;
        }
        if ( ! $row || ! file_exists($this->path . $this->file)) {
            if ($this->record->is_grouping()) {
                $this->mime = 'image/png';
                $this->width = 256;
                $this->height = 256;
                $this->baseurl = 'skins/icons/substitution/';
                $this->path = $registry->get('GV_RootPath')
                    . 'www/skins/icons/substitution/';
                $this->file = 'regroup_thumb.png';
                $this->is_substituted = true;
            } else {
                $mime = $this->record->get_mime();
                $mime = trim($mime) != '' ? str_replace('/', '_', $mime) : 'application_octet-stream';

                $this->mime = 'image/png';
                $this->width = 256;
                $this->height = 256;
                $this->baseurl = 'skins/icons/substitution/';
                $this->path = $registry->get('GV_RootPath')
                    . 'www/skins/icons/substitution/';
                $this->file = str_replace('+', '%20', $mime) . '.png';
                $this->is_substituted = true;
            }
            $this->is_physically_present = false;
            if ( ! file_exists($this->path . $this->file)) {
                $this->baseurl = 'skins/icons/';
                $this->path = $registry->get('GV_RootPath')
                    . 'www/skins/icons/';
                $this->file = 'substitution.png';
                $this->is_substituted = true;
            }
        }

        $datas = array(
            'mime'               => $this->mime
            , 'width'              => $this->width
            , 'height'             => $this->height
            , 'baseurl'            => $this->baseurl
            , 'path'               => $this->path
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
        if ( ! $this->permalink && $this->is_physically_present)
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
    public function get_baseurl()
    {
        return $this->baseurl;
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
        try {
            $system_file = new system_file($this->path . $this->file);

            return $system_file->getSize();
        } catch (Exception $e) {
            return 0;
        }
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
        return $this->pathfile;
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
     *
     * @param registryInterface $registry
     * @param int $angle
     * @return media_subdef
     */
    public function rotate(registryInterface $registry, $angle)
    {
        $Core = \bootstrap::getCore();

        $specs = new MediaAlchemyst\Specification\Image();
        $specs->setRotationAngle($angle);

        try {
            $Core['media-alchemyst']->open($this->get_pathfile())
                ->turnInto($this->get_pathfile(), $specs)
                ->close();
        } catch (\MediaAlchemyst\Exception\Exception $e) {
            return $this;
        }

        $result = new system_file($this->get_pathfile());

        $tc_datas = $result->get_technical_datas();
        if ($tc_datas[system_file::TC_DATAS_WIDTH] && $tc_datas[system_file::TC_DATAS_HEIGHT]) {

            $sql = "UPDATE subdef
              SET height = :height , width = :width, updated_on = NOW()
              WHERE record_id = :record_id AND name = :name";

            $params = array(
                ':width'     => $tc_datas[system_file::TC_DATAS_WIDTH]
                , ':height'    => $tc_datas[system_file::TC_DATAS_HEIGHT]
                , ':record_id' => $this->get_record_id()
                , ':name'      => $this->get_name()
            );

            $stmt = $this->record->get_databox()->get_connection()->prepare($sql);
            $stmt->execute($params);
            $stmt->closeCursor();
            $this->delete_data_from_cache();
        }

        return $this;
    }

    /**
     *
     * @param record_Interface $record
     * @param string $name
     * @param system_file $system_file
     * @param string $baseurl
     * @return media_subdef
     */
    public static function create(record_Interface $record, $name, system_file $system_file, $baseurl = '')
    {
        $databox = $record->get_databox();
        $connbas = $databox->get_connection();

        $path = $system_file->getPath();
        $newname = $system_file->getFilename();

        $datas = $system_file->get_technical_datas();

        $params = array(
            ':path'       => $path,
            ':file'       => $newname,
            ':baseurl'    => $baseurl,
            ':width'      => isset($datas[system_file::TC_DATAS_WIDTH]) ? $datas[system_file::TC_DATAS_WIDTH] : '',
            ':height'     => isset($datas[system_file::TC_DATAS_HEIGHT]) ? $datas[system_file::TC_DATAS_HEIGHT] : '',
            ':mime'       => $system_file->get_mime(),
            ':size'       => $system_file->getSize(),
            ':dispatched' => 1,
        );

        try {
            $subdef = new self($record, $name);

            if ( ! $subdef->is_physically_present()) {
                throw new \Exception_Media_SubdefNotFound('Require the real one');
            }

            $sql = "UPDATE subdef
              SET path = :path, file = :file, baseurl = :baseurl
                  , width = :width , height = :height, mime = :mime
                  , size = :size, dispatched = :dispatched, updated_on = NOW()
              WHERE subdef_id = :subdef_id";

            $params[':subdef_id'] = $subdef->get_subdef_id();
        } catch (\Exception_Media_SubdefNotFound $e) {
            $sql = "INSERT INTO subdef
              (record_id, name, path, file, baseurl, width
                , height, mime, size, dispatched, created_on, updated_on)
              VALUES (:record_id, :name, :path, :file, :baseurl, :width, :height
                , :mime, :size, :dispatched, NOW(), NOW())";

            $params[':record_id'] = $record->get_record_id();
            $params[':name'] = $name;
        }

        $stmt = $connbas->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return new self($record, $name);
    }

    /**
     *
     * @param boolean $random
     * @return string
     */
    protected function generate_url($random = false)
    {
        if ($this->baseurl !== '') {
            $registry = registry::get_instance();
            $this->url = $registry->get('GV_STATIC_URL')
                . '/' . p4string::addEndSlash($this->baseurl)
                . $this->file . ($random ? '?rand=' . mt_rand(10000, 99999) : '');

            return;
        }

        if (in_array($this->mime, array('video/mp4'))) {
            $token = p4file::apache_tokenize($this->pathfile);
            if ($token) {
                $this->url = $token;

                return;
            }
        }
        $this->url = "/datafiles/" . $this->record->get_sbas_id()
            . "/" . $this->record->get_record_id() . "/"
            . $this->get_name() . "/"
            . ($random ? '?' . mt_rand(10000, 99999) : '');

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
        $databox = $this->get_record()->get_databox();

        return $databox->delete_data_from_cache($this->get_cache_key($option));
    }
}

