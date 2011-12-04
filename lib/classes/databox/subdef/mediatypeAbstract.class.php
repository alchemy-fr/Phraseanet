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
abstract class databox_subdef_mediatypeAbstract
{

  /**
   *
   * @param databox_subdefAbstract $subdef
   * @return Array
   */
  public function get_options(databox_subdefAbstract $subdef)
  {
    $options = array();
    $full_options = $this->get_available_options($subdef);
    foreach ($full_options as $opt_name => $values)
    {
      $options[$opt_name] = $values['value'];
    }

    return $options;
  }

}
