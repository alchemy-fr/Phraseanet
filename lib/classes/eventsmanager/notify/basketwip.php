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
        $ret = [
            'text'  => $data['message'],
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
