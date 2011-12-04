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
class databox_subdef_audio extends databox_subdefAbstract implements databox_subdefInterface
{

  /**
   *
   * @var Array
   */
  protected $available_mediatypes = array('image', 'audio');

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
      case 'audio':
        $generated = $this->generate_audio($record, $dest_dir, $registry);
        break;
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
  protected function generate_audio(record_Interface &$record, $dest_dir, registry &$registry)
  {
    $outfile = $this->get_newpathfile_name($record, $dest_dir, 'mp3');
    if (file_exists($outfile))
      unlink($outfile);

    $adapter = new binaryAdapter_audio_resample($registry);
    $resampled = $adapter->execute($record->get_hd_file(), $outfile, $this->get_options());

    if ($resampled instanceof system_file)
    {
      return $resampled;
    }
    throw new Exception('Unable to create image from video');
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
    $outfile = $this->get_newpathfile_name($record, $dest_dir, 'jpg');
    if (file_exists($outfile))
      unlink($outfile);

    $adapter = new binaryAdapter_audio_previewExtract($registry);
    $image = $adapter->execute($record->get_hd_file(), $outfile, $this->get_options());

    $adapter = new binaryAdapter_image_resize($registry);
    $image = $adapter->execute($image, $outfile, $this->get_options());

    if ($image instanceof system_file)
    {
      return $image;
    }
    throw new Exception('Unable to create extract image from audio');
  }

}
