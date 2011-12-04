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
 * @package     Session
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class Session_Storage_Abstract
{

  /**
   *
   * @var boolean
   */
  protected $open = true;

  /**
   *
   * @return Session_Storage_Abstract
   */
  public function close()
  {
    $this->open = false;

    return $this;
  }

  /**
   *
   * @return Session_Storage_Abstract
   */
  protected function require_open_storage()
  {
    if (!$this->open)
      throw new Exception_Session_StorageClosed ();

    return $this;
  }

}
