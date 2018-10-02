<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Bridge_Api_Auth_Abstract
{
    /**
     *
     * @var Bridge_AccountSettings
     */
    protected $settings;

    /**
     *
     * @param  Bridge_AccountSettings   $settings
     * @return Bridge_Api_Auth_Abstract
     */
    public function set_settings(Bridge_AccountSettings $settings)
    {
        $this->settings = $settings;

        return $this;
    }
}
