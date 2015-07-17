<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Exception\RuntimeException;

class API_Webhook
{
    const NEW_FEED_ENTRY = "new_feed_entry";

    const USER_REGISTRATION_GRANTED = "user.registration.granted";

    const USER_REGISTRATION_REJECTED = "user.registration.rejected";

    const USER_DELETED = "user.deleted";

    public static function create(appbox $appbox, $type, array $data)
    {
        $sql = 'INSERT INTO api_webhooks (`type`, `data`, `created`)
            VALUES (:type, :data, NOW())';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(
            'type' => $type,
            'data' => json_encode($data),
        ));
        $stmt->closeCursor();

        return new self($appbox, $appbox->get_connection()->lastInsertId());
    }

    /**
     * @var appbox
     */
    protected $appbox;
    /**
     * @var int
     */
    protected $id;
    /**
     * @var string
     */
    protected $type;
    /**
     * @var string JSON encoded event payload
     */
    protected $data;
    /**
     * @var DateTime
     */
    protected $created;

    /**
     * @param appbox $appbox
     * @param $id
     */
    public function __construct(appbox $appbox, $id)
    {
        $this->appbox = $appbox;
        $this->id = $id;
        $sql = 'SELECT w.type, w.data, w.created FROM api_webhooks w WHERE w.id = :id';
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
