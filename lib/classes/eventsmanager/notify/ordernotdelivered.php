<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\User;

class eventsmanager_notify_ordernotdelivered extends eventsmanager_notifyAbstract
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->group = $this->app->trans('Commande');

        return $this;
    }

    public function icon_url()
    {
        return '/assets/common/images/icons/disktt_history.png';
    }

    public function datas(array $data, $unread)
    {
        $from = $data['from'];
        $n = $data['n'];

        if (null === $user = $this->app['repo.users']->find($from)) {
            return [];
        }

        $sender = $user->getDisplayName();

        $ret = [
            'text'  => $this->app->trans('%user% a refuse la livraison de %quantity% document(s) pour votre commande', ['%user%' => htmlentities($sender), '%quantity%' => $n])
            , 'class' => ''
        ];

        return $ret;
    }

    public function get_name()
    {
        return $this->app->trans('Refus d\'elements de commande');
    }

    public function get_description()
    {
        return $this->app->trans('Refus d\'elements de commande');
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
