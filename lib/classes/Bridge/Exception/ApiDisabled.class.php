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
class Bridge_Exception_ApiDisabled extends Bridge_Exception
{
    protected $api;

    /**
     *
     * @param Bridge_Api $api
     */
    public function __construct(Bridge_Api $api)
    {
        $this->api = $api;

        parent::__construct();
    }

    /**
     *
     * @return Bridge_Api
     */
    public function get_api()
    {
        return $this->api;
    }
}
