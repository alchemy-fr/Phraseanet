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
 * moovRelocator progressive mp4 processor for binaryAdapter package
 *
 * @package     binaryAdapter
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class binaryAdapter_video_progressive_moovRelocator extends binaryAdapter_video_processorAbstract implements binaryAdapter_processorInterface
{

  /**
   *
   * @var array
   */
  protected $options = array(
      'size' => null
  );
  /**
   *
   * @var string
   */
  protected $binary_name = 'MoovRelocator';
  /**
   *
   * @var string
   */
  protected $binary = '/usr/bin/php';

  /**
   *
   * @param system_file $origine
   * @param string $dest
   * @return system_file
   */
  protected function process(system_file $origine, $dest)
  {
    $mp4_file = $origine->getPathname();

    $moovrelocator = moov_relocator::getInstance();

    $ret = $moovrelocator->setInput($mp4_file);

    if ($ret !== true)
      throw new Exception('File format not ok');

    $ret = $moovrelocator->setOutput($dest);

    if ($ret !== true)
      throw new Exception('File output error');

    $ret = $moovrelocator->fix();

    if ($ret !== true)
      throw new Exception('Error while fixing');

    return $this;
  }

}
