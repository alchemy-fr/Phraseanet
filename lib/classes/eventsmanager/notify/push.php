<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailInfoPushReceived;

class eventsmanager_notify_push extends eventsmanager_notifyAbstract
{
    /**
     *
     * @var string
     */
    public $events = ['__PUSH_DATAS__'];

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
        $default = [
            'from'    => ''
            , 'to'      => ''
            , 'message' => ''
            , 'ssel_id' => ''
        ];

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
                $repository = $this->app['EM']->getRepository('Phraseanet:Basket');
                $basket = $repository->find($params['ssel_id']);

                $user_from = $this->app['manipulator.user']->getRepository()->find($params['from']);
                $user_to = $this->app['manipulator.user']->getRepository()->find($params['to']);

                $receiver = Receiver::fromUser($user_to, $this->app['translator']);
                $emitter = Emitter::fromUser($user_from, $this->app['translator']);
                $readyToSend = true;
            } catch (\Exception $e) {

            }

            if ($readyToSend) {
                $mail = MailInfoPushReceived::create($this->app, $receiver, $emitter, $params['message'], $params['url']);
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

        if (null === $user = $this->app['manipulator.user']->getRepository()->find($from)) {
            return [];
        }

        $sender = $user->getDisplayName($this->app['translator']);

        $ret = [
            'text'  => $this->app->trans('%user% vous a envoye un %before_link% panier %after_link%', ['%user%' => $sender, '%before_link%' => '<a href="#" onclick="openPreview(\'BASK\',1,\''
                . (string) $sx->ssel_id . '\');return false;">', '%after_link%' => '</a>'])
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
        return $this->app->trans('Push');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return $this->app->trans('Receive notification when I receive a push');
    }

    /**
     * @param integer $usr_id The id of the user to check
     *
     * @return boolean
     */
    public function is_available($usr_id)
    {
        return true;
    }

}
