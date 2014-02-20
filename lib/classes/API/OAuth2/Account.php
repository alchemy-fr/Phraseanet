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

class API_OAuth2_Account
{
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

    public function __construct(Application $app, $account_id)
    {
        $this->app = $app;
        $this->id = (int) $account_id;
        $sql = 'SELECT api_account_id, usr_id, api_version, revoked
              , application_id, created
            FROM api_accounts
            WHERE api_account_id = :api_account_id';

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':api_account_id' => $this->id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->application_id = (int) $row['application_id'];
        $this->user = $app['manipulator.user']->getRepository()->find($row['usr_id']);

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
     * @return User
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

        $params = [
            ':revoked'   => ($boolean ? '1' : '0')
            , 'account_id' => $this->id
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
     * @return API_OAuth2_Token
     */
    public function get_token()
    {
        if (! $this->token) {
            try {
                $this->token = new API_OAuth2_Token($this->app['phraseanet.appbox'], $this);
            } catch (NotFoundHttpException $e) {
                $this->token = API_OAuth2_Token::create($this->app['phraseanet.appbox'], $this);
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
            $this->application = new API_OAuth2_Application($this->app, $this->application_id);

        return $this->application;
    }

    /**
     *
     * @return void
     */
    public function delete()
    {
        $this->get_token()->delete();

        foreach (API_OAuth2_AuthCode::load_codes_by_account($this->app, $this) as $code) {
            $code->delete();
        }
        foreach (API_OAuth2_RefreshToken::load_by_account($this->app, $this) as $token) {
            $token->delete();
        }

        $sql = 'DELETE FROM api_accounts WHERE api_account_id = :account_id';

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(['account_id' => $this->id]);
        $stmt->closeCursor();

        return;
    }

    public static function create(Application $app, User $user, API_OAuth2_Application $application)
    {
        $sql = 'INSERT INTO api_accounts
              (api_account_id, usr_id, revoked, api_version, application_id, created)
            VALUES (null, :usr_id, :revoked, :api_version, :application_id, :created)';

        $datetime = new Datetime();
        $params = [
            ':usr_id'         => $user->getId()
            , ':application_id' => $application->get_id()
            , ':api_version'    => API_OAuth2_Adapter::API_VERSION
            , ':revoked'        => 0
            , ':created'        => $datetime->format("Y-m-d H:i:s")
        ];

        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $account_id = $app['phraseanet.appbox']->get_connection()->lastInsertId();

        return new self($app, $account_id);
    }

    public static function load_with_user(Application $app, API_OAuth2_Application $application, User $user)
    {
        $sql = 'SELECT api_account_id FROM api_accounts
            WHERE usr_id = :usr_id AND application_id = :application_id';

        $params = [
            ":usr_id"         => $user->getId(),
            ":application_id" => $application->get_id()
        ];

        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (! $row) {
            throw new NotFoundHttpException('Account nof found.');
        }

        return new self($app, $row['api_account_id']);
    }
}
