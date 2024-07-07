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

class eventsmanager_notify_bridgeuploadfail extends eventsmanager_notifyAbstract
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
        $reason = $data['reason'];
        $account_id = $data['account_id'];
        $sbas_id = $data['sbas_id'];
        $rid = $data['record_id'];

        try {
            $account = Bridge_Account::load_account($this->app, $account_id);
            $record = new record_adapter($this->app, $sbas_id, $rid);
        } catch (\Exception $e) {
            return [];
        }

        $ret = [
            'text'  => $this->app->trans("L'upload concernant le record %title% sur le compte %bridge_name% a echoue pour les raisons suivantes : %reason%", [
                '%title%' => $record->get_title(['encode'=> record_adapter::ENCODE_FOR_HTML]),
                '%bridge_name%' => $account->get_api()->get_connector()->get_name(),
                '%reason%' => $reason
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
        return $this->app->trans('Bridge upload fail');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return $this->app->trans('Recevoir des notifications lorsqu\'un upload echoue sur un bridge');
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
