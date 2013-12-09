<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class system_server
{
    /**
     *
     * @var string
     */
    private $_server_software;

    /**
     *
     * @return system_server
     */
    public function __construct()
    {
        $this->_server_software = isset($_SERVER['SERVER_SOFTWARE']) ?
            strtolower($_SERVER['SERVER_SOFTWARE']) : "";

        return $this;
    }

    /**
     * Return true if server is Nginx
     *
     * @return boolean
     */
    public function is_nginx()
    {
        if (strpos($this->_server_software, 'nginx') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Return true if server is lighttpd
     *
     * @return boolean
     */
    public function is_lighttpd()
    {
        if (strpos($this->_server_software, 'lighttpd') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Return true if server is Apache
     *
     * @return boolean
     */
    public function is_apache()
    {
        if (strpos($this->_server_software, 'apache') !== false) {
            return true;
        }

        return false;
    }
}
