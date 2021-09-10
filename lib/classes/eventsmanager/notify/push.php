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

class eventsmanager_notify_push extends eventsmanager_notifyAbstract
{
    /**
     *
     * @return string
     */
    public function icon_url()
    {
        return '/assets/common/images/icons/push16.png';
    }

    /**
     *
     * @param  Array   $datas
     * @param  boolean $unread
     * @return Array
     */
    public function datas(array $data, $unread)
    {
        $from = $data['from'];

        if (null === $user = $this->app['repo.users']->find($from)) {
            return [];
        }

        $sender = $user->getDisplayName();

        $ret = [
            'text'  => $this->app->trans('%user% vous a envoye un %before_link% panier %after_link%', ['%user%' => htmlentities($sender), '%before_link%' => '<a href="#"
              data-kind="BASK"
              data-position="1"
              data-id="'. $data['ssel_id'] . '"
              class="to-open-preview-action">', '%after_link%' => '</a>'])
            , 'class' => ($unread == 1 ? 'reload_baskets' : '')
        ];

        return $ret;
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return $this->app->trans('Push');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return $this->app->trans('Receive notification when I receive a push');
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
