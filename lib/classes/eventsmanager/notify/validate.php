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
use Alchemy\Phrasea\Notification\Mail\MailInfoValidationRequest;
use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Receiver;

class eventsmanager_notify_validate extends eventsmanager_notifyAbstract
{
    /**
     *
     * @var string
     */
    public $events = array('__PUSH_VALIDATION__');

    /**
     *
     * @return notify_validate
     */
    public function __construct(Application $app, eventsmanager_broker $broker)
    {
        $this->group = _('Validation');
        parent::__construct($app, $broker);

        return $this;
    }

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
                $user_from = User_Adapter::getInstance($params['from'], $this->app);
                $user_to = User_Adapter::getInstance($params['to'], $this->app);

                $basket = $this->app['EM']
                    ->getRepository('\Entities\Basket')
                    ->find($params['ssel_id']);
                $title = $basket->getName();

                $receiver = Receiver::fromUser($user_to);
                $emitter = Emitter::fromUser($user_from);

                $readyToSend = true;
            } catch (\Exception $e) {

            }

            if ($readyToSend) {
                $mail = MailInfoValidationRequest::create($this->app, $receiver, $emitter, $params['message']);
                $mail->setButtonUrl($params['url']);
                $mail->setTitle($title);

                $this->app['notification.deliverer']->deliver($mail, $params['accuse']);
                $mailed = true;
            }
        }

        return $this->broker->notify($params['to'], __CLASS__, $datas, $mailed);
    }

    /**
     *
     * @param  string  $datas
     * @param  boolean $unread
     * @return Array
     */
    public function datas($datas, $unread)
    {
        $sx = simplexml_load_string($datas);

        $from = (string) $sx->from;
        $ssel_id = (string) $sx->ssel_id;

        try {
            $registered_user = User_Adapter::getInstance($from, $this->app);
        } catch (Exception $e) {
            return array();
        }

        $sender = User_Adapter::getInstance($from, $this->app)->get_display_name();

        try {
            $repository = $this->app['EM']->getRepository('\Entities\Basket');

            $basket = $repository->findUserBasket($this->app, $ssel_id, $this->app['phraseanet.user'], false);

            $basket_name = trim($basket->getName()) ? : _('Une selection');
        } catch (Exception $e) {
            $basket_name = _('Une selection');
        }

        $bask_link = '<a href="'
            . $this->app['phraseanet.registry']->get('GV_ServerName') . 'lightbox/validate/'
            . (string) $sx->ssel_id . '/" target="_blank">'
            . $basket_name . '</a>';

        $ret = array(
            'text'  => sprintf(
                _('%1$s vous demande de valider %2$s')
                , $sender, $bask_link
            )
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
        return _('Validation');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return _('Recevoir des notifications lorsqu\'on me demande une validation');
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
