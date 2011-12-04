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
class binaryAdapter_document_toFlexpaperSwf extends binaryAdapter_adapterAbstract
{

  /**
   *
   * @var array
   */
  protected $processors = array(
      'binaryAdapter_document_toFlexpaperSwf_pdf2swf'
  );

  public function get_name()
  {
    return 'Binary adapter document to flexpaper swf';
  }

}
