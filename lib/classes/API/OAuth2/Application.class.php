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
class API_OAuth2_Application
{
    /**
     * constant for desktop application
     */
    const DESKTOP_TYPE = 'desktop';
    /**
     * constant for web application
     */
    const WEB_TYPE = 'web';
    /**
     * Uniform Resource Name
     */
    const NATIVE_APP_REDIRECT_URI = "urn:ietf:wg:oauth:2.0:oob";

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
    protected $creator;

    /**
     *
     * @var string
     */
    protected $type;

    /**
     *
     * @var string
     */
    protected $name;

    /**
     *
     * @var string
     */
    protected $nonce;

    /**
     *
     * @var string
     */
    protected $description;

    /**
     *
     * @var string
     */
    protected $website;

    /**
     *
     * @var DateTime
     */
    protected $created_on;

    /**
     *
     * @var DateTime
     */
    protected $last_modified;

    /**
     *
     * @var string
     */
    protected $client_id;

    /**
     *
     * @var string
     */
    protected $client_secret;

    /**
     *
     * @var string
     */
    protected $redirect_uri;

    /**
     *
     * @var boolean
     */
    protected $activated;

    /**
     *
     * @var boolean
     */
    protected $grant_password;

    /**
     *
     * @param  appbox                 $appbox
     * @param  int                    $application_id
     * @return API_OAuth2_Application
     */
    public function __construct(appbox &$appbox, $application_id)
    {
        $this->appbox = $appbox;
        $this->id = (int) $application_id;

        $sql = '
            SELECT
                application_id, creator, type, name, description, website
              , created_on, last_modified, client_id, client_secret, nonce
              , redirect_uri, activated, grant_password
            FROM api_applications
            WHERE application_id = :application_id';

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':application_id' => $this->id));

        if (0 === $stmt->rowCount()) {
            throw new \Exception_NotFound(sprintf('Application with id %d not found', $this->id));
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $this->creator = ! $row['creator'] ? null : User_Adapter::getInstance($row['creator'], $this->appbox);
        $this->type = $row['type'];
        $this->name = $row['name'];
        $this->description = $row['description'];
        $this->website = $row['website'];
        $this->created_on = new DateTime($row['created_on']);
        $this->last_modified = new DateTime($row['last_modified']);
        $this->client_id = $row['client_id'];
        $this->client_secret = $row['client_secret'];
        $this->redirect_uri = $row['redirect_uri'];
        $this->nonce = $row['nonce'];
        $this->activated = ! ! $row['activated'];
        $this->grant_password = ! ! $row['grant_password'];

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
    public function get_creator()
    {
        return $this->creator;
    }

    /**
     *
     * @return string
     */
    public function get_type()
    {
        return $this->type;
    }

    /**
     *
     * @return string
     */
    public function get_nonce()
    {
        return $this->nonce;
    }

    /**
     *
     * @param  string                 $type
     * @return API_OAuth2_Application
     */
    public function set_type($type)
    {
        if ( ! in_array($type, array(self::DESKTOP_TYPE, self::WEB_TYPE)))
            throw new Exception_InvalidArgument();

        $this->type = $type;

        if ($this->type == self::DESKTOP_TYPE)
            $this->set_redirect_uri(self::NATIVE_APP_REDIRECT_URI);

        $sql = 'UPDATE api_applications SET type = :type, last_modified = NOW()
            WHERE application_id = :application_id';

        $params = array(
            ':type'           => $this->type
            , ':application_id' => $this->id
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     *
     * @param  string                 $name
     * @return API_OAuth2_Application
     */
    public function set_name($name)
    {
        $this->name = $name;

        $sql = 'UPDATE api_applications SET name = :name, last_modified = NOW()
            WHERE application_id = :application_id';

        $params = array(
            ':name'           => $this->name
            , ':application_id' => $this->id
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return $this->description;
    }

    /**
     *
     * @param  string                 $description
     * @return API_OAuth2_Application
     */
    public function set_description($description)
    {
        $this->description = $description;

        $sql = 'UPDATE api_applications
            SET description = :description, last_modified = NOW()
            WHERE application_id = :application_id';

        $params = array(
            ':description'    => $this->description
            , ':application_id' => $this->id
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_website()
    {
        return $this->website;
    }

    /**
     *
     * @param  string                 $website
     * @return API_OAuth2_Application
     */
    public function set_website($website)
    {
        $this->website = $website;

        $sql = 'UPDATE api_applications
            SET website = :website, last_modified = NOW()
            WHERE application_id = :application_id';

        $params = array(
            ':website'        => $this->website
            , ':application_id' => $this->id
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    /**
     * Tell wether application is activated
     * @return boolean
     */
    public function is_activated()
    {
        return $this->activated;
    }

    /**
     *
     * @param  boolean                $activated
     * @return API_OAuth2_Application
     */
    public function set_activated($activated)
    {
        $this->activated = $activated;

        $sql = 'UPDATE api_applications
            SET activated = :activated, last_modified = NOW()
            WHERE application_id = :application_id';

        $params = array(
            ':activated'      => $this->activated
            , ':application_id' => $this->id
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    /**
     * Tell wether application authorize password grant type
     * @return boolean
     */
    public function is_password_granted()
    {
        return $this->grant_password;
    }

    /**
     *
     * @param  boolean                $grant
     * @return API_OAuth2_Application
     */
    public function set_grant_password($grant)
    {
        $this->grant_password = ! ! $grant;

        $sql = 'UPDATE api_applications
            SET grant_password = :grant_password, last_modified = NOW()
            WHERE application_id = :application_id';

        $params = array(
            ':grant_password' => $this->grant_password
            , ':application_id' => $this->id
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
     * @return DateTime
     */
    public function get_last_modified()
    {
        return $this->last_modified;
    }

    /**
     *
     * @return int
     */
    public function get_client_id()
    {
        return $this->client_id;
    }

    /**
     *
     * @param  int                    $client_id
     * @return API_OAuth2_Application
     */
    public function set_client_id($client_id)
    {
        $this->client_id = $client_id;

        $sql = 'UPDATE api_applications
            SET client_id = :client_id, last_modified = NOW()
            WHERE application_id = :application_id';

        $params = array(
            ':client_id'      => $this->client_id
            , ':application_id' => $this->id
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_client_secret()
    {
        return $this->client_secret;
    }

    /**
     *
     * @param  string                 $client_secret
     * @return API_OAuth2_Application
     */
    public function set_client_secret($client_secret)
    {
        $this->client_secret = $client_secret;

        $sql = 'UPDATE api_applications
            SET client_secret = :client_secret, last_modified = NOW()
            WHERE application_id = :application_id';

        $params = array(
            ':client_secret'  => $this->client_secret
            , ':application_id' => $this->id
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_redirect_uri()
    {
        return $this->redirect_uri;
    }

    /**
     *
     * @param  string                 $redirect_uri
     * @return API_OAuth2_Application
     */
    public function set_redirect_uri($redirect_uri)
    {
        $this->redirect_uri = $redirect_uri;
        $sql = 'UPDATE api_applications
            SET redirect_uri = :redirect_uri, last_modified = NOW()
            WHERE application_id = :application_id';

        $params = array(
            ':redirect_uri'   => $this->redirect_uri
            , ':application_id' => $this->id
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @param  User_Adapter       $user
     * @return API_OAuth2_Account
     */
    public function get_user_account(user_adapter $user)
    {
        $sql = 'SELECT api_account_id FROM api_accounts
      WHERE usr_id = :usr_id  AND application_id = :id';

        $params = array(
            ':usr_id' => $user->get_id()
            , ':id'     => $this->id
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new Exception_NotFound();

        return new API_OAuth2_Account($this->appbox, $row['api_account_id']);
    }

    /**
     *
     * @return void
     */
    public function delete()
    {
        foreach ($this->get_related_accounts() as $account) {
            $account->delete();
        }

        $sql = 'DELETE FROM api_applications
            WHERE application_id = :application_id';

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':application_id' => $this->get_id()));
        $stmt->closeCursor();

        return;
    }

    /**
     *
     * @return array
     */
    protected function get_related_accounts()
    {
        $sql = 'SELECT api_account_id FROM api_accounts
            WHERE application_id = :application_id';

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':application_id' => $this->get_id()));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $accounts = array();

        foreach ($rs as $row) {
            $accounts[] = new API_OAuth2_Account($this->appbox, $row['api_account_id']);
        }

        return $accounts;
    }

    /**
     *
     * @param  appbox                 $appbox
     * @param  User_Adapter           $user
     * @param  type                   $name
     * @return API_OAuth2_Application
     */
    public static function create(appbox &$appbox, User_Adapter $user = null, $name)
    {
        $sql = '
            INSERT INTO api_applications (
                application_id, creator, created_on, name, last_modified,
                nonce, client_id, client_secret, activated, grant_password
            )
            VALUES (
                null, :usr_id, NOW(), :name, NOW(), :nonce, :client_id,
                :client_secret, :activated, :grant_password
            )';

        $nonce = random::generatePassword(6);
        $client_secret = API_OAuth2_Token::generate_token();
        $client_token = API_OAuth2_Token::generate_token();

        $params = array(
            ':usr_id'         => $user ? $user->get_id() : null,
            ':name'           => $name,
            ':client_id'      => $client_token,
            ':client_secret'  => $client_secret,
            ':nonce'          => $nonce,
            ':activated'      => 1,
            ':grant_password' => 0
        );

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $application_id = $appbox->get_connection()->lastInsertId();

        $application = new self($appbox, $application_id);

        if ($user) {
            API_OAuth2_Account::create($appbox, $user, $application);
        }

        return $application;
    }

    /**
     *
     * @param  appbox                 $appbox
     * @param  type                   $client_id
     * @return API_OAuth2_Application
     */
    public static function load_from_client_id(appbox &$appbox, $client_id)
    {
        $sql = 'SELECT application_id FROM api_applications
              WHERE client_id = :client_id';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':client_id' => $client_id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new Exception_NotFound();

        return new self($appbox, $row['application_id']);
    }

    /**
     *
     * @param  appbox       $appbox
     * @param  User_Adapter $user
     * @return array
     */
    public static function load_dev_app_by_user(appbox &$appbox, User_Adapter $user)
    {
        $sql = 'SELECT a.application_id
        FROM api_applications a, api_accounts b
        WHERE a.creator = :usr_id AND a.application_id = b.application_id';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $user->get_id()));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $apps = array();
        foreach ($rs as $row) {
            $apps[] = new API_OAuth2_Application($appbox, $row['application_id']);
        }

        return $apps;
    }

    /**
     *
     * @param  appbox                 $appbox
     * @param  user_adapter           $user
     * @return API_OAuth2_Application
     */
    public static function load_app_by_user(appbox $appbox, user_adapter $user)
    {
        $sql = 'SELECT a.application_id
        FROM api_accounts a, api_applications c
        WHERE usr_id = :usr_id AND c.application_id = a.application_id';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $user->get_id()));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $apps = array();
        foreach ($rs as $row) {
            $apps[] = new API_OAuth2_Application($appbox, $row['application_id']);
        }

        return $apps;
    }

    public static function load_authorized_app_by_user(appbox $appbox, user_adapter $user)
    {
        $sql = '
        SELECT a.application_id
        FROM api_accounts a, api_applications c
        WHERE usr_id = :usr_id AND c.application_id = a.application_id
        AND revoked = 0';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $user->get_id()));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $apps = array();
        foreach ($rs as $row) {
            $apps[] = new API_OAuth2_Application($appbox, $row['application_id']);
        }

        return $apps;
    }
}
