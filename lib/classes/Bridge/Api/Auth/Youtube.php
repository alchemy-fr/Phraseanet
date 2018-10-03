<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Bridge_Api_Auth_Youtube extends Bridge_Api_Auth_OAuth2
{

    /**
     * Implements OAuth2.0 youtube specifications
     * @param  array  $supp_parameters
     * @return string
     */
    public function get_auth_url(array $supp_parameters = [])
    {
        $supp_parameters = array_merge(
            $supp_parameters, [
            'access_type'     => 'offline',
            'approval_prompt' => 'force']
        );

        return parent::get_auth_url($supp_parameters);
    }
}
