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
 * FFMPEG image from video extractor processor for binaryAdapter package
 *
 * @package     binaryAdapter
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class binaryAdapter_video_toimage_ffmpeg extends binaryAdapter_video_processorAbstract implements binaryAdapter_processorInterface
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
  protected $binary_name = 'GV_ffmpeg';

  /**
   *
   * @param system_file $origine
   * @param string $dest
   * @return system_file
   */
  protected function process(system_file $origine, $dest)
  {
    $tmpDir = $this->registry->get('GV_RootPath')
            . 'tmp/' . 'tmp' . time() . '/';
    system_file::mkdir($tmpDir);

    $tmpFile = $tmpDir . 'extract-tmp.jpg';

    $tech_datas = $origine->get_technical_datas();

    $time_tot = $tech_datas[system_file::TC_DATAS_DURATION];
    $time_cut = max(round($tech_datas[system_file::TC_DATAS_DURATION] * 0.6), 1);

    $system = system_server::get_platform();


    if ($this->debug)
      $this->log("Duree totale : " . $tech_datas[system_file::TC_DATAS_DURATION]);

    $dimensions = $this->get_dimensions($origine, $this->options['size']);

    $newHeight = $dimensions['height'];
    $newWidth = $dimensions['width'];

    $system = system_server::get_platform();

    if ($system == 'WINDOWS')
    {
      $cmd = $this->binary
              . ' -i ' . $this->escapeshellargs($origine->getPathname())
              . ' -s ' . $newWidth . 'x' . $newHeight
              . ' -vframes 1 -ss ' . $time_cut
              . '  -f image2 ' . $this->escapeshellargs($tmpFile);
    }
    else
    {
      $cmd = $this->binary
              . ' -i ' . $this->escapeshellargs($origine->getPathname())
              . ' -s ' . $newWidth . 'x' . $newHeight
              . ' -vframes 1 -ss ' . $time_cut
              . '  -f image2 ' . $this->escapeshellargs($tmpFile);
    }

    $this->shell_cmd($cmd);

    if ($this->debug)
      $this->log("Commande executee : $cmd \n");

    if (!file_exists($tmpFile))
      throw new Exception('Unable to extract image');

    $cmd = $this->registry->get('GV_pathcomposite')
            . ' -gravity SouthEast -quiet -compose over "'
            . $this->registry->get('GV_RootPath') . 'www/skins/icons/play.png"'
            . ' ' . $this->escapeshellargs($tmpFile)
            . ' ' . $this->escapeshellargs($dest);

    $this->shell_cmd($cmd);
    unlink($tmpFile);
    rmdir($tmpDir);

    return $this;
  }

}
