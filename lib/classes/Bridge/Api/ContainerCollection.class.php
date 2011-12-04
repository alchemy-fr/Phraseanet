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
 * @package     Bridge
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Bridge_Api_ContainerCollection extends Bridge_Api_AbstractCollection
{

  /**
   *
   * @param Bridge_Api_ContainerInterface $container
   * @return Bridge_Api_ContainerCollection
   */
  public function add_element(Bridge_Api_ContainerInterface $container)
  {
    $this->elements[] = $container;

    return $this;
  }

}
