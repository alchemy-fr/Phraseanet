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
class Session_Phrasea
{

  /**
   *
   * @var User_Adapter
   */
  protected $user;
  /**
   *
   * @var appbox
   */
  protected $appbox;
  /**
   *
   * @var int
   */
  protected $ses_id;

  /**
   *
   * @param appbox $appbox
   * @param User_Adapter $user
   * @param int $ses_id
   * @return Session_Phrasea
   */
  public function __construct(appbox &$appbox, User_Adapter &$user, $ses_id = null)
  {
    $this->clear_sessions();
    $this->appbox = $appbox;
    $this->user = $user;
    $this->ses_id = $ses_id;

    return $this;
  }

  /**
   *
   * @return int
   */
  public function get_id()
  {
    return $this->ses_id;
  }

  /**
   *
   * @param Browser $browser
   * @return Session_Phrasea
   */
  public function create(Browser &$browser)
  {
    if ($this->ses_id)
      throw new Exception_Session_AlreadyCreated();
    if (!$this->user)
      throw new Exception_Session_Closed('You have to create a new Phrasea session with the new user');

    if (($ses_id = phrasea_create_session($this->user->get_id())) === false)
      throw new Exception_InternalServerError();

    $this->ses_id = $ses_id;

    $this->update_informations($this->appbox, $browser);

    return $this;
  }

  /**
   *
   * @param appbox $appbox
   * @param Browser $browser
   * @param Array $logs
   */
  protected function update_informations(appbox &$appbox, Browser &$browser)
  {
    $sql = "UPDATE cache SET
              user_agent = :user_agent, ip = :ip, platform = :platform,
              browser = :browser,
              screen = :screen, browser_version = :browser_version
            WHERE session_id = :ses_id";
    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute(
            array(
                ':user_agent' => $browser->getUserAgent(),
                ':ip' => $browser->getIP(),
                ':platform' => $browser->getPlatform(),
                ':browser' => $browser->getBrowser(),
                ':screen' => $browser->getScreenSize(),
                ':browser_version' => $browser->getExtendedVersion(),
                ':ses_id' => $this->ses_id
            )
    );
    $stmt->closeCursor();
  }

  /**
   *
   * @return Session_Phrasea
   */
  public function open()
  {
    if (!$this->user instanceof User_Adapter)
      throw new Exception_Session_Closed();
    if (!phrasea_open_session($this->ses_id, $this->user->get_id()))
      throw new Exception_Session_Closed();

    return $this;
  }

  /**
   *
   * @return Session_Phrasea
   */
  public function close()
  {
    phrasea_close_session($this->ses_id);
    $this->ses_id = null;
    $this->user = null;

    return $this;
  }

//  /**
//   *
//   * @param type $usr_id
//   */
//  public static function get_actives_by_usr_id($usr_id)
//  {
//
//  }
//
//  public static function get_actives()
//  {
//
//  }

  /**
   *
   * @return Session_Phrasea
   */
  protected function clear_sessions()
  {

    $conn = connection::getPDOConnection();
    $registry = registry::get_instance();

    $sql = "SELECT session_id FROM cache
      WHERE (lastaccess < DATE_SUB(NOW(), INTERVAL 1 MONTH) AND token IS NOT NULL)
      OR (lastaccess < DATE_SUB(NOW(), INTERVAL 30 MINUTE) AND token IS NULL)";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach ($rs as $row)
    {
      phrasea_close_session($row['session_id']);
    }

    $date_two_day = new DateTime('+' . (int) $registry->get('GV_validation_reminder') . ' days');

    return $this;
  }

}
