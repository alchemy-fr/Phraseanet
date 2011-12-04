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
class binaryAdapter_image_resize_gd extends binaryAdapter_processorAbstract
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

    if (!is_null($size) && !$origine->is_raw_image()
            && $tech_datas[system_file::TC_DATAS_WIDTH] < $size && $tech_datas[system_file::TC_DATAS_HEIGHT] < $size)
    {
      $size = max($tech_datas[system_file::TC_DATAS_WIDTH], $tech_datas[system_file::TC_DATAS_HEIGHT]);
    }

    $imag_original = imagecreatefromjpeg($origine->getPathname());

    if ($imag_original)
    {
      $w_doc = imagesx($imag_original);
      $h_doc = imagesy($imag_original);

      if ($w_doc < $size && $h_doc < $size)
      {
        $img_mini = imagecreatetruecolor($w_doc, $h_doc);
        imagecopy($img_mini, $imag_original, 0, 0, 0, 0, $w_doc, $h_doc);
      }
      else
      {
        if ($w_doc > $h_doc)
          $h_sub = (int) (($h_doc / $w_doc) * ($w_sub = $size));
        else
          $w_sub = (int) (($w_doc / $h_doc) * ($h_sub = $size));
        $img_mini = imagecreatetruecolor($w_sub, $h_sub);

        imagecopyresampled($img_mini, $imag_original, 0, 0, 0, 0,
                $w_sub, $h_sub, $w_doc, $h_doc);
      }

      if ($this->options['autorotate'])
      {
        switch ($tech_datas[system_file::TC_DATAS_ORIENTATION])
        {
          case 3:
            $img_mini = imagerotate($img_mini, 180, 0);
            break;
          case 6:
            $img_mini = imagerotate($img_mini, 270, 0);
            $z = $w_sub;
            $w_sub = $h_sub;
            $h_sub = $z;
            break;
          case 8:
            $img_mini = imagerotate($img_mini, 90, 0);
            $z = $w_sub;
            $w_sub = $h_sub;
            $h_sub = $z;
            break;
        }
      }

      $quality = $this->options['quality'];

      imagejpeg($img_mini, $dest, $quality);

      imagedestroy($img_mini);
      imagedestroy($imag_original);
    }

    return $this;
  }

}
