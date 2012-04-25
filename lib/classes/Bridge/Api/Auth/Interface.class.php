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
interface Bridge_Api_Auth_Interface
{
    const STATE_NEED_RECO = 'need_reconnect';
    const STATE_BAD = 'not_connected';
    const STATE_OK = 'connection OK';

    public function connect($param);

    public function reconnect();

    public function disconnect();

    public function is_connected();

    public function parse_request_token();

    public function get_auth_url(Array $supp_parameters = array());

    public function get_auth_signatures();

    public function set_settings(Bridge_AccountSettings $settings);

    public function set_parameters(Array $parameters);
}
