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
 * Exiftool preview extractor from raw images processor
 * for binaryAdapter package
 *
 * @package     binaryAdapter
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class binaryAdapter_audio_previewExtract_exiftool extends binaryAdapter_processorAbstract
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
  protected $binary_name = 'GV_exiftool';

  /**
   *
   * @param system_file $raw_datas
   * @param string $dest
   * @return system_file
   */
  protected function process(system_file $raw_datas, $dest)
  {
    $cmd = sprintf('%s -s -t %s'
                    , $this->binary
                    , $this->escapeshellargs($raw_datas->getPathname())
    );

    $out = $this->shell_cmd($cmd);

    if (!$out)
      throw new Exception('Unable to extract preview datas from audio');

    foreach ($out as $outP)
    {
      $infos = explode("\t", $outP);

      if (count($infos) != 2 || $infos[0] != 'Picture')
        continue;

      $this->log("Some preview datas found in audio file");

      $cmd = sprintf('%s -b -Picture %s > %s'
                      , $this->binary
                      , $this->escapeshellargs($raw_datas->getPathname())
                      , $this->escapeshellargs($dest)
      );

      $this->shell_cmd($cmd);

      if (filesize($dest) > 0)
      {
        return $this;
      }
      else
      {
        unlink($dest);
      }
    }
  }

}
