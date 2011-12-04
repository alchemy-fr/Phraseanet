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
class databox_subdef_image extends databox_subdefAbstract implements databox_subdefInterface
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
   * @param string $dest
   * @param registry $registry
   * @return system_file
   */
  protected function generate_image(record_Interface &$record, $dest_dir, registry &$registry)
  {
    $binary_temp = false;
    $origine = $record->get_hd_file();

    $outfile = $this->get_newpathfile_name($record, $dest_dir, 'jpg');
    if (file_exists($outfile))
      unlink($outfile);

    try
    {
      $tmp_file = $registry->get('GV_RootPath') . 'tmp/preview' . time() . '.jpg';

      $adapter = new binaryAdapter_rawImage_previewExtract($registry);
      $origine = $adapter->execute($origine, $tmp_file, $this->get_options());
      $this->log('extracted a preview from the raw file');
      $binary_temp = $origine->getPathname();
    }
    catch (Exception $e)
    {
      $this->log($e->getMessage());
    }

    $adapter = new binaryAdapter_image_resize($registry);
    $resized = $adapter->execute($origine, $outfile, $this->get_options());

    if ($binary_temp)
    {
      unlink($binary_temp);
    }

    if ($resized instanceof system_file)
    {
      return $resized;
    }
    throw new Exception('Unable to create resized copy');
  }

}
