<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
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
     * @param string $event
     * @param Array $params
     * @param mixed content $object
     * @return boolean
     */
    public function fire($event, $params, &$entry)
    {
        $params = array(
            'entry_id' => $entry->get_id()
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

        $Query = new \User_Query($this->appbox);

        $Query->include_phantoms(true)->include_invite(false)->include_templates(false);

        if ($entry->get_feed()->get_collection()) {
            $Query->on_base_ids(array($entry->get_feed()->get_collection()->get_base_id()));
        }

        $start = 0;
        $perLoop = 100;

        $from = array(
            'email' => $entry->get_author_email(),
            'name'  => $entry->get_author_name()
        );

        do {
            $results = $Query->limit($start, $perLoop)->execute()->get_results();

            foreach ($results as $user_to_notif) {
                /* @var $user_to_notif \User_Adapter */
                $mailed = false;

                $send_notif = ($this->get_prefs(__CLASS__, $user_to_notif->get_id()) != '0');
                if ($send_notif) {
                    $email = array(
                        'email' => $user_to_notif->get_email(),
                        'name'  => $user_to_notif->get_display_name()
                    );

                    $token = \random::getUrlToken(
                            \random::TYPE_FEED_ENTRY
                            , $user_to_notif->get_id()
                            , null
                            , $entry->get_id()
                    );

                    $url = $this->appbox->get_registry()->get('GV_ServerName') . 'lightbox/index.php?LOG=' . $token;

                    if (self::mail($email, $from, $url, $entry))
                        $mailed = true;
                }

                $this->broker->notify($user_to_notif->get_id(), __CLASS__, $datas, $mailed);
            }
            $start += $perLoop;
        }
        while (count($results) > 0);

        return true;
    }

    /**
     *
     * @param Array $datas
     * @param boolean $unread
     * @return Array
     */
    public function datas($datas, $unread)
    {
        $sx = simplexml_load_string($datas);

        try {
            $entry = \Feed_Entry_Adapter::load_from_id($this->appbox, (int) $sx->entry_id);
        } catch (\Exception $e) {
            return array();
        }

        $ret = array(
            'text'  => sprintf(
                _('%1$s has published %2$s')
                , $entry->get_author_name()
                , '<a href="/lightbox/feeds/entry/' . $entry->get_id() . '/" target="_blank">' . $entry->get_title() . '</a>'
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
        return _('Recevoir des notifications lorsqu\'on me push quelque chose');
    }

    /**
     *
     * @return boolean
     */
    function is_available()
    {
        return true;
    }

    /**
     *
     * @param Array $to
     * @param Array $from
     * @param string $message
     * @param string $url
     * @param boolean $accuse
     * @return boolean
     */
    function mail($to, $from, $url, \Feed_Entry_Adapter $entry)
    {
        $subject = sprintf(_('Nouvelle publication : %s'), $entry->get_title());

        $body = "<div>"
            . sprintf('%s vient de publier %s', $entry->get_author_name(), $entry->get_title())
            . _('Connectez vous a l\'adresse suivante pour la consulter')
            . "</div>\n";

        $body .= '<div><a href="' . $url . '">' . $url . "</a></div>\n";

        $body .= " <br/> ";

        $body .= "<br/>\n<br/>\n<br/>\n"
            . _('push::atention: ce lien est unique et son contenu confidentiel, ne divulguez pas');

        return mail::send_mail($subject, $body, $to, $from, array());
    }
}
