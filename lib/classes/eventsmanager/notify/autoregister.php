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

class eventsmanager_notify_autoregister extends eventsmanager_notifyAbstract
{
    /**
     *
     * @return string
     */
    public function icon_url()
    {
        return '/assets/common/images/icons/user.png';
    }

    /**
     *
     * @param  Array   $datas
     * @param  boolean $unread
     * @return Array
     */
    public function datas(array $data, $unread)
    {
        $usr_id = $data['usr_id'];

        if (null === $user = $this->app['repo.users']->find($usr_id)) {
            return [];
        }

        $ret = [
            'text'  => $this->app->trans('%user% s\'est enregistre sur une ou plusieurs %before_link% scollections %after_link%', ['%user%' => $user->getDisplayName(), '%before_link%' => '<a href="/admin/?section=users" target="_blank">', '%after_link%' => '</a>'])
            , 'class' => ''
        ];

        return $ret;
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return $this->app->trans('AutoRegister information');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return $this->app->trans('Recevoir des notifications lorsqu\'un utilisateur s\'inscrit sur une collection');
    }

    /**
     * @param integer $usr_id The id of the user to check
     *
     * @return boolean
     */
    public function is_available(User $user)
    {
        if (! $this->app['registration.manager']->isRegistrationEnabled()) {
            return false;
        }

        return $this->app->getAclForUser($user)->has_right(\ACL::CANADMIN);
    }
}
