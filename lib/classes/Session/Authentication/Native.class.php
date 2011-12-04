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
 * Native Authentication for Phraseanet (login/password)
 *
 * @package     Session
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Session_Authentication_Native implements Session_Authentication_Interface
{

  /**
   *
   * @var appbox
   */
  protected $appbox;
  /**
   *
   * @var boolean
   */
  protected $captcha_challenge_result;
  /**
   *
   * @var string
   */
  protected $login;
  /**
   *
   * @var password
   */
  protected $password;
  /**
   *
   * @var User_Adapter
   */
  protected $user;

  /**
   *
   * @param appbox $appbox
   * @param string $login
   * @param string $password
   * @return Session_Authentication_Native
   */
  public function __construct(appbox &$appbox, $login, $password)
  {
    $this->appbox = $appbox;
    $this->login = $login;
    $this->password = $password;

    try
    {
      $usr_id = User_Adapter::get_usr_id_from_login($this->login);
      $this->user = User_Adapter::getInstance($usr_id, $this->appbox);
    }
    catch (Exception $e)
    {
      throw new Exception_Unauthorized('User does not exists anymore');
    }

    return $this;
  }

  /**
   *
   * @return User_Adapter
   */
  public function get_user()
  {
    return $this->user;
  }

  /**
   *
   * @param boolean $captcha_challenge_result
   * @return Session_Authentication_Native
   */
  public function set_captcha_challenge($captcha_challenge_result)
  {
    $this->captcha_challenge_result = $captcha_challenge_result;

    return $this;
  }

  public function prelog()
  {
    return $this;
  }

  /**
   *
   * @return user_Adapter
   */
  public function signOn()
  {
    $browser = Browser::getInstance();
    $this->check_and_revoke_badlogs($browser->getIP());


    $this->check_bad_salinity()
            ->check_mail_locked()
            ->challenge_password($browser);

    return $this->user;
  }

  /**
   *
   * @return Session_Authentication_Native
   */
  public function postlog()
  {
    return $this;
  }

  /**
   * Verify if the current user has not verified is account by email
   *
   * @return Session_Authentication_Native
   */
  protected function check_mail_locked()
  {
    $conn = $this->appbox->get_connection();

    $sql = 'SELECT mail_locked, usr_id
        FROM usr
        WHERE usr_id = :usr_id';
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':usr_id' => $this->user->get_id()));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if ($row && $row['mail_locked'] == "1")
      throw new Exception_Session_MailLocked();

    return $this;
  }

  /**
   *
   * @param Browser $browser
   * @return Session_Authentication_Native
   */
  public function challenge_password(Browser $browser =null)
  {
    $conn = $this->appbox->get_connection();

    $sql = 'SELECT usr_id
      FROM usr
      WHERE usr_login = :login
        AND usr.usr_password = :password
        AND usr_login NOT IN ("invite","autoregister")
        AND usr_login NOT LIKE "(#deleted_%"
        AND salted_password = 1
        AND model_of="0" AND invite="0"';

    $salt = User_Adapter::salt_password($this->password, $this->user->get_nonce());
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(
        ':login' => $this->login,
        ':password' => $salt
    ));

    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (count($rs) == 0)
    {
      if ($browser instanceof Browser)
        $this->save_badlog($browser);
      throw new Exception_Unauthorized('Bad login/Password');
    }

    return $this;
  }

  /**
   *
   * @param Browser $browser
   * @return Session_Authentication_Native
   */
  protected function save_badlog(Browser $browser)
  {
    $conn = $this->appbox->get_connection();
    $date_obj = new DateTime('-5 month');

    $sql = 'DELETE FROM badlog WHERE  date < :date';
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':date' => phraseadate::format_mysql($date_obj)));
    $stmt->closeCursor();

    $sql = 'INSERT INTO badlog (date,login,pwd,ip,locked)
            VALUES (NOW(), :login, :password, :ip, "1")';

    $params = array(
        ':login' => $this->login
        , ':password' => $this->password
        , ':ip' => $browser->getIP()
    );

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $stmt->closeCursor();

    return $this;
  }

  /**
   *
   * @return Session_Authentication_Native
   */
  protected function check_bad_salinity()
  {
    $sql = 'SELECT salted_password
      FROM usr
      WHERE usr_login = :login AND usr_password = :password';

    $params = array(
        ':login' => $this->login,
        ':password' => hash('sha256', $this->password)
    );

    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if ($row && $row['salted_password'] === '0')
      throw new Exception_Session_BadSalinity();

    return $this;
  }

  /**
   *
   * @param string $ip
   * @return Session_Authentication_Native
   */
  protected function check_and_revoke_badlogs($ip)
  {
    $conn = $this->appbox->get_connection();
    $registry = $this->appbox->get_registry();

    $sql = 'SELECT id FROM badlog
            WHERE (login = :login OR ip = :ip) AND locked="1"';

    $params = array(':login' => $this->login, ':ip' => $ip);

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $row_count = $stmt->rowCount();
    $stmt->closeCursor();

    if ($row_count == 0)

      return $this;

    if ($this->captcha_challenge_result === true)
    {
      $sql = 'UPDATE badlog SET locked="0" WHERE (login=:login OR ip=:ip)';
      $stmt = $conn->prepare($sql);
      $stmt->execute(array(':login' => $this->login, ':ip' => $ip));
      $stmt->closeCursor();
    }
    elseif ($row_count > 9)
    {
      if ($this->is_captcha_activated($registry))
        throw new Exception_Session_RequireCaptcha();
    }

    return $this;
  }

  /**
   *
   * @param registryInterface $registry
   * @return boolean
   */
  protected function is_captcha_activated(registryInterface $registry)
  {
    $registry = $this->appbox->get_registry();

    return ($registry->get('GV_captchas')
    && trim($registry->get('GV_captcha_private_key')) !== ''
    && trim($registry->get('GV_captcha_public_key') !== ''));
  }

}
