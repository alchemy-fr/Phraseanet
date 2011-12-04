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
class binaryAdapter_document_toFlexpaperSwf_pdf2swf extends binaryAdapter_processorAbstract
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
  protected $binary_name = 'GV_pdf2swf';

  /**
   *
   * @param system_file $origine
   * @param string $dest
   * @return binaryAdapter_document_toFlexpaperSwf_pdf2swf
   */
  protected function process(system_file $origine, $dest)
  {
    $system = system_server::get_platform();

    if ($system == 'WINDOWS')
    {
      $cmd = sprintf('%s %s %s -s poly2bitmap -T 9 -f'
                      , $this->binary
                      , $this->escapeshellargs($origine->getPathname())
                      , $this->escapeshellargs($dest)
      );
    }
    else
    {
      $cmd = sprintf('%s %s %s -s poly2bitmap -Q 300 -T 9 -f'
                      , $this->binary
                      , $this->escapeshellargs($origine->getPathname())
                      , $this->escapeshellargs($dest)
      );
    }

    $this->shell_cmd($cmd);

    return $this;
  }

}
