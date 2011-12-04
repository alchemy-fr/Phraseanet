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
 * MP4Box progressive mp4 processor for binaryAdapter package
 *
 * @package     binaryAdapter
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class binaryAdapter_video_progressive_mp4box extends binaryAdapter_video_processorAbstract implements binaryAdapter_processorInterface
{

  /**
   *
   * @var array
   */
  protected $options = array();
  /**
   *
   * @var string
   */
  protected $binary_name = 'GV_mp4box';

  /**
   *
   * @param system_file $origine
   * @param string $dest
   * @return system_file
   */
  protected function process(system_file $origine, $dest)
  {
    $cmd = sprintf("%s -inter 0.5 %s -out %s"
                    , $this->binary
                    , $this->escapeshellargs($origine->getPathname())
                    , $this->escapeshellargs($dest));

    $this->shell_cmd($cmd);

    return $this;
  }

}
