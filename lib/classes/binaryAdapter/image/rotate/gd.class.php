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
 * GD image resize processor for binaryAdapter package
 *
 * @package     binaryAdapter
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class binaryAdapter_image_rotate_gd extends binaryAdapter_processorAbstract
{

  /**
   *
   * @var array
   */
  protected $options = array(
      'angle' => 90
  );
  protected $binary_name = 'GD';

  /**
   *
   * @param registry $registry
   * @return binaryAdapter_image_resize_gd
   */
  public function __construct(registry $registry)
  {
    $this->binary = function_exists('imagecreate');
    if (!$this->binary)
    {
      throw new Exception('GD not installed');
    }
    parent::__construct($registry);

    return $this;
  }

  /**
   *
   * @param string $name
   * @param string $value
   * @return binaryAdapter_image_resize_gd
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

    $fichier = $origine->getPathname();
    $source = imagecreatefromjpeg($fichier);
    $rot = $this->options['angle'] * -1;
    $source = imagerotate($source, $rot, 0);
    imagejpeg($source, $dest, 90);
    imagedestroy($source);


    return $this;
  }

}
