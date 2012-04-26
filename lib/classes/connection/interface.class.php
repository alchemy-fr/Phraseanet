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
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface connection_interface
{

    public function ping();

    public function get_name();

    public function is_multi_db();

    public function get_credentials();

    public function close();

    public function prepare($statement, $driver_options = array());

    public function beginTransaction();

    public function commit();

    public function server_info();
}
