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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class databox_subdefAbstract
{

  /**
   *
   * @var boolean
   */
  protected $debug = false;
  /**
   *
   * @var string
   */
  protected $class;
  /**
   *
   * @var string
   */
  protected $name;
  /**
   *
   * @var string
   */
  protected $path;
  /**
   *
   * @var string
   */
  protected $baseurl;
  /**
   *
   * @var string
   */
  protected $mediatype;
  /**
   *
   * @var array
   */
  protected $labels = array();
  /**
   *
   * @var boolean
   */
  protected $write_meta;
  /**
   *
   * @return string
   */
  protected $current_mediatype;
  /**
   *
   * @var boolean
   */
  protected $downloadable;

  const CLASS_THUMBNAIL = 'thumbnail';

  const CLASS_PREVIEW = 'preview';

  const CLASS_DOCUMENT = 'document';

  /**
   *
   * @return string
   */
  public function get_class()
  {
    return $this->class;
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
  public function get_mediatype()
  {
    return $this->current_mediatype;
  }

  /**
   *
   * @return Array
   */
  public function get_labels()
  {
    return $this->labels;
  }

  /**
   *
   * @param SimpleXMLElement $sd
   * @return databox_subdefAbstract
   */
  public function __construct(SimpleXMLElement $sd)
  {

    $this->class = (string) $sd->attributes()->class;
    $this->name = (string) strtolower($sd->attributes()->name);
    $this->downloadable = p4field::isyes((string) $sd->attributes()->downloadable);
    $this->path = trim($sd->path) !== '' ? p4string::addEndSlash(trim($sd->path)) : '';

    $this->baseurl = trim($sd->baseurl) !== '' ?
            p4string::addEndSlash(trim($sd->baseurl)) : false;
    $this->current_mediatype = (string) $sd->mediatype;
    switch ($this->current_mediatype)
    {
      case 'image':
      default:
        $size = (int) $sd->size;
        $resolution = trim($sd->dpi);
        $strip = p4field::isyes((string) $sd->strip);
        $quality = trim($sd->quality);
        $this->mediatype = new databox_subdef_mediatype_image($size, $resolution, $strip, $quality);
        break;
      case 'audio':
        $this->mediatype = new databox_subdef_mediatype_audio();
        break;
      case 'video':
        $fps = (int) $sd->fps;
        $threads = (int) $sd->threads;
        $bitrate = (int) $sd->bitrate;
        $vcodec = trim($sd->vcodec);
        $acodec = trim($sd->acodec);
        $size = (int) $sd->size;
        $this->mediatype = new databox_subdef_mediatype_video($size, $fps, $threads, $bitrate, $acodec, $vcodec);
        break;
      case 'gif':
        $delay = (int) $sd->delay;
        $size = (int) $sd->size;
        $this->mediatype = new databox_subdef_mediatype_gif($size, $delay);
        break;
      case 'flexpaper':
        $this->mediatype = new databox_subdef_mediatype_flexpaper();
        break;
    }

    $this->write_meta = p4field::isyes((string) $sd->meta);

    foreach ($sd->label as $label)
    {
      $lang = trim((string) $label->attributes()->lang);
      if ($lang)
        $this->labels[$lang] = (string) $label;
    }

    return $this;
  }

  public function is_downloadable()
  {
    return $this->downloadable;
  }

  /**
   *
   * @return Array
   */
  public function get_mediatype_options()
  {
    $options = array();
    foreach ($this->available_mediatypes as $mediatype)
    {
      if ($mediatype == $this->current_mediatype)
      {
        $mediatype_obj = $this->mediatype;
      }
      else
      {
        try
        {
          $mediatype_class = 'databox_subdef_mediatype_' . $mediatype;
          $mediatype_obj = new $mediatype_class();
        }
        catch (Exception $e)
        {
          continue;
        }
      }
      $media_opt = $mediatype_obj->get_available_options($this);

      foreach ($media_opt as $opt_name => $values)
      {
        if (is_null($values['value']) || $values['value'] == '')
        {
          $values['value'] = $values['default'];
          $media_opt[$opt_name] = $values;
        }
      }
      $options[$mediatype] = $media_opt;
    }

    return $options;
  }

  /**
   * Tells us if we have to write meta datas in the subdef
   *
   * @return boolean
   */
  public function meta_writeable()
  {
    return $this->write_meta;
  }

  /**
   * logs
   *
   * @param <type> $message
   * @return databox_subdefAbstract
   */
  public function log($message)
  {
    if ($this->debug)
    {
      echo "\t --> \t" . $message . "\n";
    }

    return $this;
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
   * @param record $record
   * @param string $pathdest
   * @param registry $registry
   * @return media_subdef
   */
  public function generate(record_adapter &$record, $pathdest, registry &$registry)
  {
    $dest_dir = p4string::addEndSlash(dirname($pathdest));
    if (!$pathdest)
    {
      $dest_dir = databox::dispatch($this->path);
    }

    $baseurl = $this->baseurl ?
            $this->baseurl . substr($dest_dir, strlen($this->path)) : '';

    try
    {
      $generated = $this->generator_switcher($record, $dest_dir, $registry);
    }
    catch (Exception $e)
    {
      throw $e;
    }

    return media_subdef::create($record, $this->get_name(), $generated, $baseurl);
  }

  /**
   *
   * @param record $record
   * @param string $dest_dir
   * @param string $extension
   * @return string
   */
  protected function get_newpathfile_name(record_adapter &$record, $dest_dir, $extension)
  {
    return $dest_dir . $record->get_record_id() . '_'
    . $this->name . '.' . $extension;
  }

  /**
   *
   * @return <type>
   */
  public function get_options()
  {
    return $this->mediatype->get_options($this);
  }

  /**
   * Abstract generator switcher
   *
   * @param record_Interface $record
   * @param string $dest_dir
   * @param registry $registry
   * @return system_file
   */
  abstract protected function generator_switcher(record_Interface &$record, $dest_dir, registry &$registry);
}
