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
class eventsmanager_notify_autoregister extends eventsmanager_notifyAbstract
{
    /**
     *
     * @var string
     */
    public $events = array('__REGISTER_AUTOREGISTER__');

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
            'usr_id'       => ''
            , 'autoregister' => array()
        );

        $params = array_merge($default, $params);
        $base_ids = array_keys($params['autoregister']);

        if (count($base_ids) == 0) {
            return;
        }

        $mailColl = array();

        $sql = 'SELECT u.usr_id, b.base_id FROM usr u, basusr b
      WHERE u.usr_id = b.usr_id
      AND b.base_id
        IN (' . implode(', ', array_keys($base_ids)) . ')
      AND model_of="0"
      AND b.actif="1"
      AND b.canadmin="1"
          AND u.usr_login NOT LIKE "(#deleted%"';

        try {
            $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($rs as $row) {
                if ( ! isset($mailColl[$row['usr_id']]))
                    $mailColl[$row['usr_id']] = array();

                $mailColl[$row['usr_id']][] = $row['base_id'];
            }
        } catch (Exception $e) {

        }

        $dom_xml = new DOMDocument('1.0', 'UTF-8');

        $dom_xml->preserveWhiteSpace = false;
        $dom_xml->formatOutput = true;

        $root = $dom_xml->createElement('datas');

        $usr_id = $dom_xml->createElement('usr_id');
        $base_ids = $dom_xml->createElement('base_ids');

        $usr_id->appendChild($dom_xml->createTextNode($params['usr_id']));

        foreach ($params['autoregister'] as $base_id => $collection) {
            $base_id_node = $dom_xml->createElement('base_id');
            $base_id_node->appendChild($dom_xml->createTextNode($base_id));
            $base_ids->appendChild($base_id_node);
        }

        $root->appendChild($usr_id);
        $root->appendChild($base_ids);

        $dom_xml->appendChild($root);

        $datas = $dom_xml->saveXml();

        foreach ($mailColl as $usr_id => $base_ids) {

            $mailed = false;

            $send_notif = ($this->get_prefs(__CLASS__, $usr_id) != '0');

            if ($send_notif) {
                try {
                    $admin_user = User_Adapter::getInstance($usr_id, $this->app);
                } catch (Exception $e) {
                    continue;
                }

                $dest = $admin_user->get_email();

                if (trim($admin_user->get_firstname() . ' ' . $admin_user->get_lastname()) != '')
                    $dest = $admin_user->get_firstname() . ' ' . $admin_user->get_lastname();

                $to = array('email' => $admin_user->get_email(), 'name'  => $dest);
                $from = array(
                    'email' => $this->app['phraseanet.registry']->get('GV_defaulmailsenderaddr'),
                    'name'  => $this->app['phraseanet.registry']->get('GV_homeTitle')
                );

                if (self::mail($to, $from, $datas))
                    $mailed = true;
            }

            $this->broker->notify($usr_id, __CLASS__, $datas, $mailed);
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

        $usr_id = (string) $sx->usr_id;
        try {
            $registered_user = User_Adapter::getInstance($usr_id, $this->app);
        } catch (Exception $e) {
            return array();
        }

        $sender = User_Adapter::getInstance($usr_id, $this->app)->get_display_name();

        $ret = array(
            'text'  => sprintf(
                _('%1$s s\'est enregistre sur une ou plusieurs %2$scollections%3$s'), $sender, '<a href="/admin/?section=users" target="_blank">', '</a>')
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
        return _('AutoRegister information');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return _('Recevoir des notifications lorsqu\'un'
                . ' utilisateur s\'inscrit sur une collection');
    }

    /**
     *
     * @param  Array   $to
     * @param  Array   $from
     * @param  Array   $datas
     * @return boolean
     */
    public function mail($to, $from, $datas)
    {
        $subject = sprintf(_('admin::register: Inscription automatique sur %s')
            , $this->app['phraseanet.registry']->get('GV_homeTitle'));

        $body = "<div>" . _('admin::register: un utilisateur s\'est inscrit')
            . "</div>\n";

        $sx = simplexml_load_string($datas);

        $usr_id = (string) $sx->usr_id;

        try {
            $registered_user = User_Adapter::getInstance($usr_id, $this->app);
        } catch (Exception $e) {
            return false;
        }

        $body .= "<br/>\n<div>Login : " . $registered_user->get_login() . "</div>\n";
        $body .= "<div>" . _('admin::compte-utilisateur nom')
            . " : " . $registered_user->get_firstname() . "</div>\n";
        $body .= "<div>" . _('admin::compte-utilisateur prenom')
            . " : " . $registered_user->get_lastname() . "</div>\n";
        $body .= "<div>" . _('admin::compte-utilisateur email')
            . " : " . $registered_user->get_email() . "</div>\n";
        $body .= "<div>" . _('admin::compte-utilisateur adresse')
            . " : " . $registered_user->get_address() . "</div>\n";
        $body .= "<div>" . $registered_user->get_city() . " "
            . $registered_user->get_zipcode() . "</div>\n";
        $body .= "<div>" . _('admin::compte-utilisateur telephone')
            . " : " . $registered_user->get_tel() . "</div>\n";
        $body .= "<div>" . _('admin::compte-utilisateur fax')
            . " : " . $registered_user->get_fax() . "</div>\n";
        $body .= "<div>" . _('admin::compte-utilisateur poste')
            . "/" . _('admin::compte-utilisateur societe') . " "
            . $registered_user->get_job() . " " . $registered_user->get_company()
            . "</div>\n";

        $base_ids = $sx->base_ids;

        $body .= "<br/>\n<div>"
            . _('admin::register: l\'utilisateur s\'est inscrit sur les bases suivantes')
            . "</div>\n";
        $body .= "<ul>\n";

        foreach ($base_ids->base_id as $base_id) {
            $body .= "<li>"
                . phrasea::sbas_names(phrasea::sbasFromBas($this->app, (string) $base_id), $this->app)
                . ' - ' . phrasea::bas_names((string) $base_id, $this->app) . "</li>\n";
        }

        $body .= "</ul>\n";

        $body .= "<br/>\n<div><a href='/login/?redirect=admin' target='_blank'>"
            . _('admin::register: vous pourrez consulter son compte en ligne via l\'interface d\'administration')
            . "</a></div>\n";

        return mail::send_mail($this->app, $subject, $body, $to, $from);
    }

    /**
     *
     * @return boolean
     */
    public function is_available()
    {
        $bool = false;
        $login = new \login();

        if ( ! $this->app->isAuthenticated() || ! $login->register_enabled($this->app)) {
            return false;
        }

        if ($this->app['phraseanet.user']->ACL()->has_right('manageusers') === true) {
            $bool = true;
        }

        return $bool;
    }
}
