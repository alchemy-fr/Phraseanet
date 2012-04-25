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
class API_OAuth2_AuthCode
{
    protected $appbox;
    protected $code;
    protected $account;
    protected $account_id;
    protected $redirect_uri;
    protected $expires;
    protected $scope;

    public function __construct(appbox &$appbox, $code)
    {
        $this->appbox = $appbox;
        $this->code = $code;
        $sql = 'SELECT code, api_account_id, redirect_uri, UNIX_TIMESTAMP(expires) AS expires, scope
            FROM api_oauth_codes WHERE code = :code';

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':code' => $this->code));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new Exception_NotFound();

        $this->account_id = (int) $row['api_account_id'];
        $this->redirect_uri = $row['redirect_uri'];
        $this->expires = $row['expires'];
        $this->scope = $row['scope'];

        return $this;
    }

    public function get_code()
    {
        return $this->code;
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

    public function get_redirect_uri()
    {
        return $this->redirect_uri;
    }

    public function set_redirect_uri($redirect_uri)
    {
        $sql = 'UPDATE api_oauth_codes SET redirect_uri = :redirect_uri
            WHERE code = :code';

        $params = array(':redirect_uri' => $redirect_uri, ':code'         => $this->code);

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->redirect_uri = $redirect_uri;

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

    public function get_scope()
    {
        return $this->scope;
    }

    public function set_scope($scope)
    {
        $sql = 'UPDATE api_oauth_codes SET scope = :scope
            WHERE code = :code';

        $params = array(':scope' => $scope, ':code'  => $this->code);

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->scope = $scope;

        return $this;
    }

    public function delete()
    {
        $sql = 'DELETE FROM api_oauth_codes WHERE code = :code';

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':code' => $this->code));
        $stmt->closeCursor();

        return;
    }

    /**
     *
     * @param appbox $appbox
     * @param API_OAuth2_Account $account
     * @return array
     */
    public static function load_codes_by_account(appbox &$appbox, API_OAuth2_Account $account)
    {
        $sql = 'SELECT code FROM api_oauth_codes
            WHERE api_account_id = :account_id';

        $stmt = $appbox->get_connection()->prepare($sql);

        $params = array(":account_id" => $account->get_id());
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $codes = array();

        foreach ($rs as $row) {
            $codes[] = new API_OAuth2_AuthCode($appbox, $row['code']);
        }

        return $codes;
    }

    /**
     *
     * @param appbox $appbox
     * @param API_OAuth2_Account $account
     * @param type $code
     * @param int $expires
     * @return API_OAuth2_AuthCode
     */
    public static function create(appbox &$appbox, API_OAuth2_Account $account, $code, $expires)
    {

        $sql = 'INSERT INTO api_oauth_codes (code, api_account_id, expires)
            VALUES (:code, :account_id, FROM_UNIXTIME(:expires))';

        $stmt = $appbox->get_connection()->prepare($sql);

        $params = array(
            ":code"       => $code,
            ":account_id" => $account->get_id(),
            ":expires"    => $expires
        );
        $stmt->execute($params);
        $stmt->closeCursor();

        return new self($appbox, $code);
    }
}
