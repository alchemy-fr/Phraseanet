<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Api;

use Alchemy\Phrasea\Application;

class Logger
{
    const DATABOXES_RESOURCE = 'databoxes';
    const RECORDS_RESOURCE = 'record';
    const BASKETS_RESOURCE = 'baskets';
    const FEEDS_RESOURCE = 'feeds';

    /**
     *
     * @var int
     */
    private $id;

    /**
     *
     * @var int
     */
    private $account_id;

    /**
     *
     * @var DateTime
     */
    private $date;

    /**
     *
     * @var int
     */
    private $status_code;

    /**
     *
     * @var string
     */
    private $format;

    /**
     *
     * @var string
     */
    private $resource;

    /**
     *
     * @var string
     */
    private $general;

    /**
     *
     * @var string
     */
    private $aspect;

    /**
     *
     * @var string
     */
    private $action;

    /**
     *
     * @var API_OAuth2_Account
     */
    private $account;

    /**
     *
     * @var Application
     */
    private $app;

    /**
     *
     * @param Application $app
     * @param integer     $log_id
     */
    public function __construct(Application $app, $log_id)
    {
        $this->app = $app;
        $this->id = (int) $log_id;

        $sql = '
      SELECT
        api_log_id,
        api_account_id,
        api_log_route,
        api_log_date,
        api_log_status_code,
        api_log_format,
        api_log_resource,
        api_log_general,
        api_log_aspect,
        api_log_action
      FROM
        api_logs
      WHERE
        api_log_id = :log_id';

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':log_id' => $this->id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->account_id = $row['api_account_id'];
        $this->account = new \API_OAuth2_Account($this->app, (int) $row['api_account_id']);
        $this->aspect = $row['api_log_aspect'];
        $this->date = new \DateTime($row['api_log_date']);
        $this->format = $row['api_log_format'];
        $this->general = $row['api_log_general'];
        $this->resource = $row['api_log_resource'];
        $this->status_code = (int) $row['api_log_status_code'];

        return $this;
    }

    public function get_account_id()
    {
        return $this->account_id;
    }

    public function set_account_id($account_id)
    {
        $this->account_id = $account_id;

        $sql = 'UPDATE api_log
            SET api_account_id = :account_id
            WHERE api_log_id = :log_id';

        $params = [
            ':api_account_id' => $this->account_id
            , ':log_id'         => $this->id
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    public function get_date()
    {
        return $this->date;
    }

    public function set_date(DateTime $date)
    {
        $this->date = $date;

        $sql = 'UPDATE api_log
            SET api_log_date = :date
            WHERE api_log_id = :log_id';

        $params = [
            ':date'   => $this->date->format("Y-m-d H:i:s")
            , ':log_id' => $this->id
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    public function get_status_code()
    {
        return $this->status_code;
    }

    public function set_status_code($status_code)
    {
        $this->status_code = (int) $status_code;

        $sql = 'UPDATE api_log
            SET api_log_status_code = :code
            WHERE api_log_id = :log_id';

        $params = [
            ':code'   => $this->status_code
            , ':log_id' => $this->id
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    public function get_format()
    {
        return $this->format;
    }

    public function set_format($format)
    {

        if ( ! in_array($format, ['json', 'jsonp', 'yaml', 'unknow']))
            throw new \Exception_InvalidArgument();

        $this->format = $format;

        $sql = 'UPDATE api_log
            SET api_log_format = :format
            WHERE api_log_id = :log_id';

        $params = [
            ':format' => $this->format
            , ':log_id' => $this->id
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    public function get_resource()
    {
        return $this->resource;
    }

    public function set_resource($resource)
    {
        if ( ! in_array($resource, [self::DATABOXES_RESOURCE, self::BASKETS_RESOURCE, self::FEEDS_RESOURCE, self::RECORDS_RESOURCE]))
            throw new \Exception_InvalidArgument();

        $this->resource = $resource;

        $sql = 'UPDATE api_log
            SET api_log_resource = :resource
            WHERE api_log_id = :log_id';

        $params = [
            ':resource' => $this->resource,
            ':log_id'    => $this->id,
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    public function get_general()
    {
        return $this->general;
    }

    public function set_general($general)
    {
        $this->general = $general;

        $sql = 'UPDATE api_log
            SET api_log_general = :general
            WHERE api_log_id = :log_id';

        $params = [
            ':general' => $this->general
            , ':log_id'  => $this->id
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    public function get_aspect()
    {
        return $this->aspect;
    }

    public function set_aspect($aspect)
    {
        $this->aspect = $aspect;

        $sql = 'UPDATE api_log
            SET api_log_aspect = :aspect
            WHERE api_log_id = :log_id';

        $params = [
            ':aspect' => $this->aspect
            , ':log_id' => $this->id
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    public function get_action()
    {
        return $this->action;
    }

    public function set_action($action)
    {
        $this->action = $action;

        $sql = 'UPDATE api_log
            SET api_log_action = :action
            WHERE api_log_id = :log_id';

        $params = [
            ':action' => $this->action
            , ':log_id' => $this->id
        ];

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    public function get_account()
    {
        return $this->account;
    }

    public static function create(Application $app, \API_OAuth2_Account $account, $route, $status_code, $format, $resource, $general = null, $aspect = null, $action = null)
    {
        $sql = '
      INSERT INTO
        api_logs (
          api_log_id,
          api_account_id,
          api_log_route,
          api_log_date,
          api_log_status_code,
          api_log_format,
          api_log_resource,
          api_log_general,
          api_log_aspect,
          api_log_action
        )
      VALUES (
        null,
        :account_id,
        :route,
        NOW(),
        :status_code,
        :format,
        :resource,
        :general,
        :aspect,
        :action
      )';

        $params = [
            ':account_id'  => $account->get_id(),
            ':route'       => $route,
            ':status_code' => $status_code,
            ':format'      => $format,
            ':resource'    => $resource,
            ':general'     => $general,
            ':aspect'      => $aspect,
            ':action'      => $action
        ];

        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $log_id = $app['phraseanet.appbox']->get_connection()->lastInsertId();

        return new self($app, $log_id);
    }
}
