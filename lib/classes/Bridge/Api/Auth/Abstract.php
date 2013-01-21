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
 * @package     Bridge
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
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
