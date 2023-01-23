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
use Alchemy\Phrasea\Utilities\NullableDateTime;

class Bridge_Element
{
    /**
     * @var appbox
     */
    protected $app;

    /**
     * @var account
     */
    protected $account;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var record_adapter
     */
    protected $record;

    /**
     * @var string
     */
    protected $dist_id;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $connector_status;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $datas;

    /**
     * @var DateTime
     */
    protected $created_on;

    /**
     * @var DateTime
     */
    protected $uploaded_on;

    /**
     * @var DateTime
     */
    protected $updated_on;

    /**
     * @var Bridge_Api_ElementInterface
     */
    protected $connector_element;

    const STATUS_DONE = 'done';
    const STATUS_PROCESSING_SERVER = 'processing_server';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PENDING = 'pending';
    const STATUS_ERROR = 'error';

    public function __construct(Application $app, Bridge_Account $account, $id)
    {
        $this->app = $app;
        $this->account = $account;
        $this->id = (int) $id;

        $sql = 'SELECT sbas_id, record_id, dist_id, status, connector_status, type
                  , title, serialized_datas, created_on, updated_on, uploaded_on
            FROM bridge_elements WHERE id = :id';
        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':id' => $this->id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new Bridge_Exception_ElementNotFound('Element Not Found');

        $this->record = new record_adapter($app, $row['sbas_id'], $row['record_id']);
        $this->dist_id = $row['dist_id'];
        $this->status = $row['status'];
        $this->connector_status = $row['connector_status'];

        $this->title = $row['title'];
        $this->type = $row['type'];
        $this->datas = unserialize($row['serialized_datas']);
        $this->updated_on = new DateTime($row['updated_on']);
        $this->created_on = new DateTime($row['created_on']);
        $this->uploaded_on = $row['uploaded_on'] ? new DateTime($row['uploaded_on']) : null;

        return $this;
    }

    /**
     *
     * @return Bridge_Account
     */
    public function get_account()
    {
        return $this->account;
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
     * @return record_adapter
     */
    public function get_record()
    {
        return $this->record;
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
     * @param  string         $dist_id
     * @return Bridge_Element
     */
    public function set_dist_id($dist_id)
    {
        $this->dist_id = $dist_id;
        $this->updated_on = new DateTime();

        $sql = 'UPDATE bridge_elements
            SET dist_id = :dist_id, updated_on = :update WHERE id = :id';

        $params = [
            ':dist_id' => $this->dist_id
            , ':id'      => $this->id
            , ':update'  => $this->updated_on->format(DATE_ISO8601)
        ];

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_status()
    {
        return $this->status;
    }

    /**
     *
     * @param  string         $status
     * @return Bridge_Element
     */
    public function set_status($status)
    {
        $this->status = $status;
        $this->updated_on = new DateTime();

        $sql = 'UPDATE bridge_elements
            SET status = :status, updated_on = :update WHERE id = :id';

        $params = [
            ':status' => $this->status
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
     * @return string
     */
    public function get_connector_status()
    {
        return $this->connector_status;
    }

    /**
     *
     * @param  string         $status
     * @return Bridge_Element
     */
    public function set_connector_status($status)
    {
        $this->connector_status = $status;
        $this->updated_on = new DateTime();

        $sql = 'UPDATE bridge_elements
            SET connector_status = :connector_status, updated_on = :update
            WHERE id = :id';

        $params = [
            ':connector_status' => $this->connector_status
            , ':id'               => $this->id
            , ':update'           => $this->updated_on->format(DATE_ISO8601)
        ];

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_title()
    {
        return $this->title;
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
     * @return Bridge_Api_ElementInterface
     */
    public function build_connector_element()
    {
        if (! $this->connector_element) {
            try {
                $this->connector_element = $this->account->get_api()->get_element_from_id($this->dist_id, $this->type);
            } catch (\Exception $e) {
                return null;
            }
        }

        return $this->connector_element;
    }

    /**
     *
     * @param  string         $title
     * @return Bridge_Element
     */
    public function set_title($title)
    {
        $this->title = $title;
        $this->updated_on = new DateTime();

        $sql = 'UPDATE bridge_elements
            SET title = :title, updated_on = :update WHERE id = :id';

        $params = [
            ':title'  => $this->title
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
     * @return array
     */
    public function get_datas()
    {
        return $this->datas;
    }

    /**
     *
     * @param  array          $datas
     * @return Bridge_Element
     */
    public function set_datas(Array $datas)
    {
        $this->datas = $datas;
        $this->updated_on = new DateTime();

        $sql = 'UPDATE bridge_elements
            SET serialized_datas = :datas, updated_on = :update WHERE id = :id';

        $params = [
            ':datas'  => serialize($this->datas)
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
     * @return DateTime
     */
    public function get_created_on()
    {
        return $this->created_on;
    }

    /**
     * @todo write tests
     *
     * @return DateTime
     */
    public function get_uploaded_on()
    {
        return $this->uploaded_on;
    }

    /**
     * @todo write tests
     *
     * @return DateTime
     */
    public function set_uploaded_on(DateTime $date = null)
    {
        $this->uploaded_on = $date;
        $this->updated_on = new DateTime();

        $sql = 'UPDATE bridge_elements
            SET uploaded_on = :uploaded_on, updated_on = :update WHERE id = :id';

        $params = [
            ':uploaded_on' => NullableDateTime::format($this->uploaded_on, DATE_ISO8601),
            ':id' => $this->id,
            ':update' => $this->updated_on->format(DATE_ISO8601),
        ];

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    /**
     * @return DateTime
     */
    public function get_updated_on()
    {
        return $this->updated_on;
    }

    /**
     *
     * @return Void
     */
    public function delete()
    {
        $sql = 'DELETE FROM bridge_elements WHERE id = :id';

        $stmt = $this->app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':id' => $this->id]);
        $stmt->closeCursor();

        return;
    }

    public static function get_elements_by_account(Application $app, Bridge_Account $account, $offset_start = 0, $quantity = 50)
    {
        $sql = 'SELECT id FROM bridge_elements WHERE account_id = :account_id
            ORDER BY id DESC
            LIMIT ' . (int) $offset_start . ',' . (int) $quantity;

        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':account_id' => $account->get_id()]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $results = [];

        foreach ($rs as $row) {
            $results[] = new Bridge_Element($app, $account, $row['id']);
        }

        return $results;
    }

    public static function create(Application $app, Bridge_Account $account, record_adapter $record, $title, $status, $type, Array $datas = [])
    {
        $sql = 'INSERT INTO bridge_elements
            (id, account_id, sbas_id, record_id, dist_id, title, `type`
              , serialized_datas, status, created_on, updated_on)
            VALUES
            (null, :account_id, :sbas_id, :record_id, null, :title, :type
              ,:datas , :status, NOW(), NOW())';

        $params = [
            ':account_id' => $account->get_id(),
            ':sbas_id' => $record->getDataboxId(),
            ':record_id' => $record->getRecordId(),
            ':status' => $status,
            ':title' => $title,
            ':type' => $type,
            ':datas' => serialize($datas),
        ];

        $connection = $app->getApplicationBox()->get_connection();
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $element_id = $connection->lastInsertId();

        return new self($app, $account, $element_id);
    }
}
