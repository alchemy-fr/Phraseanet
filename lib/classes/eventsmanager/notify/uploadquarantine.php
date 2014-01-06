<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Model\Entities\LazaretCheck;
use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailInfoRecordQuarantined;

class eventsmanager_notify_uploadquarantine extends eventsmanager_notifyAbstract
{
    /**
     *
     * @var string
     */
    public $events = ['__UPLOAD_QUARANTINE__'];

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
        if (isset($params['lazaret_file']) && $params['lazaret_file'] instanceof LazaretFile) {
            /* @var $lazaretFile LazaretFile */
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
                /* @var $check LazaretCheck */
                $reason = $domXML->createElement('checkClassName');
                $reason->appendChild($domXML->createTextNode($check->getCheckClassname()));
                $reasons->appendChild($reason);
            }

            $root->appendChild($reasons);

            $domXML->appendChild($root);

            $datas = $domXML->saveXml();

            //Sender
            if (null !== $user = $lazaretFile->getSession()->getUser($this->app)) {
                $sender = $domXML->createElement('sender');
                $sender->appendChild($domXML->createTextNode($user->get_display_name()));
                $root->appendChild($sender);

                $this->notifyUser($user, $datas);
            } else { //No lazaretSession user, fil is uploaded via automated tasks etc ..
                $query = new User_Query($this->app);

                $users = $query
                    ->on_base_ids([$lazaretFile->getBaseId()])
                    ->who_have_right(['canaddrecord'])
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

        if ($this->shouldSendNotificationFor($user->get_id())) {
            $readyToSend = false;
            try {
                $receiver = Receiver::fromUser($user);
                $readyToSend = true;
            } catch (\Exception $e) {

            }

            if ($readyToSend) {
                $mail = MailInfoRecordQuarantined::create($this->app, $receiver);
                $this->app['notification.deliverer']->deliver($mail);
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

        $reasons = [];

        foreach ($sx->reasons as $reason) {
            $checkClassName = (string) $reason->checkClassName;

            if (class_exists($checkClassName)) {
                $reasons[] = $checkClassName::getMessage($this->app['translator']);
            }
        }

        $filename = (string) $sx->filename;

        $text = $this->app->trans('The document %name% has been quarantined', ['%name%' => $filename]);

        if ( ! ! count($reasons)) {
            $text .= ' ' . $this->app->trans('for the following reasons : %reasons%', ['%reasons%' => implode(', ', $reasons)]);
        }

        $ret = ['text'  => $text, 'class' => ''];

        return $ret;
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return $this->app->trans('Quarantine notificaton');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return $this->app->trans('be notified when a document is placed in quarantine');
    }

    /**
     * @param integer $usr_id The id of the user to check
     *
     * @return boolean
     */
    public function is_available($usr_id)
    {
        try {
            $user = \User_Adapter::getInstance($usr_id, $this->app);
        } catch (\Exception $e) {
            return false;
        }

        return $this->app['acl']->get($user)->has_right('addrecord');
    }
}
