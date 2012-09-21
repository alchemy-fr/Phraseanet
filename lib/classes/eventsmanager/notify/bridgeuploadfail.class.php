<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class eventsmanager_notify_bridgeuploadfail extends eventsmanager_notifyAbstract
{
    /**
     *
     * @var string
     */
    public $events = array('__BRIDGE_UPLOAD_FAIL__');

    /**
     *
     * @return string
     */
    public function icon_url()
    {
        return '/skins/icons/user.png';
    }

    /**
     *
     * @param  string        $event
     * @param  Array         $params
     * @param  mixed content $object
     * @return Void
     */
    public function fire($event, $params, &$object)
    {
        $default = array(
            'usr_id'     => null
            , 'reason'     => ''
            , 'account_id' => null
            , 'base_id'    => null
            , 'record_id'  => null
        );

        $params = array_merge($default, $params);

        $dom_xml = new DOMDocument('1.0', 'UTF-8');
        $dom_xml->preserveWhiteSpace = false;
        $dom_xml->formatOutput = true;

        $root = $dom_xml->createElement('datas');

        $reason = $dom_xml->createElement('reason');
        $account_id = $dom_xml->createElement('account_id');
        $sbas_id = $dom_xml->createElement('sbas_id');
        $record_id = $dom_xml->createElement('record_id');

        $reason->appendChild($dom_xml->createTextNode($params['reason']));
        $account_id->appendChild($dom_xml->createTextNode($params['account_id']));
        $sbas_id->appendChild($dom_xml->createTextNode($params['sbas_id']));
        $record_id->appendChild($dom_xml->createTextNode($params['record_id']));

        $root->appendChild($reason);
        $root->appendChild($account_id);
        $root->appendChild($sbas_id);
        $root->appendChild($record_id);

        $dom_xml->appendChild($root);

        $datas = $dom_xml->saveXml();

        $mailed = false;

        $send_notif = ($this->get_prefs(__CLASS__, $params['usr_id']) != '0');

        if ($send_notif) {
            $user = User_Adapter::getInstance($params['usr_id'], $this->app);
            $name = $user->get_display_name();

            $to = array('email' => $user->get_email(), 'name'  => $name);

            $from = array(
                'email' => $this->app['phraseanet.appbox']->get_registry()->get('GV_defaulmailsenderaddr'),
                'name'  => $this->app['phraseanet.appbox']->get_registry()->get('GV_homeTitle')
            );

            if (self::mail($to, $from, $datas))
                $mailed = true;
        }

        $this->broker->notify($params['usr_id'], __CLASS__, $datas, $mailed);

        return;
    }

    /**
     *
     * @param  Array   $datas
     * @param  boolean $unread
     * @return Array
     */
    public function datas($datas, $unread)
    {
        $sx = simplexml_load_string($datas);

        $reason = (string) $sx->reason;
        $account_id = (int) $sx->account_id;
        $sbas_id = (int) $sx->sbas_id;
        $rid = (int) $sx->record_id;

        try {
            $account = Bridge_Account::load_account($this->app, $account_id);
            $record = new record_adapter($this->app, $sbas_id, $rid);
        } catch (Exception $e) {
            return array();
        }

        $ret = array(
            'text'  => sprintf("L'upload concernant le record %s sur le comptre %s a echoue pour les raisons suivantes : %s"
                , $record->get_title(), $account->get_api()->get_connector()->get_name(), $reason)
            , 'class' => ''
        );

        return $ret;
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return _('Bridge upload fail');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return _('Recevoir des notifications lorsqu\'un'
                . ' upload echoue sur un bridge');
    }

    /**
     *
     * @param  Array   $to
     * @param  Array   $from
     * @param  Array   $datas
     * @return boolean
     */
    public function mail($to, $from, $datas)
    {
        $subject = sprintf('Echec upload sur %s'
            , $this->app['phraseanet.appbox']->get_registry()->get('GV_homeTitle'));

        $sx = simplexml_load_string($datas);

        $reason = (string) $sx->reason;
        $body = "reason : " . $reason;

        return mail::send_mail($this->app, $subject, $body, $to, $from);
    }

    /**
     *
     * @return boolean
     */
    public function is_available()
    {
        return true;
    }
}
