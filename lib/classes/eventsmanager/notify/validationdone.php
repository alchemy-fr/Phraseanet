<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Notification\Mail\MailInfoValidationDone;
use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Receiver;

class eventsmanager_notify_validationdone extends eventsmanager_notifyAbstract
{
    /**
     *
     * @var string
     */
    public $events = ['__VALIDATION_DONE__'];

    /**
     *
     * @return notify_validationdone
     */
    public function __construct(Application $app, eventsmanager_broker $broker)
    {
        parent::__construct($app, $broker);
        $this->group = $this->app->trans('Validation');

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
     * @param  Array         $event
     * @param  Array         $params
     * @param  mixed content $object
     * @return boolean
     */
    public function fire($event, $params, &$object)
    {
        $default = [
            'from'    => ''
            , 'to'      => ''
            , 'ssel_id' => ''
        ];

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

        if ($this->shouldSendNotificationFor($params['to'])) {
            $readyToSend = false;
            try {
                $user_from = $this->app['manipulator.user']->getRepository()->find($params['from']);
                $user_to = $this->app['manipulator.user']->getRepository()->find($params['to']);

                $basket = $this->app['EM']
                    ->getRepository('Phraseanet:Basket')
                    ->find($params['ssel_id']);
                $title = $basket->getName();

                $receiver = Receiver::fromUser($user_to);
                $emitter = Emitter::fromUser($user_from);

                $readyToSend = true;
            } catch (\Exception $e) {

            }

            if ($readyToSend) {
                $mail = MailInfoValidationDone::create($this->app, $receiver, $emitter);
                $mail->setButtonUrl($params['url']);
                $mail->setTitle($title);
                $mail->setUser($user_from);

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

        if (null === $registered_user = $this->app['manipulator.user']->getRepository()->find($from)) {
            return [];
        }

        $sender = $registered_user->getDisplayName($this->app['translator']);

        try {
            $repository = $this->app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Basket');

            $basket = $repository->findUserBasket($this->app, $ssel_id, $this->app['authentication']->getUser(), false);
        } catch (Exception $e) {
            return [];
        }

        $ret = [
            'text'  => $this->app->trans('%user% a envoye son rapport de validation de %title%', ['%user%' => $sender, '%title%' => '<a href="/lightbox/validate/'
                . (string) $sx->ssel_id . '/" target="_blank">'
                . $basket->getName() . '</a>'
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
        return $this->app->trans('Rapport de Validation');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return $this->app->trans('Reception d\'un rapport de validation');
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

        return $this->app['acl']->get($user)->has_right('push');
    }
}
