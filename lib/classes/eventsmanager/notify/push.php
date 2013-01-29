<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailInfoPushReceived;

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class eventsmanager_notify_push extends eventsmanager_notifyAbstract
{
    /**
     *
     * @var string
     */
    public $events = array('__PUSH_DATAS__');

    /**
     *
     * @return string
     */
    public function icon_url()
    {
        return '/skins/icons/push16.png';
    }

    /**
     *
     * @param  string        $event
     * @param  Array         $params
     * @param  mixed content $object
     * @return boolean
     */
    public function fire($event, $params, &$object)
    {
        $default = array(
            'from'    => ''
            , 'to'      => ''
            , 'message' => ''
            , 'ssel_id' => ''
        );

        $params = array_merge($default, $params);

        $dom_xml = new DOMDocument('1.0', 'UTF-8');

        $dom_xml->preserveWhiteSpace = false;
        $dom_xml->formatOutput = true;

        $root = $dom_xml->createElement('datas');

        $from = $dom_xml->createElement('from');
        $to = $dom_xml->createElement('to');
        $message = $dom_xml->createElement('message');
        $ssel_id = $dom_xml->createElement('ssel_id');

        $from->appendChild($dom_xml->createTextNode($params['from']));
        $to->appendChild($dom_xml->createTextNode($params['to']));
        $message->appendChild($dom_xml->createTextNode($params['message']));
        $ssel_id->appendChild($dom_xml->createTextNode($params['ssel_id']));

        $root->appendChild($from);
        $root->appendChild($to);
        $root->appendChild($message);
        $root->appendChild($ssel_id);

        $dom_xml->appendChild($root);

        $datas = $dom_xml->saveXml();

        $mailed = false;

        if ($this->shouldSendNotificationFor($params['to'])) {

            $readyToSend = false;
            try {
                $repository = $this->app['EM']->getRepository('\Entities\Basket');
                $basket = $repository->find($params['ssel_id']);

                $user_from = User_Adapter::getInstance($params['from'], $this->app);
                $user_to = User_Adapter::getInstance($params['to'], $this->app);

                $receiver = Receiver::fromUser($user_from);
                $emitter = Emitter::fromUser($user_to);
                $readyToSend = true;
            } catch (\Exception $e) {

            }

            if ($readyToSend) {
                $mail = MailInfoPushReceived::create($this->app, $receiver, $emitter, $params['message']);
                $mail->setBasket($basket);
                $mail->setPusher($user_from);

                $this->app['notification.deliverer']->deliver($mail, $params['accuse']);

                $mailed = true;
            }
        }

        return $this->broker->notify($params['to'], __CLASS__, $datas, $mailed);
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

        $from = (string) $sx->from;

        try {
            $registered_user = User_Adapter::getInstance($from, $this->app);
        } catch (Exception $e) {
            return array();
        }

        $sender = User_Adapter::getInstance($from, $this->app)->get_display_name();

        $ret = array(
            'text'  => sprintf(
                _('%1$s vous a envoye un %2$spanier%3$s'), $sender, '<a href="#" onclick="openPreview(\'BASK\',1,\''
                . (string) $sx->ssel_id . '\');return false;">', '</a>')
            , 'class' => ($unread == 1 ? 'reload_baskets' : '')
        );

        return $ret;
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return _('Push');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return _('Recevoir des notifications lorsqu\'on me push quelque chose');
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
