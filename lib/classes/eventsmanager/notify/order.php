<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailInfoNewOrder;

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
            $repository = $this->app['EM']->getRepository('Phraseanet:OrderElement');

            $results = $repository->findBy(array('orderId' => $order_id));

            $base_ids = array();
            foreach ($results as $result) {
                $base_ids[] = $result->getBaseId();
            }
            $base_ids = array_unique($base_ids);

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

        if (null === $orderInitiator = $this->app['manipulator.user']->getRepository()->find($params['usr_id'])) {
            return;
        }

        foreach ($users as $user) {
            $mailed = false;

            if ($this->shouldSendNotificationFor($user->getId())) {
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

            $this->broker->notify($user->getId(), __CLASS__, $datas, $mailed);
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

        if (null === $user = $this->app['manipulator.user']->getRepository()->find($usr_id)) {
            return array();
        }

        $sender = $user->getDisplayName();

        $ret = [
            'text'  => $this->app->trans('%user% a passe une %opening_link% commande %end_link%', [
                '%user%' => $sender,
                '%opening_link%' => '<a href="/prod/order/'.$order_id.'/" class="dialog full-dialog" title="'.$this->app->trans('Orders manager').'">',
                '%end_link%' => '</a>',])
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
        return $this->app->trans('Nouvelle commande');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return $this->app->trans('Recevoir des notifications lorsqu\'un utilisateur commande des documents');
    }

    /**
     * @param integer $usr_id The id of the user to check
     *
     * @return boolean
     */
    public function is_available($usr_id)
    {
        if (null === $user = $this->app['manipulator.user']->getRepository()->find($usr_id)) {
            return false;
        }

        return $this->app['acl']->get($user)->has_right('order_master');
    }
}
