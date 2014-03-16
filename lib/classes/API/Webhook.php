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
use Alchemy\Phrasea\Exception\RuntimeException;

class API_Webhook
{
    const NEW_FEED_ENTRY = "new_feed_entry";

    protected $appbox;
    protected $id;
    protected $type;
    protected $data;
    protected $created;

    public function __construct(appbox $appbox, $id)
    {
        $this->appbox = $appbox;
        $this->id = $id;
        $sql = 'SELECT `type`, `data`, created
            FROM api_webhooks
            WHERE id = :id';
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':id' => $id));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            throw new RuntimeException('Webhooks not found');
        }

        $stmt->closeCursor();

        $this->type = $row['type'];
        $this->data = json_decode($row['data']);
        $this->created = new \DateTime($row['created']);
    }

    public function delete()
    {
        $sql = 'DELETE FROM api_webhooks WHERE id = :id';

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':id' => $this->id));
        $stmt->closeCursor();

        return;
    }

    public static function create(appbox $appbox, $type, array $data)
    {
        $sql = 'INSERT INTO api_webhooks (id, `type`, `data`, created)
            VALUES (null, :type, :data, NOW())';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(
            'type' => $type,
            'data' => json_encode($data),
        ));
        $stmt->closeCursor();

        return new API_Webhook($appbox, $appbox->get_connection()->lastInsertId());
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }
}
