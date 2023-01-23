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

class eventsmanager_notify_validationreminder extends eventsmanager_notifyAbstract
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

        // for the old notifications
        $timeLeft =  '2 days';
        if (isset($data['time_left'])) {
            $timeLeft = $data['time_left'];
        }

        if (null === $user = $this->app['repo.users']->find($from)) {
            return [];
        }

        $sender = $user->getDisplayName();

        try {
            $basket = $this->app['converter.basket']->convert($ssel_id);
            $basket_name = trim($basket->getName()) ? : $this->app->trans('Une selection');
        } catch (\Exception $e) {
            $basket_name = $this->app->trans('Une selection');
        }

        $bask_link = '<a href="#" data-kind="BASK" data-position="1" data-id="'. $ssel_id . '" class="to-open-preview-action">'
            . $basket_name . '</a>';

        $ret = [
            'text'  => $this->app->trans('Rappel : Il vous reste %timeLeft% pour valider %title% de %user%', ['%timeLeft%' => $timeLeft, '%title%' => $bask_link, '%user%' => htmlentities($sender)])
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
        return $this->app->trans('Validation');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return $this->app->trans('Rappel pour une demande de validation');
    }

    /**
     * @param integer $usr_id The id of the user to check
     *
     * @return string
     */
    public function is_available(User $user)
    {
        return true;
    }
}
