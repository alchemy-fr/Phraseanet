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

class eventsmanager_notify_register extends eventsmanager_notifyAbstract
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

        $sender = $user->getDisplayName();

        $ret = [
            'text'  => $this->app->trans('%user% demande votre approbation sur une ou plusieurs %before_link% collections %after_link%', ['%user%' => $sender, '%before_link%' => '<a href="' . $this->app->url('admin', ['section' => 'registrations']) . '" target="_blank">', '%after_link%' => '</a>'])
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
        return $this->app->trans('Register approbation');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return $this->app->trans('Recevoir des notifications lorsqu\'un utilisateur demande une inscription necessitant mon approbation');
    }

    /**
     * @param integer $usr_id The id of the user to check
     *
     * @return boolean
     */
    public function is_available(User $user)
    {
        if (!$this->app['registration.manager']->isRegistrationEnabled()) {
            return false;
        }

        return $this->app->getAclForUser($user)->has_right(\ACL::CANADMIN);
    }
}
