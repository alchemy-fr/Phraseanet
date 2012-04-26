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
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class eventsmanager_notify_ordernotdelivered extends eventsmanager_notifyAbstract
{
    /**
     *
     * @var string
     */
    public $events = array('__ORDER_NOT_DELIVERED__');

    function __construct(appbox &$appbox, \Alchemy\Phrasea\Core $core, eventsmanager_broker &$broker)
    {
        $this->group = _('Commande');
        parent::__construct($appbox, $core, $broker);

        return $this;
    }

    public function icon_url()
    {
        return '/skins/prod/000000/images/disktt_history.gif';
    }

    public function fire($event, $params, &$object)
    {
        $default = array(
            'from' => ''
            , 'to'   => ''
            , 'n'    => ''
        );

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

        $send_notif = ($this->get_prefs(__CLASS__, $params['to']) != '0');
        if ($send_notif) {
            try {
                $user_from = User_Adapter::getInstance($params['from'], $this->appbox);
                $user_to = User_Adapter::getInstance($params['to'], $this->appbox);
            } catch (Exception $e) {
                return false;
            }

            $to = array(
                'email' => $user_to->get_email(),
                'name'  => $user_to->get_display_name()
            );
            $from = array(
                'email' => $user_from->get_email(),
                'name'  => $user_from->get_display_name()
            );

            if (self::mail($to, $from, $params['n']))
                $mailed = true;
        }

        return $this->broker->notify($params['to'], __CLASS__, $datas, $mailed);
    }

    public function datas($datas, $unread)
    {
        $sx = simplexml_load_string($datas);

        $from = (string) $sx->from;
        $n = (int) $sx->n;

        try {
            $registered_user = User_Adapter::getInstance($from, $this->appbox);
        } catch (Exception $e) {
            return array();
        }

        $sender = User_Adapter::getInstance($from, $this->appbox)->get_display_name();

        $ret = array(
            'text'  => sprintf(
                _('%1$s a refuse la livraison de %2$d document(s) pour votre commande'), $sender, $n
            )
            , 'class' => ''
        );

        return $ret;
    }

    public function get_name()
    {
        return _('Refus d\'elements de commande');
    }

    public function get_description()
    {
        return _('Refus d\'elements de commande');
    }

    function mail($to, $from, $n)
    {
        $subject = sprintf(_('push::mail:: Refus d\'elements de votre commande'));

        $body = "<div>"
            . sprintf(
                _('%s a refuse %d elements de votre commande'), $from['name'], $n
            ) . "</div>\n";

        return mail::send_mail($subject, $body, $to, $from, array());
    }

    /**
     *
     * @return boolean
     */
    function is_available()
    {
        return true;
    }
}
