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


class eventsmanager_notify_validationdone extends eventsmanager_notifyAbstract
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->group = $this->app->trans('Validation');
    }

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
     * @param  string[]  $data
     * @param  boolean $unread
     * @return array
     */
    public function datas(array $data, $unread)
    {
        $from = $data['from'];
        $ssel_id = $data['ssel_id'];

        /** @var UserRepository $userRepo */
        $userRepo = $this->app['repo.users'];
        if ( ($registered_user = $userRepo->find($from)) === null ) {
            return [];
        }

        $sender = $registered_user->getDisplayName();

        try {
            /** @var BasketRepository $repository */
            $repository = $this->app['repo.baskets'];

            $basket = $repository->findUserBasket($ssel_id, $this->app->getAuthenticatedUser(), false);
        }
        catch (\Exception $e) {
            return [];
        }

        $ret = [
            'text'  => $this->app->trans('%user% a envoye son rapport de validation de %title%', ['%user%' => htmlentities($sender), '%title%' => '<a href="/lightbox/validate/'
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
        return $this->app->trans('Rapport de Validation');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return $this->app->trans('Reception d\'un rapport de validation');
    }

    /**
     * @param User $user     The id of the user to check
     *
     * @return boolean
     */
    public function is_available(User $user)
    {
        try {
            return $this->app->getAclForUser($user)->has_right(\ACL::CANPUSH);
        }
        catch (\Exception $e) {
            // has_right(unknow_right) ? will not happen !
            return false;
        }
    }
}
