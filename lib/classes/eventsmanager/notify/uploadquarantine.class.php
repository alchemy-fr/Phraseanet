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
class eventsmanager_notify_uploadquarantine extends eventsmanager_notifyAbstract
{
    /**
     *
     * @var string
     */
    public $events = array('__UPLOAD_QUARANTINE__');

    /**
     *
     * @return string
     */
    public function icon_url()
    {
        return '';
    }

    /**
     *
     * @param string        $event
     * @param Array         $params
     * @param mixed content $object
     */
    public function fire($event, $params, &$object)
    {
        if (isset($params['lazaret_file']) && $params['lazaret_file'] instanceof \Entities\LazaretFile) {
            /* @var $lazaretFile \Entities\LazaretFile */
            $lazaretFile = $params['lazaret_file'];

            $domXML = new DOMDocument('1.0', 'UTF-8');
            $domXML->preserveWhiteSpace = false;
            $domXML->formatOutput = true;

            $root = $domXML->createElement('datas');

            //Filename
            $filename = $domXML->createElement('filename');
            $filename->appendChild($domXML->createTextNode($lazaretFile->getOriginalName()));
            $root->appendChild($filename);

            //Reasons for quarantine
            $reasons = $domXML->createElement('reasons');

            foreach ($lazaretFile->getChecks() as $check) {
                /* @var $check \Entities\LazaretCheck */
                $reason = $domXML->createElement('checkClassName');
                $reason->appendChild($domXML->createTextNode($check->getCheckClassname()));
                $reasons->appendChild($reason);
            }

            $root->appendChild($reasons);

            $domXML->appendChild($root);

            $datas = $domXML->saveXml();

            //Sender
            if (null !== $user = $lazaretFile->getSession()->getUser()) {
                $sender = $domXML->createElement('sender');
                $sender->appendChild($domXML->createTextNode($user->get_display_name()));
                $root->appendChild($sender);

                $this->notifyUser($user, $datas);
            } else { //No lazaretSession user, fil is uploaded via automated tasks etc ..
                $query = new User_Query($this->appbox);

                $users = $query
                    ->on_base_ids(array($lazaretFile->getBaseId()))
                    ->who_have_right(array('canaddrecord'))
                    ->execute()
                    ->get_results();

                foreach ($users as $user) {
                    $this->notifyUser($user, $datas);
                }
            }
        }

        return;
    }

    /**
     * Notifiy an user using the specified datas
     *
     * @param \User_Adapter $user
     * @param string        $datas
     */
    private function notifyUser(\User_Adapter $user, $datas)
    {
        $mailed = false;

        if ( ! ! (int) $this->get_prefs(__CLASS__, $user->get_id())) {
            $to = array('email' => $user->get_email(), 'name'  => $user->get_display_name());

            $from = array(
                'email' => $this->registry->get('GV_defaulmailsenderaddr'),
                'name'  => $this->registry->get('GV_homeTitle')
            );

            if (self::mail($to, $from, $datas)) {
                $mailed = true;
            }
        }

        $this->broker->notify($user->get_id(), __CLASS__, $datas, $mailed);
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

        $reasons = array();

        foreach ($sx->reasons as $reason) {
            $checkClassName = (string) $reason->checkClassName;

            if (class_exists($checkClassName)) {
                $reasons[] = $checkClassName::getMessage();
            }
        }

        $filename = (string) $sx->filename;

        $text = sprintf(_('The document %s has been quarantined'), $filename);

        if ( ! ! count($reasons)) {
            $text .= ' ' . sprintf(_('for the following reasons : %s'), implode(', ', $reasons));
        }

        $ret = array('text'  => $text, 'class' => '');

        return $ret;
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return _('Quarantine notificaton');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return _('be notified when a document is placed in quarantine');
    }

    /**
     * @return boolean
     */
    public function mail($to, $from, $datas)
    {
        $subject = _('A document has been quarantined');

        $datas = $this->datas($datas, false);

        $body = $datas['text'];

        return \mail::send_mail($subject, $body, $to, $from);
    }

    /**
     *
     * @return boolean
     */
    public function is_available()
    {
        $core = \bootstrap::getCore();

        $user = $core->getAuthenticatedUser();

        if (null !== $user) {
            return $user->ACL()->has_right('addrecord');
        }

        return false;
    }
}
