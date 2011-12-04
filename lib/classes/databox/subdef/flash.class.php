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
class databox_subdef_flash extends databox_subdefAbstract implements databox_subdefInterface
{

  /**
   *
   * @var Array
   */
  protected $available_mediatypes = array('image');

  /**
   *
   * @param record_Interface $record
   * @param string $dest_dir
   * @param registry $registry
   * @return system_file
   */
  protected function generator_switcher(record_Interface &$record, $dest_dir, registry &$registry)
  {
    switch ($this->current_mediatype)
    {
      case 'image':
      default:
        $generated = $this->generate_image($record, $dest_dir, $registry);
        break;
    }

    return $generated;
  }

  /**
   *
   * @param record_Interface $record
   * @param string $dest_dir
   * @param registry $registry
   * @return system_file
   */
  protected function generate_image(record_Interface &$record, $dest_dir, registry &$registry)
  {
    $pathdest = $this->get_newpathfile_name($record, $dest_dir, 'jpg');
    $tmp_file = $registry->get('GV_RootPath') . 'tmp/tmp_file-doc2pdf' . time() . '.pdf';

    $image = $exception = false;

    try
    {
      $pdf = $this->generate_image_from_swf($record, $tmp_file, $registry);
      $image = $this->resize_image($pdf, $pathdest, $registry);
    }
    catch (Exception $e)
    {
      $exception = $e;
    }

    if (is_file($tmp_file))
      unlink($tmp_file);

    if ($image instanceof system_file)
    {
      return $image;
    }
    throw new Exception('Unable to convert document to image : '
            . $exception->getMessage());
  }

  /**
   *
   * @param record_Interface $record
   * @param <type> $dest
   * @param registry $registry
   * @return system_file
   */
  protected function generate_image_from_swf(record_Interface &$record, $dest, registry &$registry)
  {
    @unlink($dest);

    $adapter = new binaryAdapter_flash_toimage($registry);
    $resized = $adapter->execute($record->get_hd_file(), $dest, $this->get_options());

    if ($resized instanceof system_file)
    {
      return $resized;
    }
    throw new Exception('Unable to create resized copy');
  }

  /**
   *
   * @param system_file $origine
   * @param string $pathdest
   * @param registry $registry
   * @return system_file
   */
  protected function resize_image(system_file $origine, $pathdest, registry &$registry)
  {
    $adapter = new binaryAdapter_image_resize($registry);
    $image = $adapter->execute($origine, $pathdest, $this->get_options());

    if ($image instanceof system_file)
    {
      return $image;
    }
    throw new Exception('Unable to convert pdf to image');
  }

}
