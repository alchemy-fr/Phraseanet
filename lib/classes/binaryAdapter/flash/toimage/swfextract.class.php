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
class binaryAdapter_flash_toimage_swfextract extends binaryAdapter_processorAbstract
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
  protected $binary_name = 'GV_swf_extract';

  /**
   *
   * @param system_file $origine
   * @param string $dest
   * @return binaryAdapter_flash_toimage_swfextract
   */
  protected function process(system_file $origine, $dest)
  {
    $cmd = sprintf('%s %s'
                    , $this->binary
                    , $this->escapeshellargs($origine->getPathname())
    );

    $stdout = $this->shell_cmd($cmd);

    $id = false;

    foreach ($stdout as $l)
    {
      if (substr(trim($l), 0, 4) == '[-j]')
      {
        $id = ' -j '
                . array_pop(explode('-', array_pop(explode(' ', trim($l)))));
        $ext = '.jpg';
        break;
      }
      if (substr(trim($l), 0, 4) == '[-p]')
      {
        $id = ' -p '
                . array_pop(explode('-', array_pop(explode(' ', trim($l)))));
        $ext = '.png';
        break;
      }
    }

    if ($id)
    {
      $cmd = sprintf('%s %s %s -o %s'
                      , $this->binary
                      , $id
                      , $this->escapeshellargs($origine->getPathname())
                      , $this->escapeshellargs($dest)
      );

      $this->shell_cmd($cmd);
    }

    return $this;
  }

}
