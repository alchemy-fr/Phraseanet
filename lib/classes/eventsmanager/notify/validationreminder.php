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
use Alchemy\Phrasea\Notification\Mail\MailInfoValidationReminder;
use Alchemy\Phrasea\Notification\Receiver;

class eventsmanager_notify_validationreminder extends eventsmanager_notifyAbstract
{
    /**
     *
     * @var string
     */
    public $events = array('__VALIDATION_REMINDER__');

    /**
     *
     * @return notify_validationreminder
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
            , 'ssel_id' => ''
            , 'url'     => ''
        );

        $params = array_merge($default, $params);

        $dom_xml = new DOMDocument('1.0', 'UTF-8');

        $dom_xml->preserveWhiteSpace = false;
        $dom_xml->formatOutput = true;

        $root = $dom_xml->createElement('datas');

        $from = $dom_xml->createElement('from');
        $to = $dom_xml->createElement('to');
        $ssel_id = $dom_xml->createElement('ssel_id');

        $from->appendChild($dom_xml->createTextNode($params['from']));
        $to->appendChild($dom_xml->createTextNode($params['to']));
        $ssel_id->appendChild($dom_xml->createTextNode($params['ssel_id']));

        $root->appendChild($from);
        $root->appendChild($to);
        $root->appendChild($ssel_id);

        $dom_xml->appendChild($root);

        $datas = $dom_xml->saveXml();

        $mailed = false;

        try {
            $user_from = User_Adapter::getInstance($params['from'], $this->app);
            $user_to = User_Adapter::getInstance($params['to'], $this->app);
        } catch (Exception $e) {
            return false;
        }

        if ($this->shouldSendNotificationFor($params['to'])) {

            $readyToSend = false;
            try {
                $basket = $this->app['EM']
                    ->getRepository('\Entities\Basket')
                    ->find($params['ssel_id']);
                $title = $basket->getName();

                $receiver = Receiver::fromUser($user_to);
                $emitter = Receiver::fromUser($user_from);

                $readyToSend = true;
            } catch (\Exception $e) {

            }

            if ($readyToSend) {
                $mail = MailInfoValidationReminder::create($this->app, $receiver, $emitter);
                $mail->setButtonUrl($params['url']);
                $mail->setTitle($title);

                $this->app['notification.deliverer']->deliver($mail);
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

        $bask_link = '<a href="#" onclick="openPreview(\'BASK\',1,\''
            . (string) $sx->ssel_id . '\');return false;">'
            . $basket_name . '</a>';

        $ret = array(
            'text'  => sprintf(
                _('Rappel : Il vous reste %1$d jours pour valider %2$s de %3$s'), $this->app['phraseanet.registry']->get('GV_validation_reminder'), $bask_link, $sender
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
        return _('Rappel pour une demande de validation');
    }

    /**
     *
     * @return string
     */
    public function is_available()
    {
        return true;
    }
}
