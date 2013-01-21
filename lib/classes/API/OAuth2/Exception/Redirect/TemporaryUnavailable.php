<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 *
 * @package     OAuth2 Connector
 *
 * @see         http://oauth.net/2/
 * @uses        http://code.google.com/p/oauth2-php/
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class API_OAuth2_Exception_Redirect_Unauthorized extends API_OAuth2_Exception_Redirect
{
    /**
     *
     * @var string
     */
    protected $error = 'temporarily_unavailable';

    /**
     *
     * @var string
     */
    protected $error_description = "The authorization server is currently unable to handle the request due to a temporary overloading or maintenance of the server.";

    /**
     *
     * @param  string                                     $redirect_uri
     * @param  string                                     $state
     * @param  string                                     $error_uri
     * @return API_OAuth2_Exception_Redirect_Unauthorized
     */
    public function __construct($redirect_uri, $state = null, $error_uri = null)
    {
        parent::__construct($redirect_uri, $this->error, $this->error_description, $state, $error_uri);

        return $this;
    }
}
