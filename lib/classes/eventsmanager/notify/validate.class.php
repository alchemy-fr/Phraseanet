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
    public function __construct(appbox &$appbox, \Alchemy\Phrasea\Core $core, eventsmanager_broker &$broker)
    {
        $this->group = _('Validation');
        parent::__construct($appbox, $core, $broker);

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

        $send_notif = ($this->get_prefs(__CLASS__, $params['to']) != '0');
        if ($send_notif) {
            $to = array(
                'email' => $params['to_email'],
                'name'  => $params['to_name']
            );
            $from = array(
                'email'  => $params['from_email'],
                'name'   => $params['from_email']
            );
            $message = $params['message'];
            $url = $params['url'];
            $accuse = $params['accuse'];

            if (self::mail($to, $from, $message, $url, $accuse))
                $mailed = true;
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
            $registered_user = User_Adapter::getInstance($from, $this->appbox);
        } catch (Exception $e) {
            return array();
        }

        $sender = User_Adapter::getInstance($from, $this->appbox)->get_display_name();

        try {
            $em = $this->core->getEntityManager();
            $repository = $em->getRepository('\Entities\Basket');

            $basket = $repository->findUserBasket($ssel_id, $this->core->getAuthenticatedUser(), false);

            $basket_name = trim($basket->getName()) ? : _('Une selection');
        } catch (Exception $e) {
            $basket_name = _('Une selection');
        }

        $bask_link = '<a href="'
            . $this->registry->get('GV_ServerName') . 'lightbox/validate/'
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
     * @param  Array   $to
     * @param  Array   $from
     * @param  string  $message
     * @param  string  $url
     * @param  boolean $accuse
     * @return boolean
     */
    public function mail($to, $from, $message, $url, $accuse)
    {
        $subject = _('push::mail:: Demande de validation de documents');

        $body = '<div>' . sprintf(
                _('Le lien suivant vous propose de valider une selection faite par %s'), $from['name']
            )
            . "</div>\n";

        $body .= "<br/>\n";
        $body .= '<div><a href="' . $url
            . '" target="_blank">' . $url . "</a></div>\n" . $message;

        $body .= "<br/>\n<br/>\n<br/>\n"
            . _('push::atention: ce lien est unique et son contenu confidentiel, ne divulguez pas');

        return mail::send_mail($subject, $body, $to, $from, array(), $accuse);
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
