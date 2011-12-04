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
 * @package     Session
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Session_Logger
{

  /**
   *
   * @var int
   */
  protected $id;

  /**
   *
   * @var databox
   */
  protected $databox;

  const EVENT_DELETE = 'delete';
  const EVENT_EDIT = 'edit';
  const EVENT_EXPORTDOWNLOAD = 'download';
  const EVENT_EXPORTFTP = 'ftp';
  const EVENT_EXPORTMAIL = 'mail';
  const EVENT_MOVE = 'collection';
  const EVENT_PRINT = 'print';
  const EVENT_PUSH = 'push';
  const EVENT_STATUS = 'status';
  const EVENT_SUBSTITUTE = 'substit';
  const EVENT_VALIDATE = 'validate';

  /**
   *
   * @param databox $databox
   * @param int $log_id
   * @return Session_Logger
   */
  public function __construct(databox &$databox, $log_id)
  {
    $this->databox = $databox;
    $this->id = (int) $log_id;

    return $this;
  }

  /**
   *
   * @return int
   */
  public function get_id()
  {
    return $this->id;
  }

  public function log(record_adapter $record, $action, $final, $comment)
  {
    $sql = 'INSERT INTO log_docs
          (id, log_id, date, record_id, action, final, comment)
          VALUES (null, :log_id, NOW(), :record_id, :action, :final, :comm)';

    $stmt = $this->databox->get_connection()->prepare($sql);

    $params = array(
        ':log_id' => $this->get_id()
        , ':record_id' => $record->get_record_id()
        , ':action' => $action
        , ':final' => $final
        , ':comm' => $comment
    );

    $stmt->execute($params);
    $stmt->closeCursor();

    return $this;
  }

  /**
   *
   * @param databox $databox
   * @param Session_Phrasea $session
   * @param User_Adapter $user
   * @param Browser $browser
   * @return Session_Logger
   */
  public static function create(databox &$databox, Browser &$browser, Session_Handler $session, User_Adapter &$user = null)
  {
    $colls = array();
    $registry = registry::get_instance();

    if($user)
    {
      $bases = $user->ACL()->get_granted_base(array(), array($databox->get_sbas_id()));
      foreach ($bases as $collection)
      {
        $colls[] = $collection->get_coll_id();
      }
    }

    $sql = "INSERT INTO log
              (id, date,sit_session, user, site, usrid,coll_list, nav,
                version, os, res, ip, user_agent,appli, fonction,
                societe, activite, pays)
            VALUES
              (null,now() , :ses_id, :usr_login, :site_id, :usr_id, :coll_list
              , :browser, :browser_version,  :platform, :screen, :ip
              , :user_agent, :appli, :fonction, :company, :activity, :country)";

    $params = array(
        ':ses_id' => $session->get_ses_id(),
        ':usr_login' => $user ? $user->get_login() : null,
        ':site_id' => $registry->get('GV_sit'),
        ':usr_id' => $user ? $user->get_id() : null,
        ':coll_list' => implode(',', $colls),
        ':browser' => $browser->getBrowser(),
        ':browser_version' => $browser->getExtendedVersion(),
        ':platform' => $browser->getPlatform(),
        ':screen' => $browser->getScreenSize(),
        ':ip' => $browser->getIP(),
        ':user_agent' => $browser->getUserAgent(),
        ':appli' => serialize(array()),
        ':fonction' => $user ? $user->get_job() : null,
        ':company' => $user ? $user->get_company() : null,
        ':activity' => $user ? $user->get_position() : null,
        ':country' => $user ? $user->get_country() : null
    );

    $stmt = $databox->get_connection()->prepare($sql);
    $stmt->execute($params);

    $log_id = $databox->get_connection()->lastInsertId();
    $stmt->closeCursor();

    return new Session_Logger($databox, $log_id);
  }

  public static function load(databox $databox, Session_Handler $session)
  {
    if(!$session->is_authenticated())
    {
      throw new Exception_Session_LoggerNotFound ('Not authenticated');
    }

    $sql = 'SELECT id FROM log
            WHERE site = :site AND sit_session = :ses_id';

    $params = array(
        ':site' => $databox->get_registry()->get('GV_sit')
        , ':ses_id' => $session->get_ses_id()
    );

    $stmt = $databox->get_connection()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if(!$row)
      throw new Exception_Session_LoggerNotFound ('Logger not found');

    return new self($databox, $row['id']);
  }

}
