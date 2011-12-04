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
class binaryAdapter_image_rotate_imagemagick extends binaryAdapter_processorAbstract
{

  /**
   *
   * @var array
   */
  protected $options = array(
      'angle' => 90
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
      case 'angle':
        $value = (int) $value;
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
    $type = $origine->get_phrasea_type();

    if ($type !== 'image')
      throw new Exception('Cant rotate non image files');

    $cmd = $this->binary;

    $cmd .= ' -rotate ' . $this->options['angle'];

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
