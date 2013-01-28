<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailInfoSomebodyAutoregistered;

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class eventsmanager_notify_autoregister extends eventsmanager_notifyAbstract
{
    /**
     *
     * @var string
     */
    public $events = array('__REGISTER_AUTOREGISTER__');

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
            'usr_id'       => ''
            , 'autoregister' => array()
        );

        $params = array_merge($default, $params);
        $base_ids = array_keys($params['autoregister']);

        if (count($base_ids) == 0) {
            return;
        }

        $mailColl = array();

        $sql = 'SELECT u.usr_id, b.base_id FROM usr u, basusr b
      WHERE u.usr_id = b.usr_id
      AND b.base_id
        IN (' . implode(', ', array_keys($base_ids)) . ')
      AND model_of="0"
      AND b.actif="1"
      AND b.canadmin="1"
          AND u.usr_login NOT LIKE "(#deleted%"';

        try {
            $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($rs as $row) {
                if ( ! isset($mailColl[$row['usr_id']]))
                    $mailColl[$row['usr_id']] = array();

                $mailColl[$row['usr_id']][] = $row['base_id'];
            }
        } catch (Exception $e) {

        }

        $dom_xml = new DOMDocument('1.0', 'UTF-8');

        $dom_xml->preserveWhiteSpace = false;
        $dom_xml->formatOutput = true;

        $root = $dom_xml->createElement('datas');

        $usr_id = $dom_xml->createElement('usr_id');
        $base_ids = $dom_xml->createElement('base_ids');

        $usr_id->appendChild($dom_xml->createTextNode($params['usr_id']));

        foreach ($params['autoregister'] as $base_id => $collection) {
            $base_id_node = $dom_xml->createElement('base_id');
            $base_id_node->appendChild($dom_xml->createTextNode($base_id));
            $base_ids->appendChild($base_id_node);
        }

        $root->appendChild($usr_id);
        $root->appendChild($base_ids);

        $dom_xml->appendChild($root);

        $datas = $dom_xml->saveXml();

        try {
            $registered_user = User_Adapter::getInstance($params['usr_id'], $this->app);
        } catch (Exception $e) {
            return;
        }

        foreach ($mailColl as $usr_id => $base_ids) {

            $mailed = false;

            if ($this->shouldSendNotificationFor($usr_id)) {
                try {
                    $admin_user = User_Adapter::getInstance($usr_id, $this->app);
                } catch (Exception $e) {
                    continue;
                }

                if (self::mail($admin_user, $registered_user))
                    $mailed = true;
            }

            $this->broker->notify($usr_id, __CLASS__, $datas, $mailed);
        }

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

        $usr_id = (string) $sx->usr_id;
        try {
            $registered_user = User_Adapter::getInstance($usr_id, $this->app);
        } catch (Exception $e) {
            return array();
        }

        $sender = User_Adapter::getInstance($usr_id, $this->app)->get_display_name();

        $ret = array(
            'text'  => sprintf(
                _('%1$s s\'est enregistre sur une ou plusieurs %2$scollections%3$s'), $sender, '<a href="/admin/?section=users" target="_blank">', '</a>')
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
        return _('AutoRegister information');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return _('Recevoir des notifications lorsqu\'un'
                . ' utilisateur s\'inscrit sur une collection');
    }

    /**
     *
     * @param  Array   $to
     * @param  Array   $from
     * @param  Array   $datas
     * @return boolean
     */
    public function mail(\User_Adapter $to, \User_Adapter $registeredUser)
    {
        $body .= sprintf("Login : %s\n", $registeredUser->get_login());
        $body .= sprintf("%s : %s\n", _('admin::compte-utilisateur nom'), $registeredUser->get_firstname());
        $body .= sprintf("%s : %s\n", _('admin::compte-utilisateur prenom'), $registeredUser->get_lastname());
        $body .= sprintf("%s : %s\n", _('admin::compte-utilisateur email'), $registeredUser->get_email());
        $body .= sprintf("%s/%s\n", $registeredUser->get_job(), $registeredUser->get_company());

        $readyToSend = false;
        try {
            $receiver = Receiver::fromUser($to);
            $readyToSend = true;
        } catch (Exception $e) {
            
        }

        if ($readyToSend) {
            $mail = MailInfoSomebodyAutoregistered::create($this->app, $receiver, $body);
            $this->app['notification.deliverer']->deliver($mail);
        }

        return true;
    }

    /**
     *
     * @return boolean
     */
    public function is_available()
    {
        $bool = false;
        $login = new \login();

        if ( ! $this->app->isAuthenticated() || ! $login->register_enabled($this->app)) {
            return false;
        }

        if ($this->app['phraseanet.user']->ACL()->has_right('manageusers') === true) {
            $bool = true;
        }

        return $bool;
    }
}
