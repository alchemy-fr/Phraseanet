<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
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
interface Session_Storage_Interface
{

    /**
     * Close the session storage
     *
     * @return Void
     */
    public function close();

    /**
     * Return true if the storage contains the key
     *
     * @param  string  $key
     * @return boolean
     */
    public function has($key);

    /**
     * Set a key in the storage
     *
     * @param string $key
     * @param mixed  $default_value
     */
    public function get($key, $default_value = null);

    public function set($key, $value);

    public function remove($key);

    public function getName();

    public function getId();

    public function reset();

    public function destroy();
}
