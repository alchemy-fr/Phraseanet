<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class API_OAuth2_Exception_Redirect_InvalidRequest extends API_OAuth2_Exception_Redirect
{
    /**
     *
     * @var string
     */
    protected $error = 'invalid_request';

    /**
     *
     * @var string
     */
    protected $error_description = "The request is missing a required parameter, includes an unsupported parameter or parameter value, repeats the same parameter, uses more than one method for including an access token, or is otherwise malformed.";

    /**
     *
     * @param  string                                       $redirect_uri
     * @param  string                                       $state
     * @param  string                                       $error_uri
     * @return API_OAuth2_Exception_Redirect_InvalidRequest
     */
    public function __construct($redirect_uri, $state = null, $error_uri = null)
    {
        parent::__construct($redirect_uri, $this->error, $this->error_description, $state, $error_uri);

        return $this;
    }
}
