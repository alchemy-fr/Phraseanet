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
 * @package     OAuth2 Connector
 *
 * @see         http://oauth.net/2/
 * @uses        http://code.google.com/p/oauth2-php/
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class API_OAuth2_Token
{
    /**
     *
     * @var appbox
     */
    protected $appbox;

    /**
     *
     * @var API_OAuth2_Account
     */
    protected $account;

    /**
     *
     * @var string
     */
    protected $token;

    /**
     *
     * @var int
     */
    protected $session_id;

    /**
     *
     * @var int
     */
    protected $expires;

    /**
     *
     * @var string
     */
    protected $scope;

    /**
     *
     * @param appbox $appbox
     * @param API_OAuth2_Account $account
     * @return API_OAuth2_Token
     */
    public function __construct(appbox &$appbox, API_OAuth2_Account &$account)
    {
        $this->appbox = $appbox;
        $this->account = $account;

        $sql = 'SELECT oauth_token, session_id, UNIX_TIMESTAMP(expires) as expires, scope
            FROM api_oauth_tokens
            WHERE api_account_id = :account_id';
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':account_id' => $this->account->get_id()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ( ! $row)
            throw new Exception_NotFound();

        $stmt->closeCursor();

        $this->token = $row['oauth_token'];
        $this->session_id = is_null($row['session_id']) ? null : (int) $row['session_id'];
        $this->expires = $row['expires'];
        $this->scope = $row['scope'];

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_value()
    {
        return $this->token;
    }

    /**
     *
     * @param string $oauth_token
     * @return API_OAuth2_Token
     */
    public function set_value($oauth_token)
    {
        $sql = 'UPDATE api_oauth_tokens SET oauth_token = :oauth_token
            WHERE oauth_token = :current_token';

        $params = array(
            ':oauth_token'   => $oauth_token
            , ':current_token' => $this->token
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->token = $oauth_token;

        return $this;
    }

    /**
     *
     * @return int
     */
    public function get_session_id()
    {
        return $this->session_id;
    }

    /**
     *
     * @param int $session_id
     * @return API_OAuth2_Token
     */
    public function set_session_id($session_id)
    {
        $sql = 'UPDATE api_oauth_tokens SET session_id = :session_id
            WHERE oauth_token = :current_token';

        $params = array(
            ':session_id'    => $session_id
            , ':current_token' => $this->token
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->session_id = (int) $session_id;

        return $this;
    }

    /**
     *
     * @return int
     */
    public function get_expires()
    {
        return $this->expires;
    }

    /**
     *
     * @param int $expires
     * @return API_OAuth2_Token
     */
    public function set_expires($expires)
    {
        $sql = 'UPDATE api_oauth_tokens SET expires = FROM_UNIXTIME(:expires)
            WHERE oauth_token = :oauth_token';

        $params = array(
            ':expires'     => $expires
            , ':oauth_token' => $this->get_value()
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->expires = $expires;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_scope()
    {
        return $this->scope;
    }

    public function set_scope($scope)
    {
        $sql = 'UPDATE api_oauth_tokens SET scope = :scope
            WHERE oauth_token = :oauth_token';

        $params = array(
            ':scope'       => $scope
            , ':oauth_token' => $this->get_value()
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->scope = $scope;

        return $this;
    }

    /**
     *
     * @return API_OAuth2_Account
     */
    public function get_account()
    {
        return $this->account;
    }

    /**
     *
     * @return API_OAuth2_Token
     */
    public function renew()
    {
        $sql = 'UPDATE api_oauth_tokens SET oauth_token = :new_token
            WHERE oauth_token = :old_token';

        $new_token = self::generate_token();

        $params = array(
            ':new_token' => $new_token
            , ':old_token' => $this->get_value()
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->token = $new_token;

        return $this;
    }

    /**
     *
     * @return void
     */
    public function delete()
    {
        $sql = 'DELETE FROM api_oauth_tokens WHERE oauth_token = :oauth_token';

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':oauth_token' => $this->get_value()));
        $stmt->closeCursor();

        return;
    }

    /**
     *
     * @param appbox $appbox
     * @param type $oauth_token
     * @return API_OAuth2_Token
     */
    public static function load_by_oauth_token(appbox &$appbox, $oauth_token)
    {
        $sql = 'SELECT a.api_account_id
            FROM api_oauth_tokens a, api_accounts b
            WHERE a.oauth_token = :oauth_token
              AND a.api_account_id = b.api_account_id';

        $stmt = $appbox->get_connection()->prepare($sql);
        $params = array(":oauth_token" => $oauth_token);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new Exception_NotFound();

        $account = new API_OAuth2_Account($appbox, $row['api_account_id']);

        return new self($appbox, $account);
    }

    /**
     *
     * @param appbox $appbox
     * @param API_OAuth2_Account $account
     * @param string $scope
     * @return API_OAuth2_Token
     */
    public static function create(appbox &$appbox, API_OAuth2_Account &$account, $scope = null)
    {
        $sql = 'INSERT INTO api_oauth_tokens
            (oauth_token, session_id, api_account_id, expires, scope)
            VALUES (:token, null, :account_id, :expire, :scope)';

        $params = array(
            ':token'      => self::generate_token()
            , ':account_id' => $account->get_id()
            , ':expire'     => time() + 3600
            , ':scope'      => $scope
        );

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return new API_OAuth2_Token($appbox, $account);
    }

    /**
     *
     * @return string
     */
    public static function generate_token()
    {
        return md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
    }
}
