<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Mail\MailInfoOrderCancelled;

class eventsmanager_notify_ordernotdelivered extends eventsmanager_notifyAbstract
{
    /**
     *
     * @var string
     */
    public $events = ['__ORDER_NOT_DELIVERED__'];

    public function __construct(Application $app, eventsmanager_broker $broker)
    {
        parent::__construct($app, $broker);
        $this->group = $this->app->trans('Commande');

        return $this;
    }

    public function icon_url()
    {
        return '/skins/prod/000000/images/disktt_history.gif';
    }

    public function fire($event, $params, &$object)
    {
        $default = [
            'from' => ''
            , 'to'   => ''
            , 'n'    => ''
        ];

        $params = array_merge($default, $params);

        $dom_xml = new DOMDocument('1.0', 'UTF-8');

        $dom_xml->preserveWhiteSpace = false;
        $dom_xml->formatOutput = true;

        $root = $dom_xml->createElement('datas');

        $from = $dom_xml->createElement('from');
        $to = $dom_xml->createElement('to');
        $n = $dom_xml->createElement('n');

        $from->appendChild($dom_xml->createTextNode($params['from']));
        $to->appendChild($dom_xml->createTextNode($params['to']));
        $n->appendChild($dom_xml->createTextNode($params['n']));

        $root->appendChild($from);
        $root->appendChild($to);
        $root->appendChild($n);

        $dom_xml->appendChild($root);

        $datas = $dom_xml->saveXml();

        $mailed = false;

        if ($this->shouldSendNotificationFor($params['to'])) {

            $readyToSend = false;

            try {
                $user_from = User_Adapter::getInstance($params['from'], $this->app);
                $user_to = User_Adapter::getInstance($params['to'], $this->app);

                $receiver = Receiver::fromUser($user_to);
                $emitter = Emitter::fromUser($user_from);

                $readyToSend = true;
            } catch (Exception $e) {

            }

            if ($readyToSend) {
                $mail = MailInfoOrderCancelled::create($this->app, $receiver, $emitter);
                $mail->setQuantity($params['n']);
                $mail->setDeliverer($user_from);

                $this->app['notification.deliverer']->deliver($mail);

                $mailed = true;
            }
        }

        return $this->broker->notify($params['to'], __CLASS__, $datas, $mailed);
    }

    public function datas($datas, $unread)
    {
        $sx = simplexml_load_string($datas);

        $from = (string) $sx->from;
        $n = (int) $sx->n;

        try {
            User_Adapter::getInstance($from, $this->app);
        } catch (Exception $e) {
            return [];
        }

        $sender = User_Adapter::getInstance($from, $this->app)->get_display_name();

        $ret = [
            'text'  => $this->app->trans('%user% a refuse la livraison de %quantity% document(s) pour votre commande', array('%user%' => $sender, '%quantity%' => $n))
            , 'class' => ''
        ];

        return $ret;
    }

    public function get_name()
    {
        return $this->app->trans('Refus d\'elements de commande');
    }

    public function get_description()
    {
        return $this->app->trans('Refus d\'elements de commande');
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
