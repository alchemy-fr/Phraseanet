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
     * @param  string        $event
     * @param  Array         $params
     * @param  mixed content $object
     * @return Void
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

            //Sender
            if (null !== $user = $lazaretFile->getSession()->getUser()) {
                $sender = $domXML->createElement('sender');
                $sender->appendChild($domXML->createTextNode($user->get_display_name()));
                $root->appendChild($sender);

                //Filename
                $filename = $domXML->createElement('filename');
                $filename->appendChild($domXML->createTextNode($lazaretFile->getOriginalName()));
                $root->appendChild($filename);

                //Reasons for quarantine
                $reasons = $domXML->createElement('reasons');

                foreach ($lazaretFile->getChecks() as $check) {
                    /* @var $check \Entities\LazaretCheck */
                    $reason = $domXML->createElement('reason');
                    $reason->appendChild($domXML->createTextNode($check->getMessage()));
                    $reasons->appendChild($reason);
                }

                $root->appendChild($reasons);

                $domXML->appendChild($root);

                $datas = $domXML->saveXml();

                $this->broker->notify($user->get_id(), __CLASS__, $datas);
            }
        }

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

        $reasons = array();

        foreach ($sx->reasons as $reason) {
            $reasons[] = (string) $reason->reason;
        }

        $filename = (string) $sx->filename;

        $ret = array(
            'text'  => sprintf(_('The document %s has been quarantined for the following reasons : %s'), $filename, implode(',', $reasons)),
            'class' => ''
        );

        return $ret;
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return _('Notificaton quarantine');
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
     *
     */
    public function email()
    {
        return false;
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
