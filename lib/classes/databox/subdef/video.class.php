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
class databox_subdef_video extends databox_subdefAbstract implements databox_subdefInterface
{

  /**
   *
   * @var Array
   */
  protected $available_mediatypes = array('image', 'video', 'gif');

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
      case 'video':
        $generated = $this->generate_video($record, $dest_dir, $registry);
        break;
      case 'gif':
        $generated = $this->generate_gif($record, $dest_dir, $registry);
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
  protected function generate_image(record_Interface $record, $dest_dir, registry $registry)
  {
    $outfile = $this->get_newpathfile_name($record, $dest_dir, 'jpg');
    if (file_exists($outfile))
      unlink($outfile);

    $adapter = new binaryAdapter_video_toimage($registry);
    $resized = $adapter->execute($record->get_hd_file(), $outfile, $this->get_options());

    if ($resized instanceof system_file)
    {
      return $resized;
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
  protected function generate_video(record_Interface $record, $dest_dir, registry $registry)
  {
    $outfile = $this->get_newpathfile_name($record, $dest_dir, 'mp4');

    $temp = $this->generate_temporary_video($record, $registry);
    $resized = $this->make_progressive($temp, $outfile, $registry);
    unlink($temp->getPathname());

    if ($resized instanceof system_file)
    {
      return $resized;
    }
    throw new Exception('Unable to create video');
  }

  /**
   *
   * @param registry $registry
   * @return system_file
   */
  protected function generate_temporary_video(record_Interface $record, registry $registry)
  {
    $outfile = $registry->get('GV_RootPath') . 'tmp/tmp_video' . time() . '.mp4';

    $adapter = new binaryAdapter_video_resize($registry);
    $resized = $adapter->execute($record->get_hd_file(), $outfile, $this->get_options());

    if ($resized instanceof system_file)
    {
      return $resized;
    }
    throw new Exception('Unable to create resized video');
  }

  /**
   *
   * @param system_file $origine
   * @param string $path_dest
   * @param registry $registry
   * @return system_file
   */
  protected function make_progressive(system_file $origine, $path_dest, registry $registry)
  {
    if (file_exists($path_dest))
      unlink($path_dest);

    $adapter = new binaryAdapter_video_progressive($registry);
    $resized = $adapter->execute($origine, $path_dest, $this->get_options());

    if ($resized instanceof system_file)
    {
      return $resized;
    }
    throw new Exception('Unable make mp4 progressive');
  }

  /**
   *
   * @param record_Interface $record
   * @param <type> $dest_dir
   * @param registry $registry
   * @return system_file
   */
  protected function generate_gif(record_Interface $record, $dest_dir, registry $registry)
  {
    $outfile = $this->get_newpathfile_name($record, $dest_dir, 'gif');
    if (file_exists($outfile))
      unlink($outfile);

    $adapter = new binaryAdapter_video_gif($registry);
    $resized = $adapter->execute($record->get_hd_file(), $outfile, $this->get_options());

    if ($resized instanceof system_file)
    {
      return $resized;
    }
    throw new Exception('Unable to create resized video');
  }

}
