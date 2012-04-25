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
 * @package     OAuth2 Connector
 *
 * @see         http://oauth.net/2/
 * @uses        http://code.google.com/p/oauth2-php/
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class API_OAuth2_RefreshToken
{
    protected $appbox;
    protected $token;
    protected $account_id;
    protected $account;
    protected $expires;
    protected $scope;

    public function __construct(appbox &$appbox, $token)
    {
        $this->appbox = $appbox;
        $this->token = $token;

        $sql = 'SELECT api_account_id, UNIX_TIMESTAMP(expires) AS expires, scope
            FROM api_oauth_refresh_tokens WHERE refresh_token = :token';

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':token' => $this->token));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->account_id = (int) $row['api_account_id'];
        $this->expires = $row['expires'];
        $this->scope = $row['scope'];

        return $this;
    }

    public function get_value()
    {
        return $this->token;
    }

    /**
     *
     * @return API_OAuth2_Account
     */
    public function get_account()
    {
        if ( ! $this->account)
            $this->account = new API_OAuth2_Account($this->appbox, $this->account_id);

        return $this->account;
    }

    /**
     *
     * @return int
     */
    public function get_expires()
    {
        return $this->expires;
    }

    public function get_scope()
    {
        return $this->scope;
    }

    public function delete()
    {
        $sql = 'DELETE FROM api_oauth_refresh_tokens
            WHERE refresh_token = :refresh_token';

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(":refresh_token" => $this->token));
        $stmt->closeCursor();

        return;
    }

    /**
     *
     * @param appbox $appbox
     * @param API_OAuth2_Account $account
     * @return array
     */
    public static function load_by_account(appbox &$appbox, API_OAuth2_Account $account)
    {
        $sql = 'SELECT refresh_token FROM api_oauth_refresh_tokens
            WHERE api_account_id = :account_id';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':account_id' => $account->get_id()));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $tokens = array();

        foreach ($rs as $row) {
            $tokens[] = new API_OAuth2_RefreshToken($appbox, $row['refresh_token']);
        }

        return $tokens;
    }

    /**
     *
     * @param appbox $appbox
     * @param API_OAuth2_Account $account
     * @param int $expires
     * @param type $refresh_token
     * @param type $scope
     * @return API_OAuth2_RefreshToken
     */
    public static function create(appbox &$appbox, API_OAuth2_Account $account, $expires, $refresh_token, $scope)
    {
        $sql = 'INSERT INTO api_oauth_refresh_tokens
              (refresh_token, api_account_id, expires, scope)
            VALUES (:refresh_token, :account_id, :expires, :scope)';

        $stmt = $appbox->get_connection()->prepare($sql);
        $params = array(
            ":refresh_token" => $refresh_token,
            ":account_id"    => $account->get_id(),
            ":expires"       => $expires,
            ":scope"         => $scope
        );
        $stmt->execute($params);
        $stmt->closeCursor();

        return new self($appbox, $refresh_token);
    }
}
