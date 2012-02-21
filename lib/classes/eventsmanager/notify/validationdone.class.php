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
class eventsmanager_notify_validationdone extends eventsmanager_notifyAbstract
{

  /**
   *
   * @var string
   */
  public $events = array('__VALIDATION_DONE__');

  /**
   *
   * @return notify_validationdone
   */
  function __construct(appbox &$appbox, \Alchemy\Phrasea\Core $core, eventsmanager_broker &$broker)
  {
    $this->group = _('Validation');
    parent::__construct($appbox, $core, $broker);

    return $this;
  }

  /**
   *
   * @return string
   */
  public function icon_url()
  {
    return '/skins/icons/push16.png';
  }

  /**
   *
   * @param Array $event
   * @param Array $params
   * @param mixed content $object
   * @return boolean
   */
  public function fire($event, $params, &$object)
  {
    $default = array(
        'from' => ''
        , 'to' => ''
        , 'ssel_id' => ''
    );

    $params = array_merge($default, $params);

    $dom_xml = new DOMDocument('1.0', 'UTF-8');

    $dom_xml->preserveWhiteSpace = false;
    $dom_xml->formatOutput = true;

    $root = $dom_xml->createElement('datas');

    $from = $dom_xml->createElement('from');
    $to = $dom_xml->createElement('to');
    $ssel_id = $dom_xml->createElement('ssel_id');

    $from->appendChild($dom_xml->createTextNode($params['from']));
    $to->appendChild($dom_xml->createTextNode($params['to']));
    $ssel_id->appendChild($dom_xml->createTextNode($params['ssel_id']));

    $root->appendChild($from);
    $root->appendChild($to);
    $root->appendChild($ssel_id);

    $dom_xml->appendChild($root);

    $datas = $dom_xml->saveXml();

    $mailed = false;

    $send_notif = ($this->get_prefs(__CLASS__, $params['to']) != '0');

    if ($send_notif)
    {
      try
      {
        $user_from = User_Adapter::getInstance($params['from'], $this->appbox);
        $user_to = User_Adapter::getInstance($params['to'], $this->appbox);
      }
      catch (Exception $e)
      {
        return false;
      }

      $to = array(
          'email' => $user_to->get_email(),
          'name' => $user_to->get_display_name()
      );
      $from = array(
          'email' => $user_from->get_email(),
          'name' => $user_from->get_display_name()
      );

      if (self::mail($to, $from, $params['ssel_id'], $params['url']))
        $mailed = true;
    }

    return $this->broker->notify($params['to'], __CLASS__, $datas, $mailed);
  }

  /**
   *
   * @param string $datas
   * @param boolean $unread
   * @return Array
   */
  public function datas($datas, $unread)
  {
    $sx = simplexml_load_string($datas);

    $from = (string) $sx->from;
    $ssel_id = (string) $sx->ssel_id;

    try
    {
      $registered_user = User_Adapter::getInstance($from, $this->appbox);
    }
    catch (Exception $e)
    {
      return array();
    }

    $sender = User_Adapter::getInstance($from, $this->appbox)->get_display_name();

    try
    {
      $em = $this->core->getEntityManager();
      $repository = $em->getRepository('\Entities\Basket');

      $basket = $repository->findUserBasket($ssel_id, $this->core->getAuthenticatedUser(), false);
    }
    catch (Exception $e)
    {
      return array();
    }

    $ret = array(
        'text' => sprintf(
                _('%1$s a envoye son rapport de validation de %2$s'),
                $sender,
                '<a href="/lightbox/validate/'
                . (string) $sx->ssel_id . '/" target="_blank">'
                . $basket->getName() . '</a>'
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
    return _('Rapport de Validation');
  }

  /**
   *
   * @return string
   */
  public function get_description()
  {
    return _('Reception d\'un rapport de validation');
  }

  /**
   *
   * @param Array $to
   * @param Array $from
   * @param int $ssel_id
   * @return boolean
   */
  function mail($to, $from, $ssel_id, $url)
  {
    try
    {
      $em = $this->core->getEntityManager();
      $repository = $em->getRepository('\Entities\Basket');

      $basket = $repository->findUserBasket($ssel_id, $this->core->getAuthenticatedUser(), false);
    }
    catch (Exception $e)
    {
      return false;
    }

    $subject = sprintf(
                    _('push::mail:: Rapport de validation de %1$s pour %2$s'),
                    $from['name'],
                    $basket->getName()
    );

    $body = "<div>" . sprintf(
                    _('%s a rendu son rapport, consulter le en ligne a l\'adresse suivante'),
                    $from['name']
            ) . "</div>\n";

    $body .= "<br/>\n" . $url;

    return mail::send_mail($subject, $body, $to, $from, array());
  }

  /**
   *
   * @return boolean
   */
  function is_available()
  {
    $bool = false;

    $session = $this->appbox->get_session();
    if (!$session->is_authenticated())

      return false;

    try
    {
      $user = User_Adapter::getInstance($session->get_usr_id(), $this->appbox);
    }
    catch (Exception $e)
    {
      return false;
    }

    if ($user->ACL()->has_right('push'))
    {
      $bool = true;
    }

    return $bool;
  }

}
