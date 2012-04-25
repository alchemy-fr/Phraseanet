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
interface Session_Authentication_Interface
{

    /**
     * Pre execution after authentication
     *
     * @return Session_Authentication_Interface
     */
    public function prelog();

    /**
     * Verify the authentication
     *
     * @return User_Adapter
     */
    public function signOn();

    /**
     * Give the user related to the object
     *
     * @return User_Adapter
     */
    public function get_user();

    /**
     * Post execution after authentication
     *
     * @return Session_Authentication_Interface
     */
    public function postlog();
}
