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
class eventsmanager_notify_publi extends eventsmanager_notifyAbstract
{

  /**
   *
   * @var string
   */
  public $events = array('__INTERNAL_PUBLI__');

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
   * @return Void
   */
  public function fire($event, $params, &$object)
  {
    $default = array(
        'from' => ''
        , 'ssel_id' => ''
    );

    $params = array_merge($default, $params);

    $dom_xml = new DOMDocument('1.0', 'UTF-8');

    $dom_xml->preserveWhiteSpace = false;
    $dom_xml->formatOutput = true;

    $root = $dom_xml->createElement('datas');

    $from = $dom_xml->createElement('from');
    $ssel_id = $dom_xml->createElement('ssel_id');

    $from->appendChild($dom_xml->createTextNode($params['from']));
    $ssel_id->appendChild($dom_xml->createTextNode($params['ssel_id']));

    $root->appendChild($from);
    $root->appendChild($ssel_id);

    $dom_xml->appendChild($root);

    $datas = $dom_xml->saveXml();

    $from_email = '';

    try
    {
      $sql = 'SELECT usr_mail FROM usr WHERE usr_id = :usr_id';
      $stmt = $this->appbox->get_connection()->prepare($sql);
      $stmt->execute(array(':usr_id' => $params['from']));
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      $stmt->closeCursor();

      if ($row)
        $from_email = $row['usr_mail'];
    }
    catch (Exception $e)
    {

    }

    $from_email = trim($from_email) !== '' ?
            $from_email : $this->registry->get('GV_defaulmailsenderaddr');

    try
    {
      $sql = 'SELECT DISTINCT u.usr_id, u.usr_mail, u.usr_nom, u.usr_prenom
      FROM basusr b, usr u
            WHERE b.actif="1"
      AND u.model_of="0"
      AND b.base_id
        IN
        (SELECT distinct base_id
          FROM sselcont
          WHERE ssel_id = :ssel_id
        )
            AND u.usr_id = b.usr_id';

      $stmt = $this->appbox->get_connection()->prepare($sql);
      $stmt->execute(array(':ssel_id' => $params['ssel_id']));
      $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();

      foreach ($rs as $row)
      {
        $mailed = false;

        $send_notif = ($this->get_prefs(__CLASS__, $row['usr_id']) != '0');
        if ($send_notif && trim($row['usr_mail']) !== '')
        {

          $email = array(
              'email' => $row['usr_mail'],
              'name' => $row['usr_mail']
          );
          $from = array(
              'email' => $from_email,
              'name' => $from_email
          );

          if (self::mail($email, $from))
            $mailed = true;
        }

        $this->broker->notify($row['usr_id'], __CLASS__, $datas, $mailed);
      }
    }
    catch (Exception $e)
    {

    }

    return;
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

    $from = (string) $sx->from;

    $ssel_id = (string) $sx->ssel_id;
    $usr_id = $this->appbox->get_session()->get_usr_id();

    try
    {
      $registered_user = User_Adapter::getInstance($from, $this->appbox);

      try
      {
        $basket = basket_adapter::getInstance($this->appbox, $ssel_id, $usr_id);
        $basket_name = (trim($basket->get_name()) != '' ?
                        $basket->get_name() : _('Une selection'));
      }
      catch (Exception $e)
      {
        $basket_name = _('Une selection');
      }

      $bask_link = '<a href="#" onclick="openPreview(\'BASK\',1,\''
              . (string) $sx->ssel_id . '\');return false;">'
              . $basket_name . '</a>';
      if (!$registered_user->get_id())
      {
        return array();
      }

      $sender = User_Adapter::getInstance($from, $this->appbox)->get_display_name();
      $ret = array(
          'text' => sprintf(_('%1$s a publie %2$s'), $sender, $bask_link)
          , 'class' => ($unread == 1 ? 'reload_baskets' : '')
      );

      return $ret;
    }
    catch (Exception $e)
    {
      return array();
    }
  }

  /**
   *
   * @return string
   */
  public function get_name()
  {
    return _('Publish');
  }

  /**
   *
   * @return string
   */
  public function get_description()
  {
    return _('Recevoir des notifications lorsqu\'une selection est publiee');
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
   * @return boolean
   */
  function mail($to, $from)
  {
    $subject = _('Une nouvelle publication est disponible');

    $body = "<div>"
            . _('Vous pouvez vous connecter a l\'adresse suivante afin de consulter cette publication')
            . "</div><br/>\n";

    $body .= '<div><a href="' . $this->registry->get('GV_ServerName') . '">'
            . $this->registry->get('GV_ServerName') . "</a></div>\n";

    $body .= " <br/> ";

    return mail::send_mail($subject, $body, $to, $from, array());
  }

}
