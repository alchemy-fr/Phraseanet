<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
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
class eventsmanager_notify_downloadmailfail extends eventsmanager_notifyAbstract
{
    const MAIL_NO_VALID = 1;
    const MAIL_FAIL = 2;

    /**
     *
     * @var string
     */
    public $events = array('__EXPORT_MAIL_FAIL__');

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
            'usr_id' => null
            , 'lst'    => ''
            , 'ssttid' => ''
            , 'dest'   => ''
            , 'reason' => ''
        );

        $params = array_merge($default, $params);

        $dom_xml = new DOMDocument('1.0', 'UTF-8');
        $dom_xml->preserveWhiteSpace = false;
        $dom_xml->formatOutput = true;

        $root = $dom_xml->createElement('datas');

        $lst = $dom_xml->createElement('lst');
        $ssttid = $dom_xml->createElement('ssttid');
        $dest = $dom_xml->createElement('dest');
        $reason = $dom_xml->createElement('reason');

        $lst->appendChild($dom_xml->createTextNode($params['lst']));
        $ssttid->appendChild($dom_xml->createTextNode($params['ssttid']));
        $dest->appendChild($dom_xml->createTextNode($params['dest']));
        $reason->appendChild($dom_xml->createTextNode($params['reason']));

        $root->appendChild($lst);
        $root->appendChild($ssttid);
        $root->appendChild($dest);
        $root->appendChild($reason);

        $dom_xml->appendChild($root);

        $datas = $dom_xml->saveXml();

        $mailed = false;

        if ($this->shouldSendNotificationFor($params['usr_id'])) {
            $user = User_Adapter::getInstance($params['usr_id'], $this->app);
            $name = $user->get_display_name();

            $to = array('email' => $user->get_email(), 'name'  => $name);

            $from = array(
                'email' => $this->app['phraseanet.registry']->get('GV_defaulmailsenderaddr'),
                'name'  => $this->app['phraseanet.registry']->get('GV_homeTitle')
            );

            if (parent::email())
                $mailed = true;
        }

        $this->broker->notify($params['usr_id'], __CLASS__, $datas, $mailed);

        return;
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
        $usr_id = (int) $sx->usr_id;
        $reason = (int) $sx->reason;
        $lst = (string) $sx->lst;
        $ssttid = (int) $sx->ssttid;
        $dest = (string) $sx->dest;

        if ($reason == self::MAIL_NO_VALID) {
            $reason = _('email is not valid');
        } elseif ($reason == self::MAIL_FAIL) {
            $reason = _('failed to send mail');
        } else {
            $reason = _('an error occured while exporting records');
        }

        $text = sprintf(
            _("The delivery to %s failed for the following reason : %s")
            , $dest
            , $reason
        );

        $ret = array(
            'text'  => $text
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
        return _('Email export fails');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return _('Get a notification when a mail export fails');
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
