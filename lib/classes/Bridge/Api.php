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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;

class Bridge_Api
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
     * @var DateTime
     */
    protected $disable_time;

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
     * @var Bridge_Api_Interface
     */
    protected $connector;

    /**
     *
     * @param  Application $app
     * @param  int         $id
     * @return Bridge_Api
     */
    public function __construct(Application $app, $id)
    {
        $this->app = $app;
        $this->id = (int) $id;

        $sql = 'SELECT id, name, disable_time, created_on, updated_on
            FROM bridge_apis WHERE id = :id';
        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':id' => $this->id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new Bridge_Exception_ApiNotFound('Api Not Found');

        $this->connector = self::get_connector_by_name($this->app, $row['name']);
        $this->disable_time = $row['disable_time'] ? new DateTime($row['disable_time']) : null;
        $this->updated_on = new DateTime($row['updated_on']);
        $this->created_on = new DateTime($row['created_on']);

        return $this;
    }

    /**
     *
     * @return Bridge_Api_Interface
     */
    public function get_connector()
    {
        return $this->connector;
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
     * @param null|DateTime $checkDate
     * @return bool
     */
    public function is_disabled(DateTime $checkDate = null)
    {
        if ($this->disable_time === null) {
            return false;
        }

        $date_obj = $checkDate ?: new DateTime();
        if ($date_obj > $this->disable_time) {
            $this->enable();

            return false;
        }

        return true;
    }

    /**
     *
     * @return Bridge_Api
     */
    public function enable()
    {
        $this->disable_time = null;

        return $this->update_disable_time(null);
    }

    /**
     *
     * @return Bridge_Api
     */
    public function disable(DateTime $date_end)
    {
        $this->disable_time = $date_end;

        return $this->update_disable_time($date_end->format(DATE_ISO8601));
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
     * @param  string $type
     * @param  int    $offset_start
     * @param  int    $quantity
     * @return array
     */
    public function list_elements($type, $offset_start = 0, $quantity = 10)
    {
        $action = function (Bridge_Api $obj) use ($type, $offset_start, $quantity) {
                return $obj->get_connector()->list_elements($type, $offset_start, $quantity);
            };

        return $this->execute_action($action);
    }

    /**
     *
     * @param  string $type
     * @param  int    $offset_start
     * @param  int    $quantity
     * @return array
     */
    public function list_containers($type, $offset_start = 0, $quantity = 10)
    {
        $action = function (Bridge_Api $obj) use ($type, $offset_start, $quantity) {
                return $obj->get_connector()->list_containers($type, $offset_start, $quantity);
            };

        return $this->execute_action($action);
    }

    /**
     *
     * @param  string               $object
     * @param  string               $object_id
     * @param  array                $datas
     * @return Bridge_Api_Interface
     */
    public function update_element($object, $object_id, Array $datas)
    {
        $action = function (Bridge_Api $obj) use ($object, $object_id, $datas) {
                return $obj->get_connector()->update_element($object, $object_id, $datas);
            };

        return $this->execute_action($action);
    }

    /**
     *
     * @param  string                        $container_type
     * @param  Request                       $request
     * @return Bridge_Api_ContainerInterface
     */
    public function create_container($container_type, Request $request)
    {
        $action = function (Bridge_Api $obj) use ($container_type, $request) {
                return $obj->get_connector()->create_container($container_type, $request);
            };

        return $this->execute_action($action);
    }

    /**
     *
     * @param  string                        $element_type
     * @param  string                        $element_id
     * @param  string                        $destination
     * @param  string                        $container_id
     * @return Bridge_Api_ContainerInterface
     */
    public function add_element_to_container($element_type, $element_id, $destination, $container_id)
    {
        $action = function (Bridge_Api $obj) use ($element_type, $element_id, $destination, $container_id) {
                return $obj->get_connector()->add_element_to_container($element_type, $element_id, $destination, $container_id);
            };

        return $this->execute_action($action);
    }

    /**
     *
     * @param  string $object
     * @param  string $object_id
     * @return Void
     */
    public function delete_object($object, $object_id)
    {
        $action = function (Bridge_Api $obj) use ($object, $object_id) {
                return $obj->get_connector()->delete_object($object, $object_id);
            };

        return $this->execute_action($action);
    }

    /**
     *
     * @return boolean
     */
    public function acceptable_records()
    {
        $action = function (Bridge_Api $obj) {
                return $obj->get_connector()->acceptable_records();
            };

        return $this->execute_action($action);
    }

    /**
     *
     * @param  type                        $element_id
     * @param  type                        $type
     * @return Bridge_Api_ElementInterface
     */
    public function get_element_from_id($element_id, $type)
    {
        $action = function (Bridge_Api $obj) use ($element_id, $type) {
                return $obj->get_connector()->get_element_from_id($element_id, $type);
            };

        return $this->execute_action($action);
    }

    /**
     *
     * @param  int                           $element_id
     * @param  string                        $type
     * @return Bridge_Api_ContainerInterface
     */
    public function get_container_from_id($element_id, $type)
    {
        $action = function (Bridge_Api $obj) use ($element_id, $type) {
                return $obj->get_connector()->get_container_from_id($element_id, $type);
            };

        return $this->execute_action($action);
    }

    /**
     *
     * @return Array
     */
    public function get_category_list()
    {
        $action = function (Bridge_Api $obj) {
                return $obj->get_connector()->get_category_list();
            };

        return $this->execute_action($action);
    }

    /**
     *
     * @param Bridge_Element $element
     *
     * @return string
     */
    public function get_element_status(Bridge_Element $element)
    {
        $action = function (Bridge_Api $obj) use ($element) {
                return $obj->get_connector()->get_element_status($element);
            };

        return $this->execute_action($action);
    }

    /**
     *
     * @param  string $status
     * @return string
     */
    public function map_connector_to_element_status($status)
    {
        $action = function (Bridge_Api $obj) use ($status) {
                return $obj->get_connector()->map_connector_to_element_status($status);
            };

        return $this->execute_action($action);
    }

    /**
     *
     * @param  string $connector_status
     * @return string
     */
    public function get_error_message_from_status($connector_status)
    {
        $action = function (Bridge_Api $obj) use ($connector_status) {
                return $obj->get_connector()->get_error_message_from_status($connector_status);
            };

        return $this->execute_action($action);
    }

    /**
     *
     * @param  record_adapter $record
     * @param  array          $options specific option, regarding the connector
     * @return string         The distant_id of the created element
     */
    public function upload(record_adapter $record, array $options = [])
    {
        $action = function (Bridge_Api $obj) use ($record, $options) {
                return $obj->get_connector()->upload($record, $options);
            };

        return $this->execute_action($action);
    }

    /**
     *
     * @return Void
     */
    public function delete()
    {
        do {
            $accounts = Bridge_Account::get_accounts_by_api($this->app, $this);
            foreach ($accounts as $account) {
                $account->delete();
            }
        } while (count($accounts) > 0);

        $sql = 'DELETE FROM bridge_apis WHERE id = :id';

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':id' => $this->id]);
        $stmt->closeCursor();

        return;
    }

    /**
     *
     * @return Bridge_Api
     */
    protected function update_disable_time($value)
    {
        $this->updated_on = new DateTime();

        $sql = 'UPDATE bridge_apis SET disable_time = :time, updated_on = :update
            WHERE id = :id';

        $params = [
            ':time'   => $value
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
     * @param  Closure $action
     * @return mixed
     */
    protected function execute_action(Closure $action)
    {
        if ($this->is_disabled())
            throw new Bridge_Exception_ApiDisabled($this);

        $n = 0;
        do {
            $e = null;
            try {
                $ret = $action($this);

                return $ret;
            } catch (\Exception $e) {
                $this->get_connector()->handle_Exception($e);

                if ($e instanceof Bridge_Exception_ActionAuthNeedReconnect) {
                    $this->get_connector()->reconnect();
                } else {
                    throw $e;
                }

                if ($n >= 2) {
                    throw new Bridge_Exception_ApiConnectorRequestFailed('Request failed');
                }
            }
            $n ++;
        } while ($n <= 2 && $e instanceof Bridge_Exception_ActionAuthNeedReconnect);

        return null;
    }

    /**
     * @param  UrlGenerator $generator
     * @param  string       $api_name
     * @return string
     */
    public static function generate_callback_url(UrlGenerator $generator, $api_name)
    {
        return $generator->generate('prod_bridge_callback', ['api_name' => strtolower($api_name)], UrlGenerator::ABSOLUTE_URL);
    }

    /**
     * @param  UrlGenerator $generator
     * @param  string       $api_name
     * @return string
     */
    public static function generate_login_url(UrlGenerator $generator, $api_name)
    {
        return $generator->generate('prod_bridge_login', ['api_name' => strtolower($api_name)], UrlGenerator::ABSOLUTE_URL);
    }

    /**
     *
     * @param  Application          $app
     * @param  string               $name
     * @return Bridge_Api_Interface
     */
    public static function get_connector_by_name(Application $app, $name)
    {
        $name = ucfirst(strtolower($name));
        $classname = 'Bridge_Api_' . $name;

        if ( ! class_exists($classname)) {
            throw new Bridge_Exception_ApiConnectorNotFound($name . ' connector not found');
        }

        $auth_classname = 'Bridge_Api_Auth_' . $classname::AUTH_TYPE;
        $auth = new $auth_classname;

        return new $classname($app['url_generator'], $app['conf'], $auth, $app['translator']);
    }

    public static function get_by_api_name(Application $app, $name)
    {
        $name = strtolower($name);

        $sql = 'SELECT id FROM bridge_apis WHERE name = :name';
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':name' => $name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new Bridge_Exception_ApiNotFound('Unknown api name ' . $name);

        return new self($app, $row['id']);
    }

    /**
     *
     * @param  Application $app
     * @return Bridge_Api
     */
    public static function get_availables(Application $app)
    {
        $sql = 'SELECT id FROM bridge_apis';
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $results = [];

        foreach ($rs as $row) {
            try {
                $results[] = new Bridge_Api($app, $row['id']);
            } catch (\Exception $e) {

            }
        }

        return $results;
    }

    public static function create(Application $app, $name)
    {
        $connection = $app->getApplicationBox()->get_connection();

        $statement = $connection->prepare('INSERT INTO bridge_apis (name, disable, disable_time, created_on, updated_on) VALUES (:name, 0, null, :now, :now)');
        $statement->bindValue('name', strtolower($name));
        $statement->bindValue('now', new DateTime('now', new DateTimeZone('UTC')), 'datetime');
        $statement->execute();
        $statement->closeCursor();

        $api_id = $connection->lastInsertId();

        return new self($app, $api_id);
    }
}
