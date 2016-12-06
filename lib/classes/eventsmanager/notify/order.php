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

class eventsmanager_notify_order extends eventsmanager_notifyAbstract
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
     * @return string
     */
    public function datas(array $data, $unread)
    {
        $usr_id = $data['usr_id'];
        $order_id = $data['order_id'];

        if (null === $user = $this->app['repo.users']->find($usr_id)) {
            return [];
        }

        $sender = $user->getDisplayName();

        $ret = [
            'text'  => $this->app->trans('%user% a passe une %opening_link% commande %end_link%', [
                '%user%' => $sender,
                '%opening_link%' => '<a href="/prod/order/'.$order_id.'/" class="dialog full-dialog" title="'.$this->app->trans('Orders manager').'">',
                '%end_link%' => '</a>',])
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
        return $this->app->trans('Nouvelle commande');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return $this->app->trans('Recevoir des notifications lorsqu\'un utilisateur commande des documents');
    }

    /**
     * @param integer $usr_id The id of the user to check
     *
     * @return boolean
     */
    public function is_available(User $user)
    {
        return $this->app->getAclForUser($user)->has_right(\ACL::ORDER_MASTER);
    }
}
