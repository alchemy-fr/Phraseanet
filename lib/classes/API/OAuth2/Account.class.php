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
class API_OAuth2_Account
{
    /**
     *
     * @var appbox
     */
    protected $appbox;

    /**
     *
     * @var int
     */
    protected $id;

    /**
     *
     * @var User_Adapter
     */
    protected $user;

    /**
     *
     * @var API_OAuth2_Application
     */
    protected $application;

    /**
     *
     * @var int
     */
    protected $application_id;

    /**
     *
     * @var string
     */
    protected $api_version;

    /**
     *
     * @var boolean
     */
    protected $revoked;

    /**
     *
     * @var DateTime
     */
    protected $created_on;

    /**
     *
     * @var string
     */
    protected $token;

    /**
     * Constructor
     *
     * @param  appbox             $appbox
     * @param  int                $account_id
     * @return API_OAuth2_Account
     */
    public function __construct(appbox &$appbox, $account_id)
    {
        $this->appbox = $appbox;
        $this->id = (int) $account_id;
        $sql = 'SELECT api_account_id, usr_id, api_version, revoked
              , application_id, created
            FROM api_accounts
            WHERE api_account_id = :api_account_id';

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':api_account_id' => $this->id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->application_id = (int) $row['application_id'];
        $this->user = User_Adapter::getInstance($row['usr_id'], $this->appbox);

        $this->api_version = $row['api_version'];
        $this->revoked = ! ! $row['revoked'];
        $this->created_on = new DateTime($row['created']);

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
     * @return string
     */
    public function get_api_version()
    {
        return $this->api_version;
    }

    /**
     *
     * @return boolean
     */
    public function is_revoked()
    {
        return $this->revoked;
    }

    /**
     *
     * @param  boolean            $boolean
     * @return API_OAuth2_Account
     */
    public function set_revoked($boolean)
    {
        $this->revoked = ! ! $boolean;

        $sql = 'UPDATE api_accounts SET revoked = :revoked
            WHERE api_account_id = :account_id';

        $params = array(
            ':revoked'   => ($boolean ? '1' : '0')
            , 'account_id' => $this->id
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @return DateTime
     */
    public function get_created_on()
    {
        return $this->created_on;
    }

    /**
     *
     * @return API_OAuth2_Token
     */
    public function get_token()
    {
        if ( ! $this->token) {
            try {
                $this->token = new API_OAuth2_Token($this->appbox, $this);
            } catch (Exception_NotFound $e) {
                $this->token = API_OAuth2_Token::create($this->appbox, $this);
            }
        }

        return $this->token;
    }

    /**
     *
     * @return API_OAuth2_Application
     */
    public function get_application()
    {
        if ( ! $this->application)
            $this->application = new API_OAuth2_Application($this->appbox, $this->application_id);

        return $this->application;
    }

    /**
     *
     * @return void
     */
    public function delete()
    {
        $this->get_token()->delete();

        foreach (API_OAuth2_AuthCode::load_codes_by_account($this->appbox, $this) as $code) {
            $code->delete();
        }
        foreach (API_OAuth2_RefreshToken::load_by_account($this->appbox, $this) as $token) {
            $token->delete();
        }

        $sql = 'DELETE FROM api_accounts WHERE api_account_id = :account_id';

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array('account_id' => $this->id));
        $stmt->closeCursor();

        return;
    }

    /**
     *
     * @param  appbox                 $appbox
     * @param  User_Adapter           $user
     * @param  API_OAuth2_Application $application
     * @return API_OAuth2_Account
     */
    public static function create(appbox &$appbox, User_Adapter $user, API_OAuth2_Application $application)
    {
        $sql = 'INSERT INTO api_accounts
              (api_account_id, usr_id, revoked, api_version, application_id, created)
            VALUES (null, :usr_id, :revoked, :api_version, :application_id, :created)';

        $datetime = new Datetime();
        $params = array(
            ':usr_id'         => $user->get_id()
            , ':application_id' => $application->get_id()
            , ':api_version'    => API_OAuth2_Adapter::API_VERSION
            , ':revoked'        => 0
            , ':created'        => $datetime->format("Y-m-d H:i:s")
        );

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $account_id = $appbox->get_connection()->lastInsertId();

        return new self($appbox, $account_id);
    }

    /**
     *
     * @param  appbox                 $appbox
     * @param  API_OAuth2_Application $application
     * @param  User_Adapter           $user
     * @return API_OAuth2_Account
     */
    public static function load_with_user(appbox &$appbox, API_OAuth2_Application $application, User_Adapter $user)
    {
        $sql = 'SELECT api_account_id FROM api_accounts
            WHERE usr_id = :usr_id AND application_id = :application_id';

        $params = array(
            ":usr_id"         => $user->get_id(),
            ":application_id" => $application->get_id()
        );

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row) {
            throw new Exception_NotFound();
        }

        return new self($appbox, $row['api_account_id']);
    }
}
