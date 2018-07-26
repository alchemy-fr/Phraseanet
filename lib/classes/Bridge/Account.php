<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\User;

class Bridge_Account
{
    /**
     *
     * @var int
     */
    protected $id;

    /**
     *
     * @var Application
     */
    protected $app;

    /**
     *
     * @var Bridge_Api
     */
    protected $api;

    /**
     *
     * @var string
     */
    protected $dist_id;

    /**
     *
     * @var User
     */
    protected $user;

    /**
     *
     * @var string
     */
    protected $name;

    /**
     *
     * @var Bridge_AccountSettings
     */
    protected $settings;

    /**
     *
     * @var DateTime
     */
    protected $created_on;

    /**
     *
     * @var DateTime
     */
    protected $updated_on;

    /**
     *
     * @param  Application    $app
     * @param  Bridge_Api     $api
     * @param  int            $id
     * @return Bridge_Account
     */
    public function __construct(Application $app, Bridge_Api $api, $id)
    {
        $this->id = (int) $id;
        $this->app = $app;
        $this->api = $api;

        $this->api->get_connector()->set_auth_settings($this->get_settings());

        $sql = 'SELECT id, dist_id, usr_id, name, created_on, updated_on
            FROM bridge_accounts WHERE id = :id';

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':id' => $this->id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new Bridge_Exception_AccountNotFound('Account Not Found');

        $this->dist_id = $row['dist_id'];
        $this->user = $this->app['repo.users']->find($row['usr_id']);
        $this->name = $row['name'];
        $this->updated_on = new DateTime($row['updated_on']);
        $this->created_on = new DateTime($row['created_on']);

        return $this;
    }

    /**
     *
     * @return Bridge_AccountSettings
     */
    public function get_settings()
    {
        if ( ! $this->settings)
            $this->settings = new Bridge_AccountSettings($this->app['phraseanet.appbox'], $this);

        return $this->settings;
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
     * @return Bridge_Api
     */
    public function get_api()
    {
        return $this->api;
    }

    /**
     *
     * @return string
     */
    public function get_dist_id()
    {
        return $this->dist_id;
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
    public function get_name()
    {
        return $this->name;
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
    public function get_updated_on()
    {
        return $this->updated_on;
    }

    /**
     *
     * @param  string         $name
     * @return Bridge_Account
     */
    public function set_name($name)
    {
        $this->name = $name;
        $this->updated_on = new DateTime();

        $sql = 'UPDATE bridge_accounts
            SET name = :name, updated_on = :update WHERE id = :id';

        $params = [
            ':name'   => $this->name
            , ':id'     => $this->id
            , ':update' => $this->updated_on->format(DATE_ISO8601)
        ];

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @return Void
     */
    public function delete()
    {
        do {
            $elements = Bridge_Element::get_elements_by_account($this->app, $this);
            foreach ($elements as $element) {
                $element->delete();
            }
        } while (count($elements) > 0);

        $sql = 'DELETE FROM bridge_accounts WHERE id = :id';

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':id' => $this->id]);
        $stmt->closeCursor();

        return;
    }

    /**
     *
     * @param  Application    $app
     * @param  integer        $account_id
     * @return Bridge_Account
     */
    public static function load_account(Application $app, $account_id)
    {
        $sql = 'SELECT id, api_id FROM bridge_accounts WHERE id = :account_id';

        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':account_id' => $account_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new Bridge_Exception_AccountNotFound('Account Not Found');

        $api = new Bridge_Api($app, $row['api_id']);
        $api->get_connector()->set_locale($app['locale']);

        return new self($app, $api, $row['id']);
    }

    /**
     *
     * @param  Application    $app
     * @param  Bridge_Api     $api
     * @param  User           $user
     * @param  string         $distant_id
     * @return Bridge_Account
     */
    public static function load_account_from_distant_id(Application $app, Bridge_Api $api, User $user, $distant_id)
    {
        $sql = 'SELECT id FROM bridge_accounts
            WHERE api_id = :api_id AND usr_id = :usr_id AND dist_id = :dist_id';

        $params = [
            ':api_id'  => $api->get_id()
            , ':usr_id'  => $user->getId()
            , ':dist_id' => $distant_id
        ];

        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new Bridge_Exception_AccountNotFound();

        return new Bridge_Account($app, $api, $row['id']);
    }

    /**
     *
     * @param  Application    $app
     * @param  Bridge_Api     $api
     * @param  int            $quantity
     * @return Bridge_Account
     */
    public static function get_accounts_by_api(Application $app, Bridge_Api $api, $quantity = 50)
    {
        $sql = 'SELECT id FROM bridge_accounts WHERE api_id = :api_id
            LIMIT 0,' . (int) $quantity;

        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':api_id' => $api->get_id()]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $results = [];

        foreach ($rs as $row) {
            $results[] = new Bridge_Account($app, $api, $row['id']);
        }

        return $results;
    }

    /**
     *
     * @param  Application    $app
     * @param  User           $user
     * @return Bridge_Account
     */
    public static function get_accounts_by_user(Application $app, User $user)
    {
        $sql = 'SELECT id, api_id FROM bridge_accounts WHERE usr_id = :usr_id';

        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => $user->getId()]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $results = [];
        $apis = [];

        foreach ($rs as $row) {
            $api_id = $row['api_id'];
            if ( ! isset($apis[$api_id])) {
                try {
                    $apis[$api_id] = new Bridge_Api($app, $api_id);
                } catch (\Exception $e) {
                    continue;
                }
            }
            $results[] = new Bridge_Account($app, $apis[$api_id], $row['id']);
        }

        return $results;
    }

    /**
     *
     * @param Application $app
     * @param Bridge_Api  $api
     * @param User        $user
     * @param string      $dist_id
     * @param string      $name
     *
     * @return Bridge_Account
     */
    public static function create(Application $app, Bridge_Api $api, User $user, $dist_id, $name)
    {
        $sql = 'INSERT INTO bridge_accounts
            (id, api_id, dist_id, usr_id, name, created_on, updated_on)
            VALUES (null, :api_id, :dist_id, :usr_id, :name, NOW(), NOW())';

        $params = [
            ':api_id'  => $api->get_id()
            , ':dist_id' => $dist_id
            , ':usr_id'  => $user->getId()
            , ':name'    => $name
        ];

        $connection = $app->getApplicationBox()->get_connection();
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $account_id = $connection->lastInsertId();

        return new self($app, $api, $account_id);
    }
}
