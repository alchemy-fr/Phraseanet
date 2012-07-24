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
class eventsmanager_notify_register extends eventsmanager_notifyAbstract
{
    /**
     *
     * @var string
     */
    public $events = array('__REGISTER_APPROVAL__');

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
            'usr_id' => ''
            , 'demand' => array()
        );

        $params = array_merge($default, $params);
        $base_ids = $params['demand'];

        if (count($base_ids) == 0) {
            return;
        }

        $mailColl = array();

        try {
            $sql = 'SELECT u.usr_id, b.base_id
      FROM usr u, basusr b
      WHERE u.usr_id = b.usr_id
      AND b.base_id
      IN (' . implode(', ', array_keys($base_ids)) . ')
      AND model_of="0"
      AND b.canadmin="1"
      AND b.actif="1"
          AND u.usr_login NOT LIKE "(#deleted%"';

            $stmt = $this->appbox->get_connection()->prepare($sql);
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

        foreach ($params['demand'] as $base_id => $is_ok) {
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
                    $admin_user = User_Adapter::getInstance($usr_id, $this->appbox);
                } catch (Exception $e) {
                    continue;
                }

                $dest = $admin_user->get_email();

                $dest = $admin_user->get_display_name();

                $to = array('email' => $admin_user->get_email(), 'name'  => $dest);
                $from = array(
                    'email' => $this->registry->get('GV_defaulmailsenderaddr'),
                    'name'  => $this->registry->get('GV_homeTitle')
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
            $registered_user = User_Adapter::getInstance($usr_id, $this->appbox);
        } catch (Exception $e) {
            return array();
        }

        $sender = User_Adapter::getInstance($usr_id, $this->appbox)->get_display_name();

        $ret = array(
            'text'  => sprintf(
                _('%1$s demande votre approbation sur une ou plusieurs %2$scollections%3$s'), $sender, '<a href="' . $this->registry->get('GV_ServerName') . 'admin/?section=registrations" target="_blank">', '</a>'
            )
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
        return _('Register approbation');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return _('Recevoir des notifications lorsqu\'un utilisateur demande une inscription necessitant mon approbation');
    }

    /**
     *
     * @param  Array   $to
     * @param  Array   $from
     * @param  string  $datas
     * @return boolean
     */
    public function mail($to, $from, $datas)
    {
        $subject = sprintf(
            _('admin::register: demande d\'inscription sur %s'), $this->registry->get('GV_homeTitle')
        );

        $body = "<div>"
            . _('admin::register: un utilisateur a fait une demande d\'inscription')
            . "</div>\n";

        $sx = simplexml_load_string($datas);

        $usr_id = (string) $sx->usr_id;

        try {
            $registered_user = User_Adapter::getInstance($usr_id, $this->appbox);
        } catch (Exception $e) {
            return false;
        }

        $body .= "<br/>\n<div>Login : "
            . $registered_user->get_login() . "</div>\n";
        $body .= "<div>" . _('admin::compte-utilisateur nom')
            . " : " . $registered_user->get_firstname() . "</div>\n";
        $body .= "<div>" . _('admin::compte-utilisateur prenom')
            . " : " . $registered_user->get_lastname() . "</div>\n";
        $body .= "<div>" . _('admin::compte-utilisateur email')
            . " : " . $registered_user->get_email() . "</div>\n";
        $body .= "<div>" . _('admin::compte-utilisateur adresse')
            . " : " . $registered_user->get_address() . "</div>\n";
        $body .= "<div>" . $registered_user->get_city()
            . " " . $registered_user->get_zipcode() . "</div>\n";
        $body .= "<div>" . _('admin::compte-utilisateur telephone')
            . " : " . $registered_user->get_tel() . "</div>\n";
        $body .= "<div>" . _('admin::compte-utilisateur fax')
            . " : " . $registered_user->get_fax() . "</div>\n";
        $body .= "<div>" . _('admin::compte-utilisateur poste')
            . "/" . _('admin::compte-utilisateur societe')
            . " " . $registered_user->get_job()
            . " " . $registered_user->get_company() . "</div>\n";

        $base_ids = $sx->base_ids;

        $body .= "<br/>\n<div>"
            . _('admin::register: les demandes de l\'utilisateur portent sur les bases suivantes')
            . "</div>\n";
        $body .= "<ul>\n";

        foreach ($base_ids->base_id as $base_id) {
            $body .= "<li>"
                . phrasea::sbas_names(phrasea::sbasFromBas((string) $base_id))
                . ' - '
                . phrasea::bas_names((string) $base_id) . "</li>\n";
        }

        $body .= "</ul>\n";

        $body .= "<br/>\n<div><a href='" . $this->registry->get('GV_ServerName')
            . "login/admin' target='_blank'>"
            . _('admin::register: vous pourrez traiter ses demandes en ligne via l\'interface d\'administration')
            . "</a></div>\n";

        return mail::send_mail($subject, $body, $to, $from);
    }

    /**
     *
     * @return boolean
     */
    public function is_available()
    {
        $bool = false;

        $session = $this->appbox->get_session();
        if ( ! $session->is_authenticated() || ! login::register_enabled()) {
            return false;
        }

        try {
            $user = User_Adapter::getInstance($session->get_usr_id(), $this->appbox);
        } catch (Exception $e) {
            return false;
        }

        if ($user->ACL()->has_right('manageusers')) {
            $bool = true;
        }

        return $bool;
    }
}
