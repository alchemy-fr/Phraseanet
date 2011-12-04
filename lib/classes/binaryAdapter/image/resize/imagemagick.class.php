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
 * ImageMagick image resize processor for binaryAdapter package
 *
 * @package     binaryAdapter
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class binaryAdapter_image_resize_imagemagick extends binaryAdapter_processorAbstract
{

  /**
   *
   * @var array
   */
  protected $options = array(
      'size' => 200,
      'dpi' => null,
      'quality' => 75,
      'strip' => true,
      'autorotate' => true
  );
  /**
   *
   * @var string
   */
  protected $binary_name = 'GV_imagick';

  /**
   *
   * @param string $name
   * @param string $value
   * @return binaryAdapter_image_resize_imagemagick
   */
  protected function set_option($name, $value)
  {
    switch ($name)
    {
      case 'dpi':
        $value = (int) $value;
        $value = ($value <= 0 || $value > 32767) ? null : $value;
        break;
      case 'quality':
        $value = (int) $value;
        $value = ($value <= 0 || $value > 100) ? 75 : $value;
        break;
      case 'strip':
        $value = !!$value;
        break;
      case 'size':
        $value = (int) $value;
        $value = ($value <= 0 || $value > 32000) ? 200 : $value;
        break;
    }

    parent::set_option($name, $value);

    return $this;
  }

  /**
   *
   * @param system_file $origine
   * @param string $dest
   * @return system_file
   */
  protected function process(system_file $origine, $dest)
  {
    $tech_datas = $origine->get_technical_datas();

    $size = $this->options['size'];
    $width = isset($tech_datas[system_file::TC_DATAS_WIDTH]) ? $tech_datas[system_file::TC_DATAS_WIDTH] : null;
    $height = isset($tech_datas[system_file::TC_DATAS_HEIGHT]) ? $tech_datas[system_file::TC_DATAS_HEIGHT] : null;

    if (!is_null($size) && !$origine->is_raw_image() && $width < $size && $height < $size)
    {
      $size = max($width, $height);
    }

    $cmd = $this->binary;

    $cmd .= ' -colorspace RGB -flatten -alpha Off -quiet';

    if ($this->options['strip'])
      $cmd .= ' -strip';

    $cmd .= sprintf(' -quality %s', $this->options['quality']);

    if ($size)
    {
      $cmd .= sprintf(' -resize %sx%s', $size, $size);

      if (in_array(
                      $origine->get_mime(), array(
                  'application/pdf',
                  'application/postscript'
                      )
              )
      )
      {
        $cmd .= sprintf(' -geometry %sx%s', $size, $size);
      }
    }

    if ($this->options['dpi'])
    {
      $cmd .= sprintf(' -density %sx%s -units PixelsPerInch'
              , $this->options['dpi']
              , $this->options['dpi']
      );
    }

    if ($this->options['autorotate'])
    {
      switch ($tech_datas[system_file::TC_DATAS_ORIENTATION])
      {
        case 3:
          $cmd .= ' -rotate 180';
          break;
        case 6:
          $cmd .= ' -rotate 90';
          break;
        case 8:
          $cmd .= ' -rotate -90';
          break;
      }
    }

    $array = array(
        'image/tiff',
        'application/pdf',
        'image/psd',
        'image/vnd.adobe.photoshop',
        'image/photoshop',
        'image/ai',
        'image/illustrator',
        'image/vnd.adobe.illustrator'
    );

    if (in_array($origine->get_mime(), $array))
    {
      $cmd .= sprintf(' %s %s'
              , $this->escapeshellargs($origine->getPathname(), '[0]')
              , $this->escapeshellargs($dest)
      );
    }
    else
    {
      $cmd .= sprintf(' %s %s'
              , $this->escapeshellargs($origine->getPathname())
              , $this->escapeshellargs($dest)
      );
    }

    $this->shell_cmd($cmd);

    return $this;
  }

}
