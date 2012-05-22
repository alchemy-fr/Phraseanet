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
class eventsmanager_notify_order extends eventsmanager_notifyAbstract
{
    /**
     *
     * @var string
     */
    public $events = array('__NEW_ORDER__');

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
            'usr_id'   => ''
            , 'order_id' => array()
        );

        $params = array_merge($default, $params);
        $order_id = $params['order_id'];

        $users = array();

        try {
            $sql = 'SELECT DISTINCT e.base_id
          FROM order_elements e
          WHERE e.order_id = :order_id';
            $stmt = $this->appbox->get_connection()->prepare($sql);
            $stmt->execute(array(':order_id' => $order_id));
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $base_ids = array();
            foreach ($rs as $row) {
                $base_ids[] = $row['base_id'];
            }

            $query = new User_Query($this->appbox);
            $users = $query->on_base_ids($base_ids)
                    ->who_have_right(array('order_master'))
                    ->execute()->get_results();
        } catch (Exception $e) {

        }

        if (count($users) == 0) {
            return;
        }

        $dom_xml = new DOMDocument('1.0', 'UTF-8');

        $dom_xml->preserveWhiteSpace = false;
        $dom_xml->formatOutput = true;

        $root = $dom_xml->createElement('datas');

        $usr_id_dom = $dom_xml->createElement('usr_id');
        $order_id_dom = $dom_xml->createElement('order_id');

        $usr_id_dom->appendChild($dom_xml->createTextNode($params['usr_id']));

        $order_id_dom->appendChild($dom_xml->createTextNode($order_id));


        $root->appendChild($usr_id_dom);
        $root->appendChild($order_id_dom);

        $dom_xml->appendChild($root);

        $datas = $dom_xml->saveXml();

        foreach ($users as $user) {
            $usr_id = $user->get_id();
            $mailed = false;

            $send_notif = ($this->get_prefs(__CLASS__, $usr_id) != '0');
            if ($send_notif) {
                $dest = User_Adapter::getInstance($usr_id, $this->appbox)->get_display_name();

                $to = array('email' => $user->get_email(), 'name'  => $dest);
                $from = array(
                    'email' => $this->registry->get('GV_defaulmailsenderaddr'),
                    'name'  => $this->registry->get('GV_homeTitle')
                );

                if (self::mail($to, $from, $datas)) {
                    $mailed = true;
                }
            }

            $this->broker->notify($usr_id, __CLASS__, $datas, $mailed);
        }

        return;
    }

    /**
     *
     * @param  Array   $datas
     * @param  boolean $unread
     * @return string
     */
    public function datas($datas, $unread)
    {
        $sx = simplexml_load_string($datas);

        $usr_id = (string) $sx->usr_id;
        $order_id = (string) $sx->order_id;

        try {
            $registered_user = User_Adapter::getInstance($usr_id, $this->appbox);
        } catch (Exception $e) {
            return array();
        }

        $sender = User_Adapter::getInstance($usr_id, $this->appbox)->get_display_name();

        $ret = array(
            'text'  => sprintf(_('%1$s a passe une %2$scommande%3$s')
                , $sender
                , '<a href="#" onclick="load_order(' . $order_id . ')">'
                , '</a>')
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
        return _('Nouvelle commande');
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return _('Recevoir des notifications lorsqu\'un utilisateur commande des documents');
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
        $subject = sprintf(_('admin::register: Nouvelle commande sur %s')
            , $this->registry->get('GV_homeTitle'));

        $body = "<div>"
            . _('admin::register: un utilisateur a commande des documents')
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
            . _('Retrouvez son bon de commande dans l\'interface')
            . "</div>\n";


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
        if ( ! $session->is_authenticated()) {
            return false;
        }

        try {
            $user = User_Adapter::getInstance($session->get_usr_id(), $this->appbox);
        } catch (Exception $e) {
            return false;
        }

        if ($user->ACL()->has_right('order_master')) {
            $bool = true;
        }

        return $bool;
    }
}
