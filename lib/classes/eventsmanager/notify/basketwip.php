<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Model\Entities\User;

class eventsmanager_notify_basketwip extends eventsmanager_notifyAbstract
{
    /**
     *
     * @return string
     */
    public function icon_url()
    {
        return null;
    }

    /**
     *
     * @param  Array   $data
     * @param  boolean $unread
     * @return Array
     */
    public function datas(array $data, $unread)
    {
        if (isset($data['message'])) {
            // to be retro compatible with old data value
            $text = $data['message'];
        } else {
            if ($data['translateMessage'] == 'text1') {
                $text = $this->app->trans('notification:: Sharing basket "%name%"...', ['%name%' => $data['name']]);
            } else {
                $text = $this->app->trans('notification:: Basket %name% is shared', ['%name%' => $data['name']]);
            }
        }

        $ret = [
            'text'  => $text,
            'class' => ($unread == 1 ? 'reload_baskets' : '')
        ];

        return $ret;
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return $this->app->trans('notification:: Basket WIP');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return $this->app->trans('notification:: Receive notification when a basket is WIP');
    }

    /**
     * @param integer $usr_id The id of the user to check
     *
     * @return boolean
     */
    public function is_available(User $user)
    {
        return true;
    }

}
