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
use Alchemy\Phrasea\Notification\Mail\MailInfoNewOrder;

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class eventsmanager_notify_order extends eventsmanager_notifyAbstract
{
    /**
     *
     * @var string
     */
    public $events = array('__NEW_ORDER__');

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
            'usr_id'   => ''
            , 'order_id' => array()
        );

        $params = array_merge($default, $params);
        $order_id = $params['order_id'];

        $users = array();

        try {
            $sql = 'SELECT DISTINCT e.base_id
          FROM order_elements e
          WHERE e.order_id = :order_id';
            $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
            $stmt->execute(array(':order_id' => $order_id));
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $base_ids = array();
            foreach ($rs as $row) {
                $base_ids[] = $row['base_id'];
            }

            $query = new User_Query($this->app);
            $users = $query->on_base_ids($base_ids)
                    ->who_have_right(array('order_master'))
                    ->execute()->get_results();
        } catch (Exception $e) {

        }

        if (count($users) == 0) {
            return;
        }

        $dom_xml = new DOMDocument('1.0', 'UTF-8');

        $dom_xml->preserveWhiteSpace = false;
        $dom_xml->formatOutput = true;

        $root = $dom_xml->createElement('datas');

        $usr_id_dom = $dom_xml->createElement('usr_id');
        $order_id_dom = $dom_xml->createElement('order_id');

        $usr_id_dom->appendChild($dom_xml->createTextNode($params['usr_id']));

        $order_id_dom->appendChild($dom_xml->createTextNode($order_id));

        $root->appendChild($usr_id_dom);
        $root->appendChild($order_id_dom);

        $dom_xml->appendChild($root);

        $datas = $dom_xml->saveXml();

        try {
            $orderInitiator = User_Adapter::getInstance($params['usr_id'], $this->app);
        } catch (\Exception $e) {
            return;
        }

        foreach ($users as $user) {
            $mailed = false;

            if ($this->shouldSendNotificationFor($user->get_id())) {
                $readyToSend = false;
                try {
                    $receiver = Receiver::fromUser($user);
                    $readyToSend = true;
                } catch (\Exception $e) {
                    continue;
                }

                if ($readyToSend) {
                    $mail = MailInfoNewOrder::create($this->app, $receiver);
                    $mail->setUser($orderInitiator);

                    $this->app['notification.deliverer']->deliver($mail);
                    $mailed = true;
                }
            }

            $this->broker->notify($user->get_id(), __CLASS__, $datas, $mailed);
        }

        return;
    }

    /**
     *
     * @param  Array   $datas
     * @param  boolean $unread
     * @return string
     */
    public function datas($datas, $unread)
    {
        $sx = simplexml_load_string($datas);

        $usr_id = (string) $sx->usr_id;
        $order_id = (string) $sx->order_id;

        try {
            $registered_user = User_Adapter::getInstance($usr_id, $this->app);
        } catch (Exception $e) {
            return array();
        }

        $sender = User_Adapter::getInstance($usr_id, $this->app)->get_display_name();

        $ret = array(
            'text'  => sprintf(_('%1$s a passe une %2$scommande%3$s')
                , $sender
                , '<a href="/prod/order/'.$order_id.'/" class="dialog full-dialog" title="'._('Orders manager').'">'
                , '</a>')
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
        return _('Nouvelle commande');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return _('Recevoir des notifications lorsqu\'un utilisateur commande des documents');
    }

    /**
     *
     * @return boolean
     */
    public function is_available()
    {
        $bool = false;
        if ( !$this->app->isAuthenticated()) {
            return false;
        }

        if ($this->app['phraseanet.user']->ACL()->has_right('order_master')) {
            $bool = true;
        }

        return $bool;
    }
}
