<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Bridge_AccountSettings
{
    /**
     *
     * @var appbox
     */
    protected $appbox;

    /**
     *
     * @var Bridge_Account
     */
    protected $account;

    /**
     *
     * @param  appbox                 $appbox
     * @param  Bridge_Account         $account
     * @return Bridge_AccountSettings
     */
    public function __construct(appbox $appbox, Bridge_Account $account)
    {
        $this->appbox = $appbox;
        $this->account = $account;

        return $this;
    }

    /**
     *
     * @param  string $key
     * @param  mixed  $default_value
     * @return mixed
     */
    public function get($key, $default_value = null)
    {
        $sql = 'SELECT value FROM bridge_account_settings
            WHERE account_id = :account_id AND `key` = :key';

        $params = [':account_id' => $this->account->get_id(), ':key'        => $key];

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return isset($row['value']) ? $row['value'] : $default_value;
    }

    /**
     *
     * @param  string $key
     * @param  string $value
     * @return string
     */
    public function set($key, $value)
    {
        $sql = 'REPLACE INTO bridge_account_settings
            (account_id, `key`, value) VALUES (:account_id, :key, :value)';

        $params = [
            ':value'      => $value
            , ':account_id' => $this->account->get_id()
            , ':key'        => $key
        ];

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $value;
    }

    /**
     *
     * @param  string $key
     * @return string
     */
    public function delete($key)
    {
        $return_value = $this->get($key);

        $sql = 'DELETE FROM bridge_account_settings
            WHERE account_id = :account_id AND `key` = :key';

        $params = [':account_id' => $this->account->get_id(), ':key'        => $key];

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        return $return_value;
    }
}
