<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\User;

class eventsmanager_notify_orderdeliver extends eventsmanager_notifyAbstract
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->group = $this->app->trans('Commande');
    }

    /**
     *
     * @return string
     */
    public function icon_url()
    {
        return '/skins/prod/000000/images/disktt_history.gif';
    }

    /**
     *
     * @param  Array   $datas
     * @param  boolean $unread
     * @return string
     */
    public function datas(array $data, $unread)
    {
        $from = $data['from'];
        $ssel_id = $data['ssel_id'];
        $n = $data['n'];

        if (null === $user= $this->app['repo.users']->find(($from))) {
            return [];
        }

        $sender = $user->getDisplayName();

        try {
            $repository = $this->app['repo.baskets'];

            $basket = $repository->findUserBasket($ssel_id, $this->app['authentication']->getUser(), false);
        } catch (\Exception $e) {
            return [];
        }
        $ret = [
            'text'  => $this->app->trans('%user% vous a delivre %quantity% document(s) pour votre commande %title%', ['%user%' => $sender, '%quantity%' => $n, '%title%' => '<a href="/lightbox/compare/'
                . $ssel_id . '/" target="_blank">'
                . $basket->getName() . '</a>'])
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
        return $this->app->trans('Reception de commande');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return $this->app->trans('Reception d\'une commande');
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
