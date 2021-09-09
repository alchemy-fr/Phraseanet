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
use Alchemy\Phrasea\Model\Repositories\BasketRepository;
use Alchemy\Phrasea\Model\Repositories\UserRepository;


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
        return '/assets/common/images/icons/disktt_history.png';
    }

    /**
     *
     * @param  string[]   $data
     * @param  boolean $unread
     * @return array
     */
    public function datas(array $data, $unread)
    {
        $from = $data['from'];
        $ssel_id = $data['ssel_id'];
        $n = $data['n'];

        /** @var UserRepository $userRepo */
        $userRepo = $this->app['repo.users'];
        if( ($user= $userRepo->find(($from))) === null ) {
            return [];
        }

        $sender = $user->getDisplayName();

        try {
            /** @var BasketRepository $repository */
            $repository = $this->app['repo.baskets'];

            $basket = $repository->findUserBasket($ssel_id, $this->app->getAuthenticatedUser(), false);
        }
        catch (\Exception $e) {
            return [];
        }

        $ret = [
            'text'  => $this->app->trans('%user% vous a delivre %quantity% document(s) pour votre commande %title%', ['%user%' => htmlentities($sender), '%quantity%' => $n, '%title%' => '<a href="/lightbox/compare/'
                . $ssel_id . '/" target="_blank">'
                . htmlentities($basket->getName()) . '</a>']),
            'class' => ''
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
