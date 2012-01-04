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
class Session_Handler
{

  /**
   *
   * @var Session_Handler
   */
  protected static $_instance;

  /**
   *
   * @var Session_Storage_Interface
   */
  protected $session_storage;

  /**
   *
   * @var Session_Phrasea
   */
  protected $phrasea_session;
  protected $appbox;
  protected static $_cookie;

  /**
   * Constructor
   *
   * @return Session_Handler
   */
  protected function __construct(appbox &$appbox)
  {
    $this->appbox = $appbox;
    $this->init_session_storage();

    if ($this->is_authenticated())
    {
      try
      {
        $user = User_Adapter::getInstance($this->get_usr_id(), $appbox);
        $this->restore($user, $this->get_ses_id());
      }
      catch (Exception $e)
      {
        $this->close_phrasea_session();
      }
    }

    return $this;
  }

  /**
   *
   * @return Session_Handler
   */
  public static function getInstance(appbox &$appbox)
  {
    if (!self::$_instance)
    {
      self::$_instance = new self($appbox);
    }

    return self::$_instance;
  }

  /**
   *
   * @return Void
   */
  public function logout()
  {
//    $this->remove_cookies();
    if (!$this->is_authenticated())

      return;

    $this->storage()->reset();
    $this->close_phrasea_session();

    return;
  }

  /**
   *
   * @return Session_Storage_Interface
   */
  public function storage()
  {
    return $this->session_storage;
  }

  /**
   * Close the session storage. It can't be re-opened after that
   *
   * @return Session_Handler
   */
  public function close_storage()
  {
    $this->storage()->close();

    return $this;
  }

  /**
   * Get the current locale used in this session
   *
   * @return string
   */
  public static function get_locale()
  {
    return self::get_cookie('locale');
  }

  /**
   * Set the locale used in this session
   *
   * @param string $value under the form i18n_l10n (de_DE, en_US...)
   * @return Session_Handler;
   */
  public static function set_locale($value)
  {
    if ((self::isset_cookie('locale') && self::get_cookie('locale') != $value) || !self::isset_cookie('locale'))
      self::set_cookie("locale", $value, 0, false);
  }

  /**
   * Get the localization code
   *
   * @return string
   */
  public function get_l10n()
  {
    return array_pop(explode('_', self::get_locale()));
  }

  /**
   * Gets the internationalization code
   *
   * @return string
   */
  public function get_I18n()
  {
    return array_shift(explode('_', self::get_locale()));
  }

  /**
   * Returns wheter or not it's authenticated
   *
   * @return boolean
   */
  public function is_authenticated()
  {
    return ($this->storage()->has('ses_id') === true &&
            $this->storage()->has('usr_id') === true);
  }

  /**
   * Get the usr_id of the owner
   *
   * @deprecated
   * @return int
   */
  public function get_usr_id()
  {
    return $this->storage()->get('usr_id', null);
  }

  /**
   * Get the ses_id of the owner
   *
   * @return type
   */
  public function get_ses_id()
  {
    return $this->storage()->get('ses_id', null);
  }

  public function isset_postlog()
  {
    return $this->storage()->has('postlog');
  }

  public function set_postlog($boolean)
  {
    return $this->storage()->set('postlog', $boolean);
  }

  public function get_postlog()
  {
    return $this->storage()->get('postlog');
  }

  public function delete_postlog()
  {
    return $this->storage()->remove('postlog');
  }

  /**
   * Set temporary preference (till the session ends)
   *
   * @param string $key
   * @param mixed $value
   * @return Session_Handler
   */
  public function set_session_prefs($key, $value)
  {
    $datas = $this->storage()->get('temp_prefs');
    $datas[$key] = $value;
    $this->storage()->set('temp_prefs', $datas);

    return $this;
  }

  /**
   *
   * @param string $key
   * @return mixed
   */
  public function get_session_prefs($key)
  {
    $datas = $this->storage()->get('temp_prefs');
    if (isset($datas[$key]))

      return $datas[$key];
    return null;
  }

  /**
   *
   * @param string $name
   * @param mixed $default_value
   * @return mixed
   */
  public static function get_cookie($name, $default_value = null)
  {
    if (http_request::is_command_line() && isset(self::$_cookie[$name]))

      return self::$_cookie[$name];
    elseif (!http_request::is_command_line() && isset($_COOKIE[$name]))

      return $_COOKIE[$name];
    elseif ($default_value !== null)

      return $default_value;
    return null;
  }

  /**
   *
   * @param string $name
   * @param mixed $value
   * @param int $avalaibility
   * @param boolean $http_only
   * @return boolean
   */
  public static function set_cookie($name, $value, $avalaibility, $http_only)
  {
    $https = false;
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'])
      $https = true;

    $expire = $avalaibility === 0 ? 0 : time() + (int) $avalaibility;

    $http_only = !!$http_only;

    if ($avalaibility >= 0)
    {
      if (http_request::is_command_line())
        self::$_cookie[$name] = $value;
      else
        $_COOKIE[$name] = $value;
    }
    else
    {
      if (http_request::is_command_line() && isset(self::$_cookie[$name]))
        unset(self::$_cookie[$name]);
      else
        unset($_COOKIE[$name]);
    }
    if (!http_request::is_command_line())

      return setcookie($name, $value, $expire, '/', '', $https, $http_only);
    else

      return true;
  }

  /**
   *
   * @param string $name
   * @return boolean
   */
  public static function isset_cookie($name)
  {
    if (http_request::is_command_line())

      return isset(self::$_cookie[$name]);
    else

      return isset($_COOKIE[$name]);
  }

  /**
   * Open the phrasea session
   *
   * @return Session_Handler
   */
  public function open_phrasea_session()
  {
    if (!$this->phrasea_session instanceof Session_Phrasea)
      throw new \Exception('No phrasea session available');
    
    $this->phrasea_session->open();

    return $this;
  }

  /**
   *
   * @param User_Adapter $user
   * @param type $ses_id
   */
  public function restore(User_Adapter $user, $ses_id)
  {
//    if ($this->is_authenticated())
//      $this->close_phrasea_session();

    $this->phrasea_session = new Session_Phrasea($this->appbox, $user, $ses_id);
    $this->phrasea_session->open();
    $ses_id = $this->phrasea_session->get_id();
    $this->storage()->set('usr_id', $user->get_id());
    $this->storage()->set('ses_id', $ses_id);
  }

  /**
   * Process the authentication
   *
   * @param Session_Authentication_Interface $auth
   * @return Session_Handler
   */
  public function authenticate(Session_Authentication_Interface $auth)
  {
    if ($this->appbox->get_registry()->get('GV_maintenance'))
    {
      throw new Exception_ServiceUnavailable();
    }

    $registry = $this->appbox->get_registry();

    $conn = $this->appbox->get_connection();
    $browser = Browser::getInstance();

    $sbases = array();

    $this->send_reminders();

    $auth->prelog();
    if ($this->is_authenticated() && $this->get_usr_id() == $auth->get_user()->get_id())
    {
      return $this;
    }
    if ($this->is_authenticated() && $this->get_usr_id() != $auth->get_user()->get_id())
    {
      $this->close_phrasea_session();
    }

    $user = $auth->signOn();
    $usr_id = $user->get_id();

    $this->phrasea_session = new Session_Phrasea($this->appbox, $user);
    $this->phrasea_session->create($browser);
    $ses_id = $this->phrasea_session->get_id();
    $this->storage()->set('usr_id', $usr_id);
    $this->storage()->set('ses_id', $ses_id);

    $locale = $this->storage()->get('locale', $user->get_locale($usr_id));
    $this->storage()->set('locale', $locale);
    $user->ACL()->inject_rights();

    foreach ($user->ACL()->get_granted_sbas() as $databox)
    {
      Session_Logger::create($databox, $browser, $this, $user);
    }

    $this->set_usr_lastconn($conn, $user->get_id());
    $this->delete_postlog();

    $auth->postlog();
    $this->add_persistent_cookie();
    self::set_cookie('last_act', '', -400000, true);

    return $this;
  }

  protected function transfer_baskets()
  {
    $conn = $this->appbox->get_connection();
    $transferBasks = ($this->isset_postlog() && $this->get_postlog() === true);

    if (!$transferBasks)
      self::set_cookie('last_act', '', -400000, true);
    if ($transferBasks && Session_Handler::isset_cookie('invite-usr_id'))
    {
      $basks = array();
      $oldusr = self::get_cookie('invite-usr_id');

      if ($oldusr == $this->get_usr_id())

        return $this;

      $sql = 'SELECT sselcont_id, s.ssel_id
          FROM sselcont c, ssel s
          WHERE s.usr_id = :usr_id
            AND s.ssel_id = c.ssel_id';

      $stmt = $conn->prepare($sql);
      $stmt->execute(array(':usr_id' => $this->get_usr_id()));
      $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();

      $sql = 'UPDATE ssel set usr_id = :usr_id WHERE ssel_id = :ssel_id AND usr_id = :old_usr_id';
      $stmt = $conn->prepare($sql);

      foreach ($rs as $row)
      {
        $stmt->execute(array(':usr_id' => $this->get_usr_id(), ':ssel_id' => $row['ssel_id'], ':old_usr_id' => $oldusr));
      }
      $stmt->closeCursor();

      $sql = 'DELETE FROM ssel WHERE usr_id = :old_usr_id';
      $stmt = $conn->prepare($sql);
      $stmt->execute(array(':old_usr_id' => $oldusr));
      $stmt->closeCursor();


      $sql = 'UPDATE dsel SET usr_id = :usr_id WHERE usr_id = :old_usr_id';
      $stmt = $conn->prepare($sql);
      $stmt->execute(array(':usr_id' => $this->get_usr_id(), ':old_usr_id' => $oldusr));
      $stmt->closeCursor();

      $sql = 'DELETE FROM usr WHERE usr_id = :old_usr_id';
      $stmt = $conn->prepare($sql);
      $stmt->execute(array(':old_usr_id' => $oldusr));
      $stmt->closeCursor();
      $sql = 'DELETE FROM basusr WHERE usr_id = :old_usr_id';
      $stmt = $conn->prepare($sql);
      $stmt->execute(array(':old_usr_id' => $oldusr));
      $stmt->closeCursor();
      $sql = 'DELETE FROM sbasusr WHERE usr_id = :old_usr_id';
      $stmt = $conn->prepare($sql);
      $stmt->execute(array(':old_usr_id' => $oldusr));
      $stmt->closeCursor();
    }

    return $this;
  }

  protected function set_usr_lastconn(connection_pdo &$conn, $usr_id)
  {
    $sql = 'UPDATE usr SET last_conn=now(), locale = :locale
            WHERE usr_id = :usr_id';
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(
        ':locale' => self::get_locale(),
        ':usr_id' => $usr_id
    ));
    $stmt->closeCursor();
  }

  public function add_persistent_cookie()
  {
    $theclient = Browser::getInstance();
    $nonce = random::generatePassword(16);

    $string = $theclient->getBrowser() . '_' . $theclient->getPlatform();

    $token = User_Adapter::salt_password($string, $nonce);

    $sql = 'UPDATE cache SET nonce = :nonce, token = :token WHERE session_id = :ses_id';

    $params = array(
        ':nonce' => $nonce,
        ':ses_id' => $this->get_ses_id(),
        ':token' => $token
    );

    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute($params);
    $stmt->closeCursor();
    self::set_cookie('persistent', $token, (30 * 24 * 3600), true);

    return $this;
  }

  protected function init_session_storage()
  {
    $session_name = 'system';
    if (http_request::is_command_line())
    {
      $this->session_storage = Session_Storage_CommandLine::getInstance($session_name);
    }
    else
    {
      $this->session_storage = Session_Storage_PHPSession::getInstance($session_name);
    }

    return $this;
  }

  protected function close_phrasea_session()
  {
    if ($this->phrasea_session instanceof Session_Phrasea)
      $this->phrasea_session->close();
    $this->storage()->reset();

    return $this;
  }

  public function remove_cookies()
  {
    self::set_cookie($this->storage()->getName(), '', -420000, false);
    self::set_cookie('last_act', '{}', -420000, true);
    self::set_cookie('persistent', '', -420000, true);

    return $this;
  }

  /**
   *
   * @param databox $databox
   * @return Session_Logger
   */
  public function get_logger(databox $databox)
  {
    try
    {
      return Session_Logger::load($databox, $this);
    }
    catch(Exception_Session_LoggerNotFound $e)
    {
      $user = null;
      $browser = Browser::getInstance();

      if($this->is_authenticated())
        $user = User_Adapter::getInstance ($this->get_usr_id (), appbox::get_instance ());

      return Session_Logger::create($databox, $browser, $this, $user);
    }
  }

  protected function send_reminders()
  {
    if (!class_exists('eventsmanager_broker'))
    {
      return $this;
    }

    $Core = bootstrap::getCore();
    
    $registry = $Core->getRegistry();
    $date_two_day = new DateTime('+' . (int) $registry->get('GV_validation_reminder') . ' days');

    $events_mngr = eventsmanager_broker::getInstance($this->appbox, $Core);

    $sql = 'SELECT v.id as validate_id, v.usr_id, v.ssel_id
              , s.usr_id as owner, t.value
            FROM (validate v, ssel s)
              INNER JOIN tokens t
                ON (t.datas = s.ssel_id
                    AND v.usr_id=t.usr_id AND t.type="validate")
            WHERE expires_on < :expires_on
            AND ISNULL(last_reminder) AND confirmed="0" AND s.ssel_id = v.ssel_id ';

    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':expires_on' => phraseadate::format_mysql($date_two_day)));
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach ($rs as $row)
    {
      $params = array(
          'to' => $row['usr_id'],
          'ssel_id' => $row['ssel_id'],
          'from' => $row['owner'],
          'validate_id' => $row['validate_id'],
          'url' => $registry->get('GV_ServerName') 
              . 'lightbox/validate/'.$row['ssel_id'].'/?LOG=' . $row['value']
      );

      $events_mngr->trigger('__VALIDATION_REMINDER__', $params);
    }

    return $this;
  }

  public function get_my_sessions()
  {
    $sql = 'SELECT session_id, lastaccess, ip, platform, browser, screen
              , created_on, browser_version, token
            FROM cache WHERE usr_id = :usr_id';
    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':usr_id' => $this->get_usr_id()));
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $geonames = new geonames();

    foreach ($rs as $k => $row)
    {
      $datas = $geonames->find_geoname_from_ip($row['ip']);

      if ($datas['city'])
      {
        $infos = $datas['city'] . ' (' . $datas['country'] . ')';
      }
      elseif ($datas['fips'])
      {
        $infos = $datas['fips'] . ' (' . $datas['country'] . ')';
      }
      elseif ($datas['country'])
      {
        $infos = $datas['country'];
      }
      else
      {
        $infos = '';
      }
      $rs[$k]['session_id'] = (int) $rs[$k]['session_id'];
      $rs[$k]['ip_infos'] = $infos;
    }

    return $rs;
  }

  public function set_event_module($app, $enter)
  {
    $sql = "SELECT app FROM cache WHERE session_id = :ses_id AND usr_id = :usr_id";

    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':ses_id' => $this->get_ses_id(), ':usr_id' => $this->get_usr_id()));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $apps = false;

    if ($row)
    {
      $apps = unserialize($row['app']);
    }
    if (!is_array($apps))
      $apps = array();

    if ($enter)
    {
      if ($app && !in_array($app, $apps))
        $apps[] = $app;
    }
    elseif (in_array($app, $apps))
    {
      unset($apps[$app]);
    }

    $ret['apps'] = count($apps);

    $sql = "UPDATE cache SET lastaccess=now(),app = :apps WHERE session_id = :ses_id AND usr_id = :usr_id";

    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':ses_id' => $this->get_ses_id(), ':usr_id' => $this->get_usr_id(), ':apps' => serialize($apps)));
    $stmt->closeCursor();

    return $this;
  }

  public static function get_active_sessions()
  {

    $conn = connection::getPDOConnection();
    $date_obj = new DateTime('-5 min');
    $time = date("Y-m-d H:i:s", $date_obj->format('U'));

    $sql = "SELECT session_id,app, usr_id, user_agent, ip, lastaccess,
              platform, browser, screen, created_on, browser_version, token
            FROM cache WHERE lastaccess > :time";

    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':time' => $time));
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $geonames = new geonames();

    foreach ($rs as $k => $row)
    {
      $rs[$k]['created_on'] = new DateTime($row['created_on']);
      $rs[$k]['lastaccess'] = new DateTime($row['lastaccess']);
      $rs[$k]['token'] = !!$row['token'];
      $rs[$k]['usr_id'] = User_Adapter::getInstance($row['usr_id'], appbox::get_instance());

      $datas = $geonames->find_geoname_from_ip($row['ip']);

      if ($datas['city'])
      {
        $infos = $datas['city'] . ' (' . $datas['country'] . ')';
      }
      elseif ($datas['fips'])
      {
        $infos = $datas['fips'] . ' (' . $datas['country'] . ')';
      }
      elseif ($datas['country'])
      {
        $infos = $datas['country'];
      }
      else
      {
        $infos = '';
      }

      $rs[$k]['ip_infos'] = $infos;
    }

    return $rs;
  }

}

