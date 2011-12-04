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
 * FFMPEG video to processor for binaryAdapter package
 *
 * @package     binaryAdapter
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class binaryAdapter_video_gif_ffmpeg extends binaryAdapter_video_processorAbstract implements binaryAdapter_processorInterface
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
    $this->log('GENERATING anim gif');

    $tmpDir = $this->registry->get('GV_RootPath') . 'tmp/' . 'tmp' . time();
    system_file::mkdir($tmpDir);
    $tmpDir = p4string::addEndSlash($tmpDir);

    $dimensions = $this->get_dimensions($origine, $this->options['size']);
    $newHeight = $dimensions['height'];
    $newWidth = $dimensions['width'];

    $system = system_server::get_platform();

    if ($system == 'WINDOWS')
    {
      $cmd = $this->registry->get('GV_ffmpeg')
              . ' -i ' . $this->escapeshellargs($origine->getPathname())
              . ' -s ' . $newWidth . 'x' . $newHeight
              . ' -r 1 -f image2 '
              . $tmpDir . 'images%05d.jpg';
    }
    else
    {
      $cmd = $this->binary
              . ' -i ' . $this->escapeshellargs($origine->getPathname())
              . ' -s ' . $newWidth . 'x' . $newHeight
              . ' -r 1 -f image2 '
              . $this->escapeshellargs($tmpDir) . 'images%05d.jpg';
    }

    $this->shell_cmd($cmd);

    $files = array();


    foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tmpDir),
            RecursiveIteratorIterator::LEAVES_ONLY
    ) as $file)
    {
      if ($file->isDir() || strpos($file->getPathname(), '/.svn/') !== false)
      {
        continue;
      }
      if ($file->isFile())
      {
        $filename = $file->getFilename();
        $files[$filename] = $file->getPathname();
      }
    }

    ksort($files);

    $n = count($files);

    $inter = round(count($files) / 10);


    $i = 0;
    foreach ($files as $k => $file)
    {
      if ($i % $inter !== 0)
      {
        if (unlink($file))
          unset($files[$k]);
      }
      $i++;
    }

    $cmd = $this->registry->get('GV_imagick')
            . ' -delay 100 -loop 0   ' . $tmpDir . '*.jpg ' . $dest;
    $this->shell_cmd($cmd);

    foreach ($files as $file)
      unlink($file);

    rmdir($tmpDir);

    return $this;
  }

}
