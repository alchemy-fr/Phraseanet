<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class API_V1_Log
{
    const DATABOXES_RESSOURCE = 'databoxes';
    const RECORDS_RESSOURCE = 'record';
    const BASKETS_RESSOURCE = 'baskets';
    const FEEDS_RESSOURCE = 'feeds';

    /**
     *
     * @var int
     */
    protected $id;

    /**
     *
     * @var int
     */
    protected $account_id;

    /**
     *
     * @var DateTime
     */
    protected $date;

    /**
     *
     * @var int
     */
    protected $status_code;

    /**
     *
     * @var string
     */
    protected $format;

    /**
     *
     * @var string
     */
    protected $ressource;

    /**
     *
     * @var string
     */
    protected $general;

    /**
     *
     * @var string
     */
    protected $aspect;

    /**
     *
     * @var string
     */
    protected $action;

    /**
     *
     * @var API_OAuth2_Account
     */
    protected $account;

    /**
     *
     * @var appbox
     */
    protected $appbox;

    /**
     *
     * @param appbox $appbox
     * @param Request $request
     * @param API_OAuth2_Account $account
     */
    public function __construct(appbox &$appbox, $log_id)
    {
        $this->appbox = $appbox;
        $this->id = (int) $log_id;

        $sql = '
      SELECT
        api_log_id,
        api_account_id,
        api_log_route,
        api_log_date,
        api_log_status_code,
        api_log_format,
        api_log_ressource,
        api_log_general,
        api_log_aspect,
        api_log_action
      FROM
        api_logs
      WHERE
        api_log_id = :log_id';

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':log_id' => $this->id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->account_id = $row['api_account_id'];
        $this->account = new API_OAuth2_Account($this->appbox, (int) $row['api_account_id']);
        $this->aspect = $row['api_log_aspect'];
        $this->date = new DateTime($row['api_log_date']);
        $this->format = $row['api_log_format'];
        $this->general = $row['api_log_general'];
        $this->ressource = $row['api_log_ressource'];
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

        $params = array(
            ':api_account_id' => $this->account_id
            , ':log_id'         => $this->id
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
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

        $params = array(
            ':date'   => $this->date->format("Y-m-d H:i:s")
            , ':log_id' => $this->id
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
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

        $params = array(
            ':code'   => $this->status_code
            , ':log_id' => $this->id
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
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

        if ( ! in_array($format, array('json', 'jsonp', 'yaml', 'unknow')))
            throw new Exception_InvalidArgument();

        $this->format = $format;

        $sql = 'UPDATE api_log
            SET api_log_format = :format
            WHERE api_log_id = :log_id';

        $params = array(
            ':format' => $this->format
            , ':log_id' => $this->id
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    public function get_ressource()
    {
        return $this->ressource;
    }

    public function set_ressource($ressource)
    {
        if ( ! in_array($format, array(self::DATABOXES_RESSOURCE, self::BASKETS_RESSOURCE, self::FEEDS_RESSOURCE, self::RECORDS_RESSOURCE)))
            throw new Exception_InvalidArgument();

        $this->ressource = $ressource;

        $sql = 'UPDATE api_log
            SET api_log_ressource = :ressource
            WHERE api_log_id = :log_id';

        $params = array(
            ':ressource' => $this->ressource
            , ':log_id'    => $this->id
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
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

        $params = array(
            ':general' => $this->general
            , ':log_id'  => $this->id
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
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

        $params = array(
            ':aspect' => $this->aspect
            , ':log_id' => $this->id
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
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

        $params = array(
            ':action' => $this->action
            , ':log_id' => $this->id
        );

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    public function get_account()
    {
        return $this->account;
    }

    public static function create(appbox &$appbox, API_OAuth2_Account $account, $route, $status_code, $format, $ressource, $general = null, $aspect = null, $action = null)
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
          api_log_ressource,
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
        :ressource,
        :general,
        :aspect,
        :action
      )';

        $params = array(
            ':account_id'  => $account->get_id(),
            ':route'       => $route,
            ':status_code' => $status_code,
            ':format'      => $format,
            ':ressource'   => $ressource,
            ':general'     => $general,
            ':aspect'      => $aspect,
            ':action'      => $action
        );

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $log_id = $appbox->get_connection()->lastInsertId();

        return new self($appbox, $log_id);
    }
}
