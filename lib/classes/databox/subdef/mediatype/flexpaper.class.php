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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class databox_subdef_mediatype_flexpaper extends databox_subdef_mediatypeAbstract implements databox_subdef_mediatypeInterface
{

  /**
   *
   * @return databox_subdef_mediatype_flexpaper
   */
  public function __construct()
  {
    return $this;
  }

  /**
   *
   * @param databox_subdefAbstract $subdef
   * @return array
   */
  public function get_available_options(databox_subdefAbstract $subdef)
  {
    $options = array();

    return $options;
  }

}
