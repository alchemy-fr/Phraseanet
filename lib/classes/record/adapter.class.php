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
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class record_adapter implements record_Interface, cache_cacheableInterface
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
  protected $bitly_link;

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

  const CACHE_ORIGINAL_NAME = 'originalname';
  const CACHE_TECHNICAL_DATAS = 'technical_datas';
  const CACHE_MIME = 'mime';
  const CACHE_SHA256 = 'sha256';
  const CACHE_SUBDEFS = 'subdefs';
  const CACHE_GROUPING = 'grouping';
  const CACHE_STATUS = 'status';

  /**
   *
   * @param <int> $base_id
   * @param <int> $record_id
   * @param <int> $number
   * @param <string> $xml
   * @param <string> $status
   * @return record_adapter
   */
  public function __construct($sbas_id, $record_id, $number = null)
  {
    $this->databox = databox::get_instance((int) $sbas_id);
    $this->number = (int) $number;
    $this->record_id = (int) $record_id;

    return $this->load();
    ;
  }

  protected function load()
  {
    try
    {
      $datas = $this->get_data_from_cache();

      $this->mime = $datas['mime'];
      $this->sha256 = $datas['sha256'];
      $this->bitly_link = $datas['bitly_link'];
      $this->original_name = $datas['original_name'];
      $this->type = $datas['type'];
      $this->grouping = $datas['grouping'];
      $this->uuid = $datas['uuid'];
      $this->modification_date = $datas['modification_date'];
      $this->creation_date = $datas['creation_date'];
      $this->base_id = $datas['base_id'];

      return $this;
    }
    catch (Exception $e)
    {
      
    }

    $connbas = $this->databox->get_connection();
    $sql = 'SELECT coll_id, record_id,credate , uuid, moddate, parent_record_id
            , type, originalname, bitly, sha256, mime
            FROM record WHERE record_id = :record_id';
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':record_id' => $this->record_id));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$row)
      throw new Exception_Record_AdapterNotFound('Record ' . $this->record_id . ' on database ' . $this->databox->get_sbas_id() . ' not found ');

    $this->base_id = (int) phrasea::baseFromColl($this->databox->get_sbas_id(), $row['coll_id']);
    $this->creation_date = new DateTime($row['credate']);
    $this->modification_date = new DateTime($row['moddate']);
    $this->uuid = $row['uuid'];

    $this->grouping = ($row['parent_record_id'] == '1');
    $this->type = $row['type'];
    $this->original_name = $row['originalname'];
    $this->bitly_link = $row['bitly'];
    $this->sha256 = $row['sha256'];
    $this->mime = $row['mime'];

    $datas = array(
        'mime' => $this->mime
        , 'sha256' => $this->sha256
        , 'bitly_link' => $this->bitly_link
        , 'original_name' => $this->original_name
        , 'type' => $this->type
        , 'grouping' => $this->grouping
        , 'uuid' => $this->uuid
        , 'modification_date' => $this->modification_date
        , 'creation_date' => $this->creation_date
        , 'base_id' => $this->base_id
    );

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
   * @param int $number
   * @return record_adapter
   */
  public function set_number($number)
  {
    $this->number = (int) $number;

    return $this;
  }

  /**
   *
   * @param string $type
   * @return record_adapter
   */
  public function set_type($type)
  {
    $type = strtolower($type);

    $old_type = $this->get_type();

    if (!in_array($type, array('document', 'audio', 'video', 'image', 'flash', 'map')))
      throw new Exception('unrecognized document type');

    $connbas = connection::getPDOConnection($this->get_sbas_id());

    $sql = 'UPDATE record SET type = :type WHERE record_id = :record_id';
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':type' => $type, ':record_id' => $this->get_record_id()));
    $stmt->closeCursor();

    if ($old_type !== $type)
      $this->rebuild_subdefs();

    $this->type = $type;
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
   * return recor_id of the record
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
  public function get_embedable_medias()
  {
    return $this->get_subdefs();
  }

  /**
   *
   * @return string
   */
  public function get_status_icons()
  {
    $dstatus = databox_status::getDisplayStatus();
    $sbas_id = $this->get_sbas_id();
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

    $status = '';

    if (isset($dstatus[$sbas_id]))
    {
      foreach ($dstatus[$sbas_id] as $n => $statbit)
      {
        if ($statbit['printable'] == '0' &&
                !$user->ACL()->has_right_on_base($this->base_id, 'chgstatus'))
          continue;

        $x = (substr((strrev($this->get_status())), $n, 1));

        $source0 = "/skins/icons/spacer.gif";
        $style0 = "visibility:hidden;display:none;";
        $source1 = "/skins/icons/spacer.gif";
        $style1 = "visibility:hidden;display:none;";
        if ($statbit["img_on"])
        {
          $source1 = $statbit["img_on"];
          $style1 = "visibility:auto;display:none;";
        }
        if ($statbit["img_off"])
        {
          $source0 = $statbit["img_off"];
          $style0 = "visibility:auto;display:none;";
        }
        if ($x == '1')
        {
          if ($statbit["img_on"])
          {
            $style1 = "visibility:auto;display:inline;";
          }
        }
        else
        {
          if ($statbit["img_off"])
          {
            $style0 = "visibility:auto;display:inline;";
          }
        }
        $status .= '<img style="margin:1px;' . $style1 . '" ' .
                'class="STAT_' . $this->base_id . '_'
                . $this->record_id . '_' . $n . '_1" ' .
                'src="' . $source1 . '" title="' .
                (isset($statbit["labelon"]) ?
                        $statbit["labelon"] :
                        $statbit["lib"]) . '"/>';
        $status .= '<img style="margin:1px;' . $style0 . '" ' .
                'class="STAT_' . $this->base_id . '_'
                . $this->record_id . '_' . $n . '_0" ' .
                'src="' . $source0 . '" title="' .
                (isset($statbit["labeloff"]) ?
                        $statbit["labeloff"] :
                        ("non-" . $statbit["lib"])) . '"/>';
      }
    }

    return $status;
  }

  /**
   * return the type of the document
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
   * return duration in seconds
   *
   * @return int
   */
  public function get_duration()
  {
    if (!$this->duration)
    {
      $this->duration = $this->get_technical_infos(system_file::TC_DATAS_DURATION);
    }

    return $this->duration;
  }

  /**
   *
   * @param collection $collection
   * @param appbox $appbox
   * @return record_adapter
   */
  public function move_to_collection(collection &$collection, appbox &$appbox)
  {
    $sql = 'UPDATE sselcont
            SET base_id = :base_id
            WHERE record_id = :record_id
            AND base_id IN (SELECT base_id FROM bas WHERE sbas_id = :sbas_id)';

    $params = array(
        ':base_id' => $collection->get_base_id(),
        ':record_id' => $this->get_record_id(),
        ':sbas_id' => $this->get_sbas_id()
    );

    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute($params);
    $stmt->closeCursor();

    basket_adapter::revoke_baskets_record($this, $appbox);

    $sql = "UPDATE record SET coll_id = :coll_id WHERE record_id =:record_id";

    $params = array(
        ':coll_id' => $collection->get_coll_id(),
        ':record_id' => $this->get_record_id()
    );

    $stmt = $this->get_databox()->get_connection()->prepare($sql);
    $stmt->execute($params);
    $stmt->closeCursor();

    $this->base_id = $collection->get_base_id();

    $appbox->get_session()->get_logger($this->get_databox())
            ->log($this, Session_Logger::EVENT_MOVE, $collection->get_coll_id(), '');

    $this->delete_data_from_cache();

    return $this;
  }

  /**
   *
   * @return media
   */
  public function get_rollover_thumbnail()
  {
    if ($this->get_type() != 'video')
    {
      return null;
    }
    try
    {
      return $this->get_subdef('thumbnailGIF');
    }
    catch (Exception $e)
    {
      
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
    if (!$this->status)
    {
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
    try
    {
      return $this->get_data_from_cache(self::CACHE_STATUS);
    }
    catch (Exception $e)
    {
      
    }
    $sql = 'SELECT BIN(status) as status FROM record
              WHERE record_id = :record_id';
    $stmt = $this->get_databox()->get_connection()->prepare($sql);
    $stmt->execute(array(':record_id' => $this->get_record_id()));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$row)
      throw new Exception('status not found');

    $status = $row['status'];
    $n = strlen($status);
    while ($n < 64)
    {
      $status = '0' . $status;
      $n++;
    }

    $this->set_data_to_cache($status, self::CACHE_STATUS);

    return $status;
  }

  /**
   *
   * @param <type> $name
   * @return media_subdef
   */
  public function get_subdef($name)
  {
    $name = strtolower($name);
    if (!in_array($name, $this->get_available_subdefs()))
      throw new Exception_Media_SubdefNotFound ();

    if (isset($this->subdefs[$name]))
      return $this->subdefs[$name];

    if (!$this->subdefs)
      $this->subdefs = array();

    $substitute = ($name !== 'document');

    return $this->subdefs[$name] = new media_subdef($this, $name, $substitute);
  }

  /**
   *
   * @return Array
   */
  public function get_subdefs()
  {
    if (!$this->subdefs)
      $this->subdefs = array();

    $subdefs = $this->get_available_subdefs();
    foreach ($subdefs as $name)
    {
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
    try
    {
      return $this->get_data_from_cache(self::CACHE_SUBDEFS);
    }
    catch (Exception $e)
    {
      
    }

    $connbas = $this->get_databox()->get_connection();

    $sql = 'SELECT name FROM record r, subdef s
            WHERE s.record_id = r.record_id AND r.record_id = :record_id';

    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':record_id' => $this->get_record_id()));
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $subdefs = array('preview', 'thumbnail');

    foreach ($rs as $row)
    {
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
    return collection::getLogo($this->base_id, true);
  }

  /**
   *
   * @param string $data
   * @return Array
   */
  public function get_technical_infos($data = false)
  {

    if (!$this->technical_datas)
    {
      try
      {
        $this->technical_datas = $this->get_data_from_cache(self::CACHE_TECHNICAL_DATAS);
      }
      catch (Exception $e)
      {
        $this->technical_datas = array();
        $connbas = $this->get_databox()->get_connection();
        $sql = 'SELECT name, value FROM technical_datas WHERE record_id = :record_id';
        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':record_id' => $this->get_record_id()));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row)
        {
          switch ($row['name'])
          {
            case 'size':
            case system_file::TC_DATAS_WIDTH:
            case system_file::TC_DATAS_COLORDEPTH:
            case system_file::TC_DATAS_HEIGHT:
            case system_file::TC_DATAS_DURATION:
              $this->technical_datas[$row['name']] = (int) $row['value'];
              break;
            default:
              $this->technical_datas[$row['name']] = $row['value'];
              break;
          }
        }
        /**
         * @todo un patch pour ca, et rentrer les infos à l'insert
         */
//        try
//        {
//          $hd = $this->get_subdef('document');
////          if ($hd)
////          {
//          $this->technical_datas['size'] = $hd->get_size();
//          $this->technical_datas['width'] = $hd->get_width();
//          $this->technical_datas['height'] = $hd->get_height();
////          }
//        }
//        catch (Exception $e)
//        {
//
//        }
        $this->set_data_to_cache($this->technical_datas, self::CACHE_TECHNICAL_DATAS);
        unset($e);
      }
    }

    if ($data)
    {
      if (isset($this->technical_datas[$data]))
        return $this->technical_datas[$data];
      else
        return false;
    }

    return $this->technical_datas;
  }

  /**
   *
   * @return caption_record
   */
  public function get_caption()
  {
    if (!$this->caption_record)
      $this->caption_record = new caption_record($this, $this->get_databox());

    return $this->caption_record;
  }

  /**
   *
   * @return string
   */
  public function get_xml()
  {
    if (!$this->xml)
    {
      $dom_doc = new DOMDocument('1.0', 'UTF-8');
      $dom_doc->formatOutput = true;
      $dom_doc->standalone = true;

      $record = $dom_doc->createElement('record');
      $record->setAttribute('record_id', $this->get_record_id());
      $dom_doc->appendChild($record);
      $description = $dom_doc->createElement('description');
      $record->appendChild($description);

      $caption = $this->get_caption();

      foreach ($caption->get_fields() as $field)
      {
        if ($field->is_multi())
          $values = $field->get_value();
        else
          $values = array($field->get_value());

        foreach ($values as $value)
        {
          $elem = $dom_doc->createElement($field->get_name());
          $elem->appendChild($dom_doc->createTextNode($value));
          $elem->setAttribute('meta_id', $field->get_meta_id());
          $elem->setAttribute('meta_struct_id', $field->get_meta_struct_id());
          $description->appendChild($elem);
        }
      }

      $doc = $dom_doc->createElement('doc');

      $tc_datas = $this->get_technical_infos();

      foreach ($tc_datas as $key => $data)
      {
        $doc->setAttribute($key, $data);
      }

      $record->appendChild($doc);

      $this->xml = $dom_doc->saveXML();
    }

    return $this->xml;
  }

  /**
   *
   * @return string
   */
  public function get_original_name()
  {
    return $this->original_name;
  }

  /**
   *
   * @return string
   */
  public function get_title($highlight = false, searchEngine_adapter $searchEngine = null)
  {
    $sbas_id = $this->get_sbas_id();
    $record_id = $this->get_record_id();

    $title = '';
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();

    $fields = $this->get_databox()->get_meta_structure();

    $fields_to_retrieve = array();

    foreach ($fields as $field)
    {
      if (in_array($field->get_thumbtitle(), array('1', $session->get_I18n())))
      {
        $fields_to_retrieve [] = $field->get_name();
      }
    }

    if (count($fields_to_retrieve) > 0)
    {
      $retrieved_fields = $this->get_caption()->get_highlight_fields($highlight, $fields_to_retrieve, $searchEngine);
      $titles = array();
      foreach ($retrieved_fields as $key => $value)
      {
        if (trim($value === ''))
          continue;
        $titles[] = $value;
      }
      $title = trim(implode(' - ', $titles));
    }

    if (trim($title) === '')
    {
      $title = trim($this->get_original_name());
    }

    $title = $title != "" ? $title : "<i>" . _('reponses::document sans titre') . "</i>";

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
    try
    {
      $this->get_subdef('preview');

      return $this->get_subdef('preview')->is_physically_present();
    }
    catch (Exception $e)
    {
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

  /**
   *
   * @param string $name subdef name
   * @param system_file $pathfile new file
   * @return record_adapter
   */
  public function substitute_subdef($name, system_file $pathfile)
  {
    $newfilename = $this->record_id . '_0_' . $name
            . '.' . $pathfile->get_extension();

    $base_url = '';
    $original_file = $subdef_def = false;

    $subdefs = $this->get_databox()->get_subdef_structure();

    foreach ($subdefs as $type => $datas)
    {
      if ($this->get_type() != $type)
        continue;

      if (!isset($datas[$name]))
        throw new Exception('No available subdef declaration for this type and name');

      $subdef_def = $datas[$name];
      break;
    }

    if (!$subdef_def)
      throw new Exception('Unknown subdef name');

    try
    {
      $value = $this->get_subdef($name);
      $original_file = p4string::addEndSlash($value->get_path()) . $value->get_file();
      unlink($original_file);
    }
    catch (Exception $e)
    {
      $path = databox::dispatch($subdef_def->get_path());
      system_file::mkdir($path);
      $original_file = $path . $newfilename;
    }

    $path_file_dest = $original_file;

    if (trim($subdef_def->get_baseurl()) !== '')
    {
      $base_url = str_replace(
              array((string) $subdef_def->get_path(), $newfilename)
              , array((string) $subdef_def->get_baseurl(), '')
              , $path_file_dest
      );
    }

    try
    {
      $connbas = connection::getPDOConnection($this->get_sbas_id());

      $sql = 'DELETE FROM subdef WHERE record_id= :record_id AND name=:name';
      $stmt = $connbas->prepare($sql);
      $stmt->execute(
              array(
                  ':record_id' => $this->record_id
                  , ':name' => $name
              )
      );

      $registry = registry::get_instance();

      $adapter = new binaryAdapter_image_resize($registry);
      $adapter->execute($pathfile, $path_file_dest, $subdef_def->get_options());

      $system_file = new system_file($path_file_dest);
      $system_file->chmod();

      $image_size = $system_file->get_technical_datas();

      $sql = 'INSERT INTO subdef
                (record_id, name, baseurl, file, width,
                  height, mime, path, size, substit)
              VALUES
                (:record_id, :name, :baseurl, :filename,
                  :width, :height, :mime, :path, :filesize, "1")';

      $stmt = $connbas->prepare($sql);

      $stmt->execute(array(
          ':record_id' => $this->record_id,
          ':name' => $name,
          ':baseurl' => $base_url,
          ':filename' => $system_file->getFilename(),
          ':width' => $image_size[system_file::TC_DATAS_WIDTH],
          ':height' => $image_size[system_file::TC_DATAS_HEIGHT],
          ':mime' => $system_file->get_mime(),
          ':path' => $system_file->getPath(),
          ':filesize' => $system_file->getSize()
      ));

      $sql = 'UPDATE record SET moddate=NOW() WHERE record_id=:record_id';
      $stmt = $connbas->prepare($sql);
      $stmt->execute(array(':record_id' => $this->get_record_id()));
      $stmt->execute();

      $this->delete_data_from_cache(self::CACHE_SUBDEFS);


      if ($subdef_def->meta_writeable())
      {
        $this->write_metas();
      }
      if ($name == 'document')
      {
        $this->rebuild_subdefs();
      }
    }
    catch (Exception $e)
    {
      unset($e);
    }

    return $this;
  }

  /**
   *
   * @param DOMDocument $dom_doc
   * @return record_adapter
   */
  protected function set_xml(DOMDocument $dom_doc)
  {
    $connbas = $this->get_databox()->get_connection();
    $sql = 'UPDATE record SET xml = :xml WHERE record_id= :record_id';
    $stmt = $connbas->prepare($sql);
    $stmt->execute(
            array(
                ':xml' => $dom_doc->saveXML(),
                ':record_id' => $this->record_id
            )
    );

    return $this;
  }

  /**
   *
   * @todo move this function to caption_record
   * @param Array $params An array containing three keys : meta_struct_id (int), meta_id (int or null) and value (Array)
   * @return record_adapter
   */
  protected function set_metadata(Array $params, databox $databox)
  {
    $mandatoryParams = array('meta_struct_id', 'meta_id', 'value');

    foreach ($mandatoryParams as $param)
    {
      if (!array_key_exists($param, $params))
        throw new Exception_InvalidArgument();
    }

    if (!is_array($params['value']))
      throw new Exception();

    $databox_field = databox_field::get_instance($databox, $params['meta_struct_id']);

    if (trim($params['meta_id']) !== '')
    {
      $tmp_val = trim(implode('', $params['value']));
      $caption_field = new caption_field($databox_field, $this, $params['meta_id']);

      if ($tmp_val === '')
      {
        $caption_field->delete();
        unset($caption_field);
      }
      else
      {
        $caption_field->set_value($params['value']);
      }
    }
    else
    {
      $caption_field = caption_field::create($databox_field, $this, $params['value']);
    }

    $this->caption_record = null;

    return $this;
  }

  /**
   *
   * @todo move this function to caption_record
   * @param array $metadatas
   * @return record_adapter
   */
  public function set_metadatas(Array $metadatas)
  {
    foreach ($metadatas as $param)
    {
      if (!is_array($param))
        throw new Exception_InvalidArgument();

      $this->set_metadata($param, $this->databox);
    }

    $this->xml = null;
    $this->caption_record = null;

    $xml = new DOMDocument();
    $xml->loadXML($this->get_xml());

    $this->set_xml($xml);
    $this->reindex();

    unset($xml);

    return $this;
  }

  /**
   * Reindex the record
   *
   * @return record_adapter
   */
  public function reindex()
  {
    $connbas = connection::getPDOConnection($this->get_sbas_id());
    $sql = 'UPDATE record SET status=(status & ~7 | 4)
            WHERE record_id= :record_id';
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':record_id' => $this->record_id));
    $this->delete_data_from_cache(self::CACHE_STATUS);

    return $this;
  }

  /**
   *
   * @return record_adapter
   */
  public function rebuild_subdefs()
  {
    $connbas = connection::getPDOConnection($this->get_sbas_id());
    $sql = 'UPDATE record SET jeton=(jeton | ' . JETON_MAKE_SUBDEF . ') WHERE record_id = :record_id';
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':record_id' => $this->get_record_id()));

    return $this;
  }

  /**
   *
   * @return record_adapter
   */
  public function write_metas()
  {
    $connbas = connection::getPDOConnection($this->get_sbas_id());
    $sql = 'UPDATE record
            SET jeton = ' . (JETON_WRITE_META_DOC | JETON_WRITE_META_SUBDEF) . '
            WHERE record_id= :record_id';
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':record_id' => $this->record_id));

    return $this;
  }

  /**
   *
   * @param string $status
   * @return record_adapter
   */
  public function set_binary_status($status)
  {
    $connbas = connection::getPDOConnection($this->get_sbas_id());

    $registry = registry::get_instance();
    $sql = 'UPDATE record SET status = 0b' . $status . '
            WHERE record_id= :record_id';
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':record_id' => $this->record_id));

    $sql = 'REPLACE INTO status (id, record_id, name, value) VALUES (null, :record_id, :name, :value)';
    $stmt = $connbas->prepare($sql);

    $status = strrev($status);
    for ($i = 4; $i < strlen($status); $i++)
    {
      $stmt->execute(array(
          ':record_id' => $this->get_record_id(),
          ':name' => $i,
          ':value' => $status[$i]
      ));
    }
    $stmt->closeCursor();

    try
    {
      $sphinx = sphinxrt::get_instance($registry);

      $sbas_params = phrasea::sbas_params();
      $sbas_id = $this->get_sbas_id();
      if (isset($sbas_params[$sbas_id]))
      {
        $params = $sbas_params[$sbas_id];
        $sbas_crc = crc32(str_replace(array('.', '%'), '_', sprintf('%s_%s_%s_%s', $params['host'], $params['port'], $params['user'], $params['dbname'])));
        $sphinx->update_status(array("metadatas" . $sbas_crc, "metadatas" . $sbas_crc . "_stemmed_en", "metadatas" . $sbas_crc . "_stemmed_fr", "documents" . $sbas_crc), $this->get_sbas_id(), $this->get_record_id(), strrev($status));
      }
    }
    catch (Exception $e)
    {
      
    }
    $this->delete_data_from_cache(self::CACHE_STATUS);


    return $this;
  }

  /**
   *
   * @return string
   */
  public function get_reg_name()
  {
    if (!$this->is_grouping())
      return false;

    $balisename = '';

    $struct = $this->databox->get_structure();

    if ($sxe = simplexml_load_string($struct))
    {
      $z = $sxe->xpath('/record/description');
      if ($z && is_array($z))
      {
        foreach ($z[0] as $ki => $vi)
        {
          if ($vi['regname'] == '1')
          {
            $balisename = $ki;
            break;
          }
        }
      }
    }
    $regname = '';
    if ($sxe = simplexml_load_string($this->get_xml()))
      $regname = (string) $sxe->description->$balisename;

    return $regname;
  }

  /**
   *
   * @return string
   */
  public function get_bitly_link()
  {

    $registry = registry::get_instance();

    if ($this->bitly_link !== null)
      return $this->bitly_link;

    $this->bitly_link = false;

    if (trim($registry->get('GV_bitly_user')) == ''
            && trim($registry->get('GV_bitly_key')) == '')
      return $this->bitly_link;

    try
    {
      $short = new PHPShortener();
      $bitly = $short->encode($url . 'view/', 'bit.ly', $registry);

      if (preg_match('/^(http:\/\/)?(www\.)?([^\/]*)\/(.*)$/', $bitly, $results))
      {
        if ($results[3] && $results[4])
        {
          $hash = 'http://bit.ly/' . $results[4];
          $sql = 'UPDATE record SET bitly = :hash WHERE record_id = :record_id';

          $connbas = connection::getPDOConnection($this->get_sbas_id());
          $stmt = $connbas->prepare($sql);
          $stmt->execute(array(':hash' => $hash, ':record_id' => $this->get_record_id()));
          $stmt->closeCursor();

          $this->bitly_link = 'http://bit.ly/' . $hash;
        }
      }
    }
    catch (Exception $e)
    {
      unset($e);
    }
    $this->delete_data_from_cache();

    return $this->bitly_link;
  }

  /**
   *
   * @param collection $collection
   * @param system_file $system_file
   * @param string $original_name
   * @param boolean $is_grouping
   * @return record_adapter
   */
  public static function create(collection $collection, system_file &$system_file, $original_name=false, $is_grouping = false)
  {
    $type = $system_file->get_phrasea_type();

    if ($is_grouping)
    {
      $uuid = uuid::generate_v4();
      $sha256 = null;
    }
    else
    {
      $uuid = $system_file->read_uuid();
      if (!uuid::is_valid($uuid))
      {
        $uuid = uuid::generate_v4();
      }
      $sha256 = $system_file->get_sha256();
    }

    if (!$original_name)
      $original_name = $system_file->getFilename();

    $databox = $collection->get_databox();
    $sbas_id = $databox->get_sbas_id();
    $coll_id = $collection->get_coll_id();

    $connbas = $databox->get_connection();

    $sql = 'INSERT INTO record
              (coll_id, record_id, parent_record_id, moddate, credate
                , jeton, type, sha256, uuid, originalname, mime)
            VALUES
              (:coll_id, null, :parent_record_id, NOW(), NOW()
              , ' . JETON_MAKE_SUBDEF . ' , :type, :sha256, :uuid
              , :originalname, :mime)';

    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(
        ':coll_id' => $coll_id
        , ':parent_record_id' => ($is_grouping ? 1 : 0)
        , ':type' => $type
        , ':sha256' => $sha256
        , ':uuid' => $uuid
        , ':originalname' => $original_name
        , ':mime' => $system_file->get_mime()
    ));

    $record_id = $connbas->lastInsertId();
    $record = new self($sbas_id, $record_id);

    try
    {
      $appbox = appbox::get_instance();
      $session = $appbox->get_session();
      $log_id = $session->get_logger($databox)->get_id();

      $sql = 'INSERT INTO log_docs (id, log_id, date, record_id, action, final, comment)
            VALUES (null, :log_id, now(),
              :record_id, "add", :coll_id,"")';
      $stmt = $connbas->prepare($sql);
      $stmt->execute(array(
          ':log_id' => $log_id,
          ':record_id' => $record_id,
          ':coll_id' => $coll_id
      ));
      $stmt->closeCursor();
    }
    catch (Exception $e)
    {
      unset($e);
    }

    $pathhd = trim($databox->get_sxml_structure()->path);
    $pathhd = databox::dispatch($pathhd);

    system_file::mkdir($pathhd);

    $newname = $record->get_record_id() . "_document." . $system_file->get_extension();
    if (!copy($system_file->getPathname(), $pathhd . $newname))
    {
      throw new Exception('Unable to write file');
    }

    $system_file2 = new system_file($pathhd . $newname);
    $system_file2->write_uuid($uuid);

    media_subdef::create($record, 'document', $system_file2);

    $record->delete_data_from_cache(record_adapter::CACHE_SUBDEFS);

    $tc_datas = $system_file->get_technical_datas();

    $sql = 'REPLACE INTO technical_datas (id, record_id, name, value)
        VALUES (null, :record_id, :name, :value)';
    $stmt = $connbas->prepare($sql);

    foreach ($tc_datas as $name => $value)
    {
      if (is_null($value))
        continue;

      $stmt->execute(array(
          ':record_id' => $record_id
          , ':name' => $name
          , ':value' => $value
      ));
    }
    $stmt->closeCursor();

    return $record;
  }

  /**
   *
   * @param int $base_id
   * @param int $record_id
   * @param string $sha256
   * @return record_adapter
   */
  public static function get_record_by_sha($sbas_id, $sha256, $record_id = null)
  {
    $conn = connection::getPDOConnection($sbas_id);

    $sql = "SELECT record_id
            FROM record r
            WHERE sha256 IS NOT NULL
              AND sha256 = :sha256";

    $params = array(':sha256' => $sha256);

    if (!is_null($record_id))
    {
      $sql .= ' AND record_id = :record_id';
      $params[':record_id'] = $record_id;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $records = array();

    foreach ($rs as $row)
    {
      $k = count($records);
      $records[$k] = new record_adapter($sbas_id, $row['record_id']);
    }

    return $records;
  }

  /**
   *
   * @return system_file
   */
  public function get_hd_file()
  {
    $hd = $this->get_subdef('document');
    if ($hd->is_physically_present())
      return new system_file(p4string::addEndSlash($hd->get_path()) . $hd->get_file());
    return null;
  }

  /**
   *
   * @return Array : list of deleted files;
   */
  public function delete()
  {
    $connbas = $this->get_databox()->get_connection();
    $sbas_id = $this->get_databox()->get_sbas_id();
    $appbox = appbox::get_instance();
    $registry = $appbox->get_registry();
    $conn = $appbox->get_connection();

    $ftodel = array();
    foreach ($this->get_subdefs() as $subdef)
    {
      if (!$subdef->is_physically_present())
        continue;

      $ftodel[] = $subdef->get_pathfile();
      $watermark = $subdef->get_path() . 'watermark_' . $subdef->get_file();
      if (file_exists($watermark))
        $ftodel[] = $watermark;
      $stamp = $subdef->get_path() . 'stamp_' . $subdef->get_file();
      if (file_exists($stamp))
        $ftodel[] = $stamp;
    }

    $origcoll = phrasea::collFromBas($this->get_base_id());

    $appbox->get_session()->get_logger($this->get_databox())
            ->log($this, Session_Logger::EVENT_DELETE, $origcoll, $this->get_xml());

    $sql = "DELETE FROM record WHERE record_id = :record_id";
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':record_id' => $this->get_record_id()));
    $stmt->closeCursor();


    $sql = 'SELECT id FROM metadatas WHERE record_id = :record_id';
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':record_id' => $this->get_record_id()));
    $rs = $stmt->fetchAll();
    $stmt->closeCursor();

    try
    {
      $sphinx_rt = sphinxrt::get_instance($registry);

      $sbas_params = phrasea::sbas_params();

      if (isset($sbas_params[$sbas_id]))
      {
        $params = $sbas_params[$sbas_id];
        $sbas_crc = crc32(str_replace(array('.', '%'), '_', sprintf('%s_%s_%s_%s', $params['host'], $params['port'], $params['user'], $params['dbname'])));
        foreach ($rs as $row)
        {
          $sphinx_rt->delete(array("metadatas" . $sbas_crc, "metadatas" . $sbas_crc . "_stemmed_en", "metadatas" . $sbas_crc . "_stemmed_fr"), "metas_realtime" . $sbas_crc, $row['id']);
        }
        $sphinx_rt->delete(array("documents" . $sbas_crc, "documents" . $sbas_crc . "_stemmed_fr", "documents" . $sbas_crc . "_stemmed_en"), "docs_realtime" . $sbas_crc, $this->get_record_id());
      }
    }
    catch (Exception $e)
    {
      unset($e);
    }

    $sql = "DELETE FROM metadatas WHERE record_id = :record_id";
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':record_id' => $this->get_record_id()));
    $stmt->closeCursor();

    $sql = "DELETE FROM prop WHERE record_id = :record_id";
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':record_id' => $this->get_record_id()));
    $stmt->closeCursor();

    $sql = "DELETE FROM idx WHERE record_id = :record_id";
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':record_id' => $this->get_record_id()));
    $stmt->closeCursor();

    $sql = "DELETE FROM permalinks
            WHERE subdef_id
              IN (SELECT subdef_id FROM subdef WHERE record_id=:record_id)";
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':record_id' => $this->get_record_id()));
    $stmt->closeCursor();

    $sql = "DELETE FROM subdef WHERE record_id = :record_id";
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':record_id' => $this->get_record_id()));
    $stmt->closeCursor();

    $sql = "DELETE FROM technical_datas WHERE record_id = :record_id";
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':record_id' => $this->get_record_id()));
    $stmt->closeCursor();

    $sql = "DELETE FROM thit WHERE record_id = :record_id";
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':record_id' => $this->get_record_id()));
    $stmt->closeCursor();

    $sql = "DELETE FROM regroup WHERE rid_parent = :record_id";
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':record_id' => $this->get_record_id()));
    $stmt->closeCursor();

    $sql = "DELETE FROM regroup WHERE rid_child = :record_id";
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':record_id' => $this->get_record_id()));
    $stmt->closeCursor();

    $sql = 'SELECT s.ssel_id, c.sselcont_id, s.usr_id
            FROM sselcont c, ssel s
            WHERE c.base_id = :base_id AND c.record_id = :record_id
              AND s.ssel_id = c.ssel_id';
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':record_id' => $this->get_record_id(), ':base_id' => $this->get_base_id()));
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rs as $row)
    {
      $basket = basket_adapter::getInstance($appbox, $row['ssel_id'], $row['usr_id']);
      $basket->remove_from_ssel($row['sselcont_id']);
    }

    $stmt->closeCursor();

    foreach ($ftodel as $f)
      @unlink($f);

    $this->delete_data_from_cache(self::CACHE_SUBDEFS);

    return array_keys($ftodel);
  }

  /**
   *
   * @param string $option optionnal cache name
   * @return string
   */
  public function get_cache_key($option = null)
  {
    return 'record_' . $this->get_serialize_key() . ($option ? '_' . $option : '');
  }

  /**
   *
   * @param databox $databox
   */
  public function generate_subdefs(databox $databox, Array $wanted_subdefs = null, $log_details = false)
  {
    $available_subdefs = $databox->get_subdef_structure();

    $subdefs = array();

    foreach ($available_subdefs as $groupname => $subdefgroup)
    {
      if ($this->get_type() == $groupname)
      {
        $subdefs = $subdefgroup;
        break;
      }
    }

    if (count($subdefs) == 0)
    {
      if ($log_details)
        echo 'Aucune sous definition a faire pour ' . $this->get_type() . "\n";
    }

    $subdef_class = 'databox_subdef' . ucfirst($this->get_type());
    $record_subdefs = $this->get_subdefs();

    foreach ($subdefs as $subdef)
    {
      $subdefname = $subdef->get_name();

      if (is_array($wanted_subdefs) && !in_array($subdefname, $wanted_subdefs))
      {
        continue;
      }
      $pathdest = false;

      if (isset($record_subdefs[$subdefname]) && $record_subdefs[$subdefname]->is_physically_present())
      {
        $pathdest = $record_subdefs[$subdefname]->get_pathfile();
        if (!is_file($pathdest))
          $pathdest = false;
      }
      try
      {
        $this->generate_subdef($subdef, $pathdest);
      }
      catch (Exception $e)
      {
        if ($log_details)
          echo $e->getMessage() . "\n";
      }

      if (!array_key_exists($subdefname, $record_subdefs))
      {
        continue;
      }

      $record_subdefs[$subdefname]->delete_data_from_cache();

      $this->delete_data_from_cache(self::CACHE_SUBDEFS);
      try
      {
        $subdef = $this->get_subdef($subdefname);
        if ($subdef instanceof media_subdef)
        {
          $permalink = $subdef->get_permalink();
          if ($permalink instanceof media_Permalink_Adapter)
            $permalink->delete_data_from_cache();
        }
      }
      catch (Exception $e)
      {
        
      }
      $this->delete_data_from_cache(self::CACHE_SUBDEFS);
    }

    return $this;
  }

  /**
   *
   * @todo move to media_subdef class
   * @param databox_subdefInterface $subdef_class
   * @param string $pathdest
   */
  protected function generate_subdef(databox_subdefInterface $subdef_class, $pathdest)
  {
    $registry = registry::get_instance();
    $generated = $subdef_class->generate($this, $pathdest, $registry);

    return $this;
  }

  /**
   *
   * @param string $option optionnal cache name
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
    switch ($option)
    {
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
    $connbas = connection::getPDOConnection($this->get_sbas_id());

    $sql = 'INSERT INTO log_view (id, log_id, date, record_id, referrer, site_id)
            VALUES
            (null, :log_id, now(), :rec, :referrer, :site)';

    $params = array(
        ':log_id' => $log_id
        , ':rec' => $this->get_record_id()
        , ':referrer' => $referrer
        , ':site' => $gv_sit
    );
    $stmt = $connbas->prepare($sql);
    $stmt->execute($params);
    $stmt->closeCursor();

    return $this;
  }

  public function rotate_subdefs($angle)
  {
    $registry = registry::get_instance();
    foreach ($this->get_subdefs() as $name => $subdef)
    {
      if ($name == 'document')
        continue;

      try
      {
        $subdef->rotate($registry, $angle);
      }
      catch (Exception $e)
      {
        
      }
    }

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
  public function get_container_baskets()
  {
    if ($this->container_basket)
      return $this->container_basket;

    $appbox = appbox::get_instance();
    $session = $appbox->get_session();

    $baskets = array();
    $sql = 'SELECT s.ssel_id FROM ssel s, sselcont c
            WHERE s.ssel_id = c.ssel_id
              AND c.base_id = :base_id AND record_id = :record_id
              AND usr_id = :usr_id AND temporaryType="0"';

    $params = array(
        ':base_id' => $this->get_base_id()
        , ':record_id' => $this->get_record_id()
        , ':usr_id' => $session->get_usr_id()
    );

    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute($params);
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach ($rs as $row)
    {
      $baskets[$row['ssel_id']] = basket_adapter::getInstance($appbox, $row['ssel_id'], $session->get_usr_id());
    }

    $this->container_basket = $baskets;

    return $this->container_basket;
  }

  /**
   *
   * @param databox $databox
   * @param int $offset_start
   * @param int $how_many
   * @return type
   */
  public static function get_records_by_originalname(databox $databox, $original_name, $offset_start=0, $how_many=10)
  {
    $offset_start = (int) ($offset_start < 0 ? 0 : $offset_start);
    $how_many = (int) (($how_many > 20 || $how_many < 1) ? 10 : $how_many);

    $sql = sprintf('SELECT record_id FROM record
            WHERE original_name = :original_name LIMIT %d, %d'
            , $offset_start, $how_many);

    $stmt = $databox->get_connection()->prepare($sql);
    $stmt->execute(array(':original_name' => $original_name));
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $records = array();
    foreach ($rs as $row)
    {
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
    if (!$this->is_grouping())
      throw new Exception('This record is not a grouping');

    $appbox = appbox::get_instance();

    $sql = 'SELECT record_id
              FROM regroup g
                INNER JOIN (record r
                  INNER JOIN collusr c
                    ON site = :GV_site
                      AND usr_id = :usr_id
                      AND c.coll_id = r.coll_id
                      AND ((status ^ mask_xor) & mask_and) = 0
                      AND r.parent_record_id=0
                )
                ON (g.rid_child = r.record_id AND g.rid_parent = :record_id)
              ORDER BY g.ord ASC, dateadd ASC, record_id ASC';

    $params = array(
        ':GV_site' => $appbox->get_registry()->get('GV_sit')
        , ':usr_id' => $appbox->get_session()->get_usr_id()
        , ':record_id' => $this->get_record_id()
    );

    $stmt = $this->get_databox()->get_connection()->prepare($sql);
    $stmt->execute($params);
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $set = new set_selection();
    $i = 1;
    foreach ($rs as $row)
    {
      $set->add_element(new record_adapter($this->get_sbas_id(), $row['record_id'], $i));
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
    $appbox = appbox::get_instance();

    $sql = 'SELECT r.record_id
            FROM regroup g
              INNER JOIN (record r
                INNER JOIN collusr c
                  ON site = :GV_site
                    AND usr_id = :usr_id
                    AND c.coll_id = r.coll_id
                    AND ((status ^ mask_xor) & mask_and)=0
                    AND r.parent_record_id = 1
              )
              ON (g.rid_parent = r.record_id)
            WHERE rid_child = :record_id';


    $params = array(
        ':GV_site' => $appbox->get_registry()->get('GV_sit')
        , ':usr_id' => $appbox->get_session()->get_usr_id()
        , ':record_id' => $this->get_record_id()
    );

    $stmt = $this->get_databox()->get_connection()->prepare($sql);
    $stmt->execute($params);
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $set = new set_selection();
    foreach ($rs as $row)
    {
      $set->add_element(new record_adapter($this->get_sbas_id(), $row['record_id']));
    }

    return $set;
  }

}
