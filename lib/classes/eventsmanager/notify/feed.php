<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailInfoNewPublication;

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class eventsmanager_notify_feed extends eventsmanager_notifyAbstract
{
    /**
     *
     * @var string
     */
    public $events = array('__FEED_ENTRY_CREATE__');

    /**
     *
     * @return string
     */
    public function icon_url()
    {
        return '/skins/icons/rss16.png';
    }

    /**
     *
     * @param  string        $event
     * @param  Array         $params
     * @param  mixed content $object
     * @return boolean
     */
    public function fire($event, $params, &$entry)
    {
        $params = array(
            'entry_id' => $entry->getId()
        );

        $dom_xml = new DOMDocument('1.0', 'UTF-8');

        $dom_xml->preserveWhiteSpace = false;
        $dom_xml->formatOutput = true;

        $root = $dom_xml->createElement('datas');

        $entry_id = $dom_xml->createElement('entry_id');

        $entry_id->appendChild($dom_xml->createTextNode($params['entry_id']));

        $root->appendChild($entry_id);

        $dom_xml->appendChild($root);

        $datas = $dom_xml->saveXml();

        $Query = new \User_Query($this->app);

        $Query->include_phantoms(true)
            ->include_invite(false)
            ->include_templates(false)
            ->email_not_null(true);

        if ($entry->getFeed()->getCollection($this->app)) {
            $Query->on_base_ids(array($entry->getFeed()->getCollection($this->app)->get_base_id()));
        }

        $start = 0;
        $perLoop = 100;

        $from = array(
            'email' => $entry->getAuthorEmail(),
            'name'  => $entry->getAuthorName()
        );

        do {
            $results = $Query->limit($start, $perLoop)->execute()->get_results();

            foreach ($results as $user_to_notif) {
                /* @var $user_to_notif \User_Adapter */
                $mailed = false;

                if ($this->shouldSendNotificationFor($user_to_notif->get_id())) {
                    $readyToSend = false;
                    try {
                        $token = $this->app['tokens']->getUrlToken(
                                \random::TYPE_FEED_ENTRY
                                , $user_to_notif->get_id()
                                , null
                                , $entry->getId()
                        );

                        $url = $this->app->url('lightbox', array('LOG' => $token));

                        $receiver = Receiver::fromUser($user_to_notif);
                        $readyToSend = true;
                    } catch (\Exception $e) {

                    }

                    if ($readyToSend) {
                        $mail = MailInfoNewPublication::create($this->app, $receiver);
                        $mail->setButtonUrl($url);
                        $mail->setAuthor($entry->getAuthorName());
                        $mail->setTitle($entry->getTitle());

                        $this->app['notification.deliverer']->deliver($mail);
                        $mailed = true;
                    }
                }

                $this->broker->notify($user_to_notif->get_id(), __CLASS__, $datas, $mailed);

            }
            $start += $perLoop;
        } while (count($results) > 0);

        return true;
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

        try {
            $entry = $this->app['EM']->getRepository('Entities\FeedEntry')->find((int) $sx->entry_id);
        } catch (\Exception $e) {
            return array();
        }

        if (null === $entry) {
            return array();
        }

        $ret = array(
            'text'  => sprintf(
                _('%1$s has published %2$s')
                , $entry->getAuthorName()
                , '<a href="/lightbox/feeds/entry/' . $entry->getId() . '/" target="_blank">' . $entry->getTitle() . '</a>'
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
        return _('Feeds');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return _('Receive notification when a publication is available');
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
