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

class eventsmanager_notify_downloadmailfail extends eventsmanager_notifyAbstract
{
    const MAIL_NO_VALID = 1;
    const MAIL_FAIL = 2;

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
        $reason = $data['reason'];
        $dest = $data['dest'];

        if ($reason == self::MAIL_NO_VALID) {
            $reason = $this->app->trans('email is not valid');
        } elseif ($reason == self::MAIL_FAIL) {
            $reason = $this->app->trans('failed to send mail');
        } else {
            $reason = $this->app->trans('an error occured while exporting records');
        }

        $text = $this->app->trans("The delivery to %email% failed for the following reason : %reason%", ['%email%' => $dest, '%reason%' => $reason]);

        $ret = [
            'text'  => $text
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
        return $this->app->trans('Email export fails');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return $this->app->trans('Get a notification when a mail export fails');
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
