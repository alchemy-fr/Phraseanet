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
 * SIPS image resize processor for binaryAdapter package
 *
 * @package     binaryAdapter
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class binaryAdapter_image_resize_sips extends binaryAdapter_processorAbstract
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
   * @param registry $registry
   * @return binaryAdapter_imageTransform_sips
   */
  public function __construct(registry $registry)
  {
    throw new Exception('Need to work');
    try
    {
      $system = system_server::get_platform();
      if ($system !== 'DARWIN')
        throw new Exception('this adapter is not available on this platform');

      $this->binary = new SplFileObject('/usr/bin/sips');

      if (!$this->binary->isExecutable())
        throw new Exception('Sips is not executable');

      parent::__construct($registry);
    }
    catch (Exception $e)
    {
      throw $e;
    }

    return $this;
  }

  /**
   *
   * @param string $name
   * @param string $value
   * @return binaryAdapter_image_resize_sips
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

    if ($tech_datas[system_file::TC_DATAS_COLORSPACE] == 'CMYK')
      throw new Exception('this adapter does not work with CMYK');

    if (!is_null($size) && !$origine->is_raw_image()
            && $tech_datas[system_file::TC_DATAS_WIDTH] < $size && $tech_datas[system_file::TC_DATAS_HEIGHT] < $size)
    {
      $size = max($tech_datas[system_file::TC_DATAS_WIDTH], $tech_datas[system_file::TC_DATAS_HEIGHT]);
    }

    $cmd = 'sips'
            . ' -s format jpeg'
            . ' -s formatOptions ' . $this->options['quality']
            . ' -Z ' . $size;

    if ($this->options['dpi'])
    {
      $cmd .= ' -s dpiHeight ' . $this->options['dpi']
              . ' -s dpiWidth ' . $this->options['dpi'];
    }

    if ($this->options['autorotate'])
    {
      switch ($tech_datas[system_file::TC_DATAS_ORIENTATION])
      {
        case 3:
          $cmd .= ' -r 180';
          break;
        case 6:
          $cmd .= ' -r 90';
          break;
        case 8:
          $cmd .= ' -r 270';
          break;
      }
    }

    $cmd .= sprintf(' %s --out %s'
                    , $this->escapeshellargs($origine->getPathname())
                    , $this->escapeshellargs($dest)
    );

    $this->shell_cmd($cmd);

    return $this;
  }

}
