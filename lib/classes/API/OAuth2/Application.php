<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Alchemy\Phrasea\Model\Entities\User;

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
     * @var Application
     */
    protected $app;

    /**
     *
     * @var int
     */
    protected $id;

    /**
     *
     * @var User
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
     * @param  Application            $app
     * @param  int                    $application_id
     * @return API_OAuth2_Application
     */
    public function __construct(Application $app, $application_id)
    {
        $this->app = $app;
        $this->id = (int) $application_id;

        $sql = '
            SELECT
                application_id, creator, type, name, description, website
              , created_on, last_modified, client_id, client_secret, nonce
              , redirect_uri, activated, grant_password
            FROM api_applications
            WHERE application_id = :application_id';

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':application_id' => $this->id]);

        if (0 === $stmt->rowCount()) {
            throw new NotFoundHttpException(sprintf('Application with id %d not found', $this->id));
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $this->creator = ! $row['creator'] ? null : $this->app['manipulator.user']->getRepository()->find($row['creator']);
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
     * @return User
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
        if ( ! in_array($type, [self::DESKTOP_TYPE, self::WEB_TYPE]))
            throw new Exception_InvalidArgument();

        $this->type = $type;

        if ($this->type == self::DESKTOP_TYPE)
            $this->set_redirect_uri(self::NATIVE_APP_REDIRECT_URI);

        $sql = 'UPDATE api_applications SET type = :type, last_modified = NOW()
            WHERE application_id = :application_id';

        $params = [
            ':type'           => $this->type
            , ':application_id' => $this->id
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
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

        $params = [
            ':name'           => $this->name
            , ':application_id' => $this->id
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
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

        $params = [
            ':description'    => $this->description
            , ':application_id' => $this->id
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
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

        $params = [
            ':website'        => $this->website
            , ':application_id' => $this->id
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
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

        $params = [
            ':activated'      => $this->activated
            , ':application_id' => $this->id
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
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

        $params = [
            ':grant_password' => $this->grant_password
            , ':application_id' => $this->id
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
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

        $params = [
            ':client_id'      => $this->client_id
            , ':application_id' => $this->id
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
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

        $params = [
            ':client_secret'  => $this->client_secret
            , ':application_id' => $this->id
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
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

        $params = [
            ':redirect_uri'   => $this->redirect_uri
            , ':application_id' => $this->id
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @param  User       $user
     * @return API_OAuth2_Account
     */
    public function get_user_account(User $user)
    {
        $sql = 'SELECT api_account_id FROM api_accounts
      WHERE usr_id = :usr_id  AND application_id = :id';

        $params = [
            ':usr_id' => $user->getId()
            , ':id'     => $this->id
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new NotFoundHttpException('Application not found.');

        return new API_OAuth2_Account($this->app, $row['api_account_id']);
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

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':application_id' => $this->get_id()]);
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

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':application_id' => $this->get_id()]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $accounts = [];

        foreach ($rs as $row) {
            $accounts[] = new API_OAuth2_Account($this->app, $row['api_account_id']);
        }

        return $accounts;
    }

    /**
     *
     * @param  Application            $app
     * @param  User           $user
     * @param  type                   $name
     * @return API_OAuth2_Application
     */
    public static function create(Application $app, User $user = null, $name)
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

        $params = [
            ':usr_id'         => $user ? $user->getId() : null,
            ':name'           => $name,
            ':client_id'      => $client_token,
            ':client_secret'  => $client_secret,
            ':nonce'          => $nonce,
            ':activated'      => 1,
            ':grant_password' => 0
        ];

        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $application_id = $app['phraseanet.appbox']->get_connection()->lastInsertId();

        $application = new self($app, $application_id);

        if ($user) {
            API_OAuth2_Account::create($app, $user, $application);
        }

        return $application;
    }

    /**
     *
     * @param  Application            $app
     * @param  type                   $client_id
     * @return API_OAuth2_Application
     */
    public static function load_from_client_id(Application $app, $client_id)
    {
        $sql = 'SELECT application_id FROM api_applications
              WHERE client_id = :client_id';

        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':client_id' => $client_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new NotFoundHttpException('Client not found.');

        return new self($app, $row['application_id']);
    }

    public static function load_dev_app_by_user(Application $app, User $user)
    {
        $sql = 'SELECT a.application_id
        FROM api_applications a, api_accounts b
        WHERE a.creator = :usr_id AND a.application_id = b.application_id';

        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => $user->getId()]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $apps = [];
        foreach ($rs as $row) {
            $apps[] = new API_OAuth2_Application($app, $row['application_id']);
        }

        return $apps;
    }

    public static function load_app_by_user(Application $app, User $user)
    {
        $sql = 'SELECT a.application_id
        FROM api_accounts a, api_applications c
        WHERE usr_id = :usr_id AND c.application_id = a.application_id';

        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => $user->getId()]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $apps = [];
        foreach ($rs as $row) {
            $apps[] = new API_OAuth2_Application($app, $row['application_id']);
        }

        return $apps;
    }

    public static function load_authorized_app_by_user(Application $app, User $user)
    {
        $sql = '
        SELECT a.application_id
        FROM api_accounts a, api_applications c
        WHERE usr_id = :usr_id AND c.application_id = a.application_id
        AND revoked = 0';

        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => $user->getId()]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $apps = [];
        foreach ($rs as $row) {
            $apps[] = new API_OAuth2_Application($app, $row['application_id']);
        }

        return $apps;
    }
}
