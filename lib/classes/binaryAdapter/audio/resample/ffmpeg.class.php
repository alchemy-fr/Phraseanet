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
 * @package     binaryAdapter
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class binaryAdapter_audio_resample_ffmpeg extends binaryAdapter_processorAbstract
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
  protected $binary_name = 'GV_ffmpeg';

  /**
   *
   * @param system_file $origine
   * @param string $dest
   * @return binaryAdapter_audio_resample_ffmpeg
   */
  protected function process(system_file $origine, $dest)
  {
    $cmd = sprintf('%s -y -i %s %s'
                    , $this->binary
                    , $this->escapeshellargs($origine->getPathname())
                    , $this->escapeshellargs($dest)
    );

    $this->shell_cmd($cmd);

    return $this;
  }

}
