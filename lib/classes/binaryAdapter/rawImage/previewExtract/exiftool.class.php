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
class binaryAdapter_rawImage_previewExtract_exiftool extends binaryAdapter_processorAbstract
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
    if (!$raw_datas->is_raw_image())
      throw new Exception('Provided file is not raw image datas');

    $tmpFiles = array();

    $cmd = $this->binary;

    $thisFile = $tmpFiles[] = $this->registry->get('GV_RootPath')
            . 'tmp/' . time() . '-PI';

    $cmd .= sprintf(' -b -PreviewImage %s > %s'
                    , $this->escapeshellargs($raw_datas->getPathname())
                    , $this->escapeshellargs($thisFile)
    );

    $this->shell_cmd($cmd);

    $cmd = $this->binary;

    $thisFile = $tmpFiles[] = $this->registry->get('GV_RootPath')
            . 'tmp/' . time() . '-JP';

    $cmd .= sprintf(' -b -JpgFromRaw %s > %s'
                    , $this->escapeshellargs($raw_datas->getPathname())
                    , $this->escapeshellargs($thisFile)
    );

    $this->shell_cmd($cmd);

    $refSize = 0;
    $tmpFile = false;

    foreach ($tmpFiles as $file)
    {
      if (is_file($file) && filesize($file) > 0)
      {
        if (filesize($file) > $refSize)
        {
          $tmpFile = $file;
          $refSize = filesize($file);
        }
        else
          unlink($file);
      }
      else
        unlink($file);
    }
    if (!$tmpFile)
      throw new Exception('Unable to extract a preview for the raw imageFile');

    rename($tmpFile, $dest);

    return $this;
  }

}
