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
     * @param  string  $datas
     * @param  boolean $unread
     * @return Array
     */
    public function datas(array $data, $unread)
    {
        $from = $data['from'];
        $ssel_id = $data['ssel_id'];

        if (null === $registered_user = $this->app['repo.users']->find($from)) {
            return [];
        }

        $sender = $registered_user->getDisplayName();

        try {
            $repository = $this->app['repo.baskets'];

            $basket = $repository->findUserBasket($ssel_id, $this->app->getAuthenticatedUser(), false);
        } catch (\Exception $e) {
            return [];
        }

        $ret = [
            'text'  => $this->app->trans('%user% a envoye son rapport de validation de %title%', ['%user%' => $sender, '%title%' => '<a href="/lightbox/validate/'
                . $ssel_id . '/" target="_blank">'
                . $basket->getName() . '</a>'
            ])
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
     * @param integer $usr_id The id of the user to check
     *
     * @return boolean
     */
    public function is_available(User $user)
    {
        return $this->app->getAclForUser($user)->has_right(\ACL::CANPUSH);
    }
}
